"""
This file contains the core business logic. 
After done the business logic, it returns the final processed result back to the routing layer
"""
import numpy as np
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from api.mapper import Mapper
import time


class InferenceService:
    def __init__(self, pipeline:Pipeline, voting:TemporalVoting, mapper:Mapper):
        self.pipeline = pipeline
        self.voting = voting
        self.mapper=mapper
    
    def predict(self,frame: np.ndarray) :
        """
        This function will run AI logic
        """
        start_time=time.perf_counter()

        pipeline_results= self.pipeline.run(frame)
        #Get bbox 
        bbox_map={}
        for pipeline_result in pipeline_results:
            bbox_map[pipeline_result.track_id]=self.mapper.mapping_bbox(pipeline_result.bbox)
        self.voting.update(pipeline_results)
        results=self.voting.vote()
        listDetect=[]
        for key,value in results.items():
            listDetect.append(self.mapper.mapping_detectionResponse(track_id=key,bbox=bbox_map[key],label=value.label,matched_count=value.matched_count))

        end_time=time.perf_counter()
        execution_time =end_time-start_time

        return listDetect,execution_time*1000
