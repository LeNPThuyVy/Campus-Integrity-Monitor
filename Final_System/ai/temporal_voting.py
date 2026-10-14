import ai.config as my_config
from collections import deque
from ai.pipeline import PipelineResult
from dataclasses import dataclass
import logging

@dataclass
class VotingResult:
    label: str
    matched_count: int
    uniform_label: str = "Waiting"
    card_label: str = "Waiting"
    is_final: bool = False

class TemporalVoting:
    def __init__(self):
        """
        histories contain track_id history for uniform and card classification
        """
        self.len_history = getattr(my_config, "LEN_HISTORY", 30)
        self.uniform_histories = {}
        self.card_histories = {}
        self.missing_counter = {}
        self.current_uniform_labels = {}
        self.current_card_labels = {}

    def update(self, new_pipeline_result: list[PipelineResult]): 
        """
        this function will update history by appending new class results
        and update missing counter if the id doesn't appear after k frames
        """
        for result in new_pipeline_result:
            if result.uniform_prediction is not None:
                if result.uniform_prediction.confidence >= getattr(my_config, "CLASSIFY_CONF", 0.0):
                    self.uniform_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append({
                        "label": result.uniform_prediction.label,
                        "confidence": result.uniform_prediction.confidence
                    })
            
            if result.card_detections is not None:
                # Store the max confidence if card found, else 0.0
                if len(result.card_detections) > 0:
                    max_conf = max(d.confidence for d in result.card_detections)
                    self.card_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append({
                        "label": "Card",
                        "confidence": max_conf
                    })
                else:
                    self.card_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append({
                        "label": "No_Card",
                        "confidence": 1.0  # Implicit confidence for no detection
                    })
            self.missing_counter[result.track_id] = 0

        #Get the id doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame = {result.track_id for result in new_pipeline_result}
        all_tracked_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())
        disappear_id = list(all_tracked_ids - ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id] = self.missing_counter.get(track_id, 0) + 1

    def vote(self) -> dict[int, VotingResult]:
        """
        Voting v2: Sequential decision.
        """
        results_voting = {}
        all_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())
        
        min_samples = getattr(my_config, "MIN_SAMPLES", 4)
        confirm_score = getattr(my_config, "CONFIRM_SCORE", 2.5)

        for track_id in all_ids:
            u_history = self.uniform_histories.get(track_id, deque())
            c_history = self.card_histories.get(track_id, deque())

            # --- Uniform Voting ---
            prev_u = self.current_uniform_labels.get(track_id, "Waiting")
            if len(u_history) < min_samples:
                u_label = "Waiting"
            else:
                score_uniform = sum(item["confidence"] for item in u_history if item["label"] == "Uniform")
                score_non_uniform = sum(item["confidence"] for item in u_history if item["label"] == "Non_Uniform")
                
                # Apply hysteresis if already confirmed
                if prev_u == "Uniform":
                    score_uniform *= 1.2
                elif prev_u == "Non_Uniform":
                    score_non_uniform *= 1.2
                    
                if score_uniform >= confirm_score and score_uniform >= score_non_uniform:
                    u_label = "Uniform"
                elif score_non_uniform >= confirm_score and score_non_uniform > score_uniform:
                    u_label = "Non_Uniform"
                else:
                    u_label = prev_u if prev_u != "Waiting" else "Waiting"
            self.current_uniform_labels[track_id] = u_label

            # --- Card Voting ---
            prev_c = self.current_card_labels.get(track_id, "Waiting")
            if len(c_history) < min_samples:
                c_label = "Waiting"
            else:
                card_hits = sum(1 for item in c_history if item["label"] == "Card" and item["confidence"] >= getattr(my_config, "DETECT_CARD_CONF", 0.25))
                
                if card_hits >= 2: # OR logic, fast confirm
                    c_label = "Card"
                else:
                    # Require solid proof of No_Card
                    if len(c_history) >= min_samples + 1:
                        c_label = "No_Card"
                    else:
                        c_label = prev_c if prev_c != "Waiting" else "Waiting"
                        
                # Hysteresis
                if prev_c == "Card" and card_hits >= 1:
                    c_label = "Card"
            self.current_card_labels[track_id] = c_label

            matched_cnt = max(len(u_history), len(c_history))
            results_voting[track_id] = VotingResult(
                label=f"{u_label} | {c_label}",
                matched_count=matched_cnt,
                uniform_label=u_label,
                card_label=c_label,
                is_final=False
            )

        return results_voting
        
    def get_finalized_and_remove(self) -> dict[int, VotingResult]:
        """
        Remove track_id if counter >= MISSING_COUNTER_THRESHOLD.
        Returns final votes for those removed tracks so they can be logged.
        """
        missing_id = []
        for track_id, counter in self.missing_counter.items():
            if counter >= getattr(my_config, "MISSING_COUNTER_THRESHOLD", 15):
                missing_id.append(track_id)
                
        finalized_votes = {}
        min_final = getattr(my_config, "MIN_SAMPLES_FINAL", 3)
        
        for track_id in missing_id:
            u_history = self.uniform_histories.get(track_id, [])
            c_history = self.card_histories.get(track_id, [])
            
            u_label = self.current_uniform_labels.get(track_id, "Waiting")
            if u_label == "Waiting" and len(u_history) >= min_final:
                # Force best effort
                score_u = sum(item["confidence"] for item in u_history if item["label"] == "Uniform")
                score_nu = sum(item["confidence"] for item in u_history if item["label"] == "Non_Uniform")
                u_label = "Uniform" if score_u >= score_nu else "Non_Uniform"
                
            c_label = self.current_card_labels.get(track_id, "Waiting")
            if c_label == "Waiting" and len(c_history) >= min_final:
                card_hits = sum(1 for item in c_history if item["label"] == "Card" and item["confidence"] >= getattr(my_config, "DETECT_CARD_CONF", 0.25))
                c_label = "Card" if card_hits >= 1 else "No_Card"
                
            if u_label != "Waiting" or c_label != "Waiting":
                finalized_votes[track_id] = VotingResult(
                    label=f"{u_label} | {c_label}",
                    matched_count=max(len(u_history), len(c_history)),
                    uniform_label=u_label,
                    card_label=c_label,
                    is_final=True
                )

            self.uniform_histories.pop(track_id, None)
            self.card_histories.pop(track_id, None)
            self.missing_counter.pop(track_id, None)
            self.current_uniform_labels.pop(track_id, None)
            self.current_card_labels.pop(track_id, None)
            
        return finalized_votes
