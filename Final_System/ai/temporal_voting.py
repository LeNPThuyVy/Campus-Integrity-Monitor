import ai.config as my_config
from collections import deque,Counter
from ai.pipeline import PipelineResult
from dataclasses import dataclass

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
        self.len_history=my_config.LEN_HISTORY
        self.uniform_histories = {}
        self.card_histories = {}
        self.histories = self.uniform_histories
        self.missing_counter={}

    def update(self,new_pipeline_result: list[PipelineResult]): 
        """
        this function will update history by appending new class results
        and update missing counter if the id doesn't appear after k frames
        """
        for result in new_pipeline_result:
            if result.uniform_prediction is not None:
                self.uniform_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append(result.uniform_prediction.label)
            if result.card_prediction is not None:
                self.card_histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append(result.card_prediction.label)
            self.missing_counter[result.track_id]=0

        #Get the id doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame={
            result.track_id
            for result in new_pipeline_result}
        all_tracked_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())
        disappear_id=list(all_tracked_ids - ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id] = self.missing_counter.get(track_id, 0) + 1
        #Remove the id disappear after update
        self.remove_track_id()


    def vote(self):
        """
        Voting depend on VOTING_THRESHOLD
        If len(history) too short -> wait until len(history)>= history_threshold
        This function returns VotingResult with uniform_label and card_label
        """
        results_voting={}
        all_ids = set(self.uniform_histories.keys()) | set(self.card_histories.keys())
        for track_id in all_ids:
            u_history = self.uniform_histories.get(track_id, deque())
            c_history = self.card_histories.get(track_id, deque())

            if len(u_history) < my_config.VOTING_THREDSHOLD:
                u_label = "Waiting"
            else:
                u_stats = Counter(u_history)
                u_label = "Uniform" if u_stats["Uniform"] >= my_config.VOTING_THREDSHOLD else "Non_Uniform"

            if len(c_history) < my_config.VOTING_THREDSHOLD:
                c_label = "Waiting"
            else:
                c_stats = Counter(c_history)
                c_label = "Card" if c_stats["Card"] >= my_config.VOTING_THREDSHOLD else "No_Card"

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
        missing_id=[]
        for track_id,counter in self.missing_counter.items():
            if counter>=my_config.MISSING_COUNTER_THRESHOLD:
                missing_id.append(track_id)
        for track_id in missing_id:
            self.uniform_histories.pop(track_id,None)
            self.card_histories.pop(track_id,None)
            self.missing_counter.pop(track_id,None)
    
