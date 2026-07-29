import ai.config as my_config
from collections import deque,Counter
from ai.pipeline import PipelineResult
from dataclasses import dataclass

@dataclass
class VotingResult:
    label: str
    matched_count:int

class TemporalVoting:
    def __init__(self):
        """
        history is a dict contains: key(track_id) and value(history of the track_id)
        """
        self.len_history=my_config.LEN_HISTORY
        self.histories = {}
        self.missing_counter={}

    def update(self,new_pipeline_result: list[PipelineResult]): 
        """
        this function will update history by append new class result to self.histories.track_id  
        and update missing counter if the id doesn't appear after k frames
        """
        for result in new_pipeline_result:
            #To append without worrying about new value with make old value deleted -> use setdefault
            self.histories.setdefault(result.track_id, deque(maxlen=self.len_history)).append(result.prediction.label)
            self.missing_counter[result.track_id]=0
        #Get the id doesn't appear in new_pipeline_result to update the missing counter
        ids_in_new_frame={
            result.track_id
            for result in new_pipeline_result}
        disappear_id=list(self.histories.keys()-ids_in_new_frame)
        for track_id in disappear_id:
            self.missing_counter[track_id]+=1
        #Remove the id disappear after update
        self.remove_track_id()


    def vote(self):
        """
        Voting depend on VOTING_THRESHOLD
        If len(history) too short -> wait until len(history)>= history_threshold
        This function returns Voting_Result

        ---Haven't done yet: add weight voting -> The lastest frame will have higher score weighted voting
        """
        results_voting={}
        for track_id,history in self.histories.items():
            if len(history)<my_config.VOTING_THREDSHOLD:
                results_voting[track_id]=VotingResult("Waiting", len(history))
                continue
            statistics=Counter(history)
            if statistics["Uniform"]>=my_config.VOTING_THREDSHOLD:
                results_voting[track_id]=VotingResult("Uniform",statistics["Uniform"])
            else:
                results_voting[track_id]=VotingResult("Non_Uniform",statistics["Non_Uniform"])

        return results_voting
    
    def remove_track_id(self):
        """
        Idea for update next version -> remove id by check the last frame it appear and the lastest frame
        lastest_frame - last_frame_appear
        """
        missing_id=[]
        for track_id,counter in self.missing_counter.items():
            if counter>=my_config.MISSING_COUNTER_THRESHOLD:
                missing_id.append(track_id)
        for track_id in missing_id:
            self.histories.pop(track_id,None)
            self.missing_counter.pop(track_id,None)
    
