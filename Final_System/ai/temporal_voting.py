import ai.config as my_config
from collections import deque,Counter
from ai.pipeline import PipelineResult
from dataclasses import dataclass
import logging

@dataclass
class VotingResult:
    label: str
    matched_count: int
    uniform_label: str = "Waiting"
    card_label: str = "Waiting"

class TemporalVoting:
    def __init__(self):
        """
        histories contain track_id history for uniform and card classification
        """
        self.len_history = my_config.LEN_HISTORY
        self.uniform_histories = {}
        self.card_histories = {}
        self.histories = self.uniform_histories
        self.missing_counter = {}
        self.current_uniform_labels = {}
        self.current_card_labels = {}

    def update(self, new_pipeline_result: list[PipelineResult]): 
        """
        this function will update history by appending new class results
        and update missing counter if the id doesn't appear after k frames
        """
        classify_conf = getattr(my_config, "CLASSIFY_CONF", 0.8)
        card_conf= getattr(my_config,"DETECT_CARD_CONF", 0.25)
        for result in new_pipeline_result:
            if result.uniform_prediction is not None:
                if result.uniform_prediction.label == "Uniform":
                    if result.uniform_prediction.confidence >=classify_conf:
                        self.uniform_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append("Uniform")
                    else:
                        self.uniform_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append("Non_Uniform")
                else:
                    self.uniform_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append("Non_Uniform")
            if result.card_detections is not None:
                valid_cards = [d for d in result.card_detections if d.confidence >= card_conf]
                c_label = "Card" if len(valid_cards) > 0 else "No_Card"
                self.card_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append(c_label)
            self.missing_counter[result.track_id] = 0

        #Get the id doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame = {
            result.track_id
            for result in new_pipeline_result}
        all_tracked_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())
        disappear_id = list(all_tracked_ids - ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id] = self.missing_counter.get(track_id, 0) + 1
        #Remove the id disappear after update
        self.remove_track_id()


    def vote(self):
        """
        Voting with Hysteresis (Dual Threshold) to prevent flickering.
        - VOTING_HIGH_THRESHOLD: count needed to transition to Positive state ("Uniform" / "Card")
        - VOTING_LOW_THRESHOLD: count below which state transitions to Negative state ("Non_Uniform" / "No_Card")
        - Between LOW and HIGH: retain current label state
        """
        logging.debug("uniform_histories (len=%d): %s", len(self.uniform_histories), dict(self.uniform_histories))
        logging.debug("card_histories: %s", dict(self.card_histories))
        results_voting = {}
        all_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())

        high_thresh = getattr(my_config, "VOTING_HIGH_THRESHOLD", 22)
        low_thresh = getattr(my_config, "VOTING_LOW_THRESHOLD", 10)
        history_thresh = getattr(my_config, "HISTORY_THRESHOLD", 25)

        for track_id in all_ids:
            u_history = self.uniform_histories.get(track_id, deque())
            c_history = self.card_histories.get(track_id, deque())

            # --- Uniform Hysteresis Voting ---
            if len(u_history) < history_thresh:
                u_label = "Waiting"
            else:
                u_stats = Counter(u_history)
                u_cnt = u_stats.get("Uniform", 0)
                prev_u = self.current_uniform_labels.get(track_id, "Waiting")

                if prev_u == "Waiting":
                    if u_cnt > high_thresh:
                        u_label = "Uniform"
                    elif u_cnt <= low_thresh:
                        u_label = "Non_Uniform"
                    else:
                        u_label = "Uniform" if u_cnt > (high_thresh * 0.75) else "Non_Uniform"
                elif prev_u == "Uniform":
                    u_label = "Non_Uniform" if u_cnt <= low_thresh else "Uniform"
                else:  # prev_u == "Non_Uniform"
                    u_label = "Uniform" if u_cnt > high_thresh else "Non_Uniform"

                self.current_uniform_labels[track_id] = u_label

            # --- Card Hysteresis Voting ---
            if len(c_history) < history_thresh:
                c_label = "Waiting"
            else:
                c_stats = Counter(c_history)
                c_cnt = c_stats.get("Card", 0)
                prev_c = self.current_card_labels.get(track_id, "Waiting")

                if prev_c == "Waiting":
                    if c_cnt >= high_thresh:
                        c_label = "Card"
                    elif c_cnt < low_thresh:
                        c_label = "No_Card"
                    else:
                        c_label = "Card" if c_cnt >= (high_thresh * 0.75) else "No_Card"
                elif prev_c == "Card":
                    c_label = "No_Card" if c_cnt < low_thresh else "Card"
                else:  # prev_c == "No_Card"
                    c_label = "Card" if c_cnt >= high_thresh else "No_Card"

                self.current_card_labels[track_id] = c_label

            matched_cnt = max(len(u_history), len(c_history))
            results_voting[track_id] = VotingResult(
                label=u_label,
                matched_count=matched_cnt,
                uniform_label=u_label,
                card_label=c_label
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
            self.current_uniform_labels.pop(track_id, None)
            self.current_card_labels.pop(track_id, None)
    
