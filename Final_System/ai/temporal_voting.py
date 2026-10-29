import time
from collections import deque
from dataclasses import dataclass

import ai.config as my_config
from ai.pipeline import PipelineResult

WAITING = "Waiting"
UNIFORM, NON_UNIFORM = "Uniform", "Non_Uniform"
CARD, NO_CARD = "Card", "No_Card"


@dataclass
class VotingResult:
    label: str
    matched_count: int
    uniform_label: str = WAITING
    card_label: str = WAITING
    uniform_confidence: float | None = None
    card_confidence: float | None = None


def _decide_stable(
    history,
    positive: str,
    negative: str,
    locked_info: tuple[str, float] | None,
    now: float
) -> tuple[str, float | None, tuple[str, float] | None]:
    """
    Decide label with temporal stability:
    1. Fewer than VOTING_THREDSHOLD -> Waiting
    2. Hysteresis threshold to prevent flickering around the boundary
    3. LABEL_HOLD_SECONDS: once decided, hold label for minimum seconds before flipping
    """
    if len(history) < my_config.VOTING_THREDSHOLD:
        return WAITING, None, None

    negatives = [confidence for label, confidence in history if label == negative]
    positives = [confidence for label, confidence in history if label == positive]
    neg_ratio = len(negatives) / len(history)
    neg_conf = (sum(negatives) / len(negatives)) if negatives else None
    pos_conf = (sum(positives) / len(positives)) if positives else None

    base_violation_ratio = getattr(my_config, "VIOLATION_RATIO", 0.6)
    hysteresis = getattr(my_config, "HYSTERESIS_MARGIN", 0.20)
    hold_seconds = getattr(my_config, "LABEL_HOLD_SECONDS", 2.0)

    # First decision for this track
    if locked_info is None:
        if neg_ratio >= base_violation_ratio:
            chosen = negative
            conf = neg_conf
        else:
            chosen = positive
            conf = pos_conf
        return chosen, conf, (chosen, now)

    prev_label, locked_time = locked_info

    # Determine candidate label using hysteresis threshold
    if prev_label == negative:
        # Require violation ratio to drop below (base_ratio - hysteresis) to flip back to positive
        flip_threshold = max(0.0, base_violation_ratio - hysteresis)
        candidate = positive if neg_ratio <= flip_threshold else negative
    else:
        # Require violation ratio to meet base_ratio to flip to negative
        candidate = negative if neg_ratio >= base_violation_ratio else positive

    # If candidate agrees with current locked label, keep it
    if candidate == prev_label:
        chosen = prev_label
        conf = neg_conf if chosen == negative else pos_conf
        return chosen, conf, locked_info

    # Candidate differs: check if hold duration has elapsed
    if (now - locked_time) < hold_seconds:
        chosen = prev_label
        conf = neg_conf if chosen == negative else pos_conf
        return chosen, conf, locked_info

    # Hold duration has passed: flip to new label and restart hold timer
    chosen = candidate
    conf = neg_conf if chosen == negative else pos_conf
    return chosen, conf, (chosen, now)


