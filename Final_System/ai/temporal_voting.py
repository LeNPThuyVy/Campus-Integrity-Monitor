import ai.config as my_config
from collections import deque
from ai.pipeline import PipelineResult
from dataclasses import dataclass

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


def _decide(history, positive: str, negative: str) -> tuple[str, float | None]:
    """
    Combine several frames into one label to reduce false alarms:
    - fewer than VOTING_THREDSHOLD confident results -> Waiting
    - "negative" (violation) only when its share >= VIOLATION_RATIO, otherwise "positive"
    Returns (label, mean confidence of the frames that agree with the label).
    """
    if len(history) < my_config.VOTING_THREDSHOLD:
        return WAITING, None
    negatives = [confidence for label, confidence in history if label == negative]
    positives = [confidence for label, confidence in history if label == positive]
    if len(negatives) / len(history) >= my_config.VIOLATION_RATIO:
        return negative, sum(negatives) / len(negatives)
    return positive, (sum(positives) / len(positives)) if positives else None


class TemporalVoting:
    def __init__(self):
        """
        histories contain track_id history for uniform and card classification.
        Each entry is (label, confidence); only predictions with confidence >= CLASSIFY_CONF are kept.
        """
        self.len_history=my_config.LEN_HISTORY
        self.uniform_histories = {}
        self.card_histories = {}
        self.histories = self.uniform_histories
        self.missing_counter={}

    def reset(self, len_history: int | None = None):
        """Forget every track (new video source, user reset or changed window length)."""
        if len_history is not None:
            self.len_history = len_history
        self.uniform_histories.clear()
        self.card_histories.clear()
        self.missing_counter.clear()

    def _append(self, histories: dict, track_id: int, prediction, min_conf: float = None):
        if prediction is None:
            return
        threshold = my_config.CLASSIFY_CONF if min_conf is None else min_conf
        if prediction.confidence < threshold:
            return
        histories.setdefault(track_id, deque(maxlen=self.len_history)).append(
            (prediction.label, float(prediction.confidence)))

    def update(self,new_pipeline_result: list[PipelineResult]):
        """
        this function will update history by appending new class results
        and update missing counter if the id doesn't appear after k frames
        """
        card_min_conf = getattr(my_config, "DETECT_CARD_CONF", my_config.CLASSIFY_CONF)
        for result in new_pipeline_result:
            self._append(self.uniform_histories, result.track_id, result.uniform_prediction, min_conf=my_config.CLASSIFY_CONF)
            self._append(self.card_histories, result.track_id, result.card_prediction, min_conf=card_min_conf)
            self.missing_counter[result.track_id]=0

        #Get the id doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame={
            result.track_id
            for result in new_pipeline_result}
        all_tracked_ids = set(self.missing_counter.keys())
        disappear_id=list(all_tracked_ids - ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id] = self.missing_counter.get(track_id, 0) + 1
        #Remove the id disappear after update
        self.remove_track_id()


    def vote(self):
        """
        Voting depend on VOTING_THREDSHOLD (minimum frames) and VIOLATION_RATIO.
        This function returns VotingResult with uniform_label and card_label
        """
        results_voting={}
        for track_id in self.missing_counter:
            u_history = self.uniform_histories.get(track_id, ())
            c_history = self.card_histories.get(track_id, ())
            u_label, u_conf = _decide(u_history, UNIFORM, NON_UNIFORM)
            c_label, c_conf = _decide(c_history, CARD, NO_CARD)
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
        missing_id=[]
        for track_id,counter in self.missing_counter.items():
            if counter>=my_config.MISSING_COUNTER_THRESHOLD:
                missing_id.append(track_id)
        for track_id in missing_id:
            self.uniform_histories.pop(track_id,None)
            self.card_histories.pop(track_id,None)
            self.missing_counter.pop(track_id,None)