class TemporalVoting:
    def __init__(self):
        """
        Histories contain track_id history for uniform and card classification.
        Independent thresholds:
        - Uniform votes need confidence >= CLASSIFY_CONF, otherwise the frame is skipped
        - Non_Uniform votes need confidence >= NON_UNIFORM_CONF, otherwise the frame is skipped
        """
        self.len_history = my_config.LEN_HISTORY
        self.uniform_histories = {}
        self.card_histories = {}
        self.histories = self.uniform_histories
        self.missing_counter = {}
        self.uniform_locked_state: dict[int, tuple[str, float]] = {}
        self.card_locked_state: dict[int, tuple[str, float]] = {}

    def reset(self, len_history: int | None = None):
        """Forget every track (new video source, user reset or changed window length)."""
        if len_history is not None:
            self.len_history = len_history
        self.uniform_histories.clear()
        self.card_histories.clear()
        self.missing_counter.clear()
        self.uniform_locked_state.clear()
        self.card_locked_state.clear()

    def _append(self, histories: dict, track_id: int, prediction, min_conf: float = None):
        if prediction is None:
            return
        threshold = my_config.CLASSIFY_CONF if min_conf is None else min_conf
        if prediction.confidence < threshold:
            return
        histories.setdefault(track_id, deque(maxlen=self.len_history)).append(
            (prediction.label, float(prediction.confidence))
        )

    def _append_uniform(
        self,
        track_id: int,
        prediction,
        min_conf: float = None,
        non_uniform_min_conf: float = None
    ):
        if prediction is None:
            return
        uniform_thresh = my_config.CLASSIFY_CONF if min_conf is None else min_conf
        non_uniform_thresh = (
            getattr(my_config, "NON_UNIFORM_CONF", my_config.CLASSIFY_CONF)
            if non_uniform_min_conf is None
            else non_uniform_min_conf
        )

        # Uncertain predictions are skipped, never converted into a vote for the opposite class
        threshold = uniform_thresh if prediction.label == UNIFORM else non_uniform_thresh
        if prediction.confidence < threshold:
            return

        self.uniform_histories.setdefault(track_id, deque(maxlen=self.len_history)).append(
            (prediction.label, float(prediction.confidence))
        )

    def update(self, new_pipeline_result: list[PipelineResult]):
        """
        Update history by appending new classification results
        and update missing counter if the id doesn't appear after k frames
        """
        card_min_conf = getattr(my_config, "DETECT_CARD_CONF", my_config.CLASSIFY_CONF)
        for result in new_pipeline_result:
            self._append_uniform(
                result.track_id,
                result.uniform_prediction,
                min_conf=my_config.CLASSIFY_CONF,
                non_uniform_min_conf=getattr(my_config, "NON_UNIFORM_CONF", my_config.CLASSIFY_CONF)
            )
            self._append(self.card_histories, result.track_id, result.card_prediction, min_conf=card_min_conf)
            self.missing_counter[result.track_id] = 0

        # Get the id that doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame = {result.track_id for result in new_pipeline_result}
        all_tracked_ids = set(self.missing_counter.keys())
        disappear_id = list(all_tracked_ids - ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id] = self.missing_counter.get(track_id, 0) + 1
            
        # Remove tracks that disappeared for too long
        self.remove_track_id()

    def vote(self, now: float | None = None) -> dict[int, VotingResult]:
        """
        Voting depends on VOTING_THREDSHOLD, VIOLATION_RATIO, HYSTERESIS_MARGIN and LABEL_HOLD_SECONDS.
        Returns VotingResult with uniform_label and card_label.
        """
        current_time = time.monotonic() if now is None else now
        results_voting = {}
        for track_id in self.missing_counter:
            u_history = self.uniform_histories.get(track_id, ())
            c_history = self.card_histories.get(track_id, ())
            
            u_locked = self.uniform_locked_state.get(track_id)
            c_locked = self.card_locked_state.get(track_id)

            u_label, u_conf, new_u_locked = _decide_stable(u_history, UNIFORM, NON_UNIFORM, u_locked, current_time)
            c_label, c_conf, new_c_locked = _decide_stable(c_history, CARD, NO_CARD, c_locked, current_time)

            if new_u_locked is not None:
                self.uniform_locked_state[track_id] = new_u_locked
            if new_c_locked is not None:
                self.card_locked_state[track_id] = new_c_locked

            results_voting[track_id] = VotingResult(
                label=u_label,
                matched_count=max(len(u_history), len(c_history)),
                uniform_label=u_label,
                card_label=c_label,
                uniform_confidence=u_conf,
                card_confidence=c_conf,
            )

        return results_voting

    def remove_track_id(self):
        """
        Remove track_id if counter >= MISSING_COUNTER_THRESHOLD
        """
        missing_id = []
        for track_id, counter in self.missing_counter.items():
            if counter >= my_config.MISSING_COUNTER_THRESHOLD:
                missing_id.append(track_id)
        for track_id in missing_id:
            self.uniform_histories.pop(track_id, None)
            self.card_histories.pop(track_id, None)
            self.missing_counter.pop(track_id, None)
            self.uniform_locked_state.pop(track_id, None)
            self.card_locked_state.pop(track_id, None)
