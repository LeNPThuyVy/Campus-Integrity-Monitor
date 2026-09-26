from ultralytics import YOLO
from dataclasses import dataclass
import numpy as np
import logging

@dataclass
class TrackResult:
    bbox: list[float]
    track_id: int


class Detector:
    def __init__(self,model_path, device,conf):
        """
        Init model -> Load YOLO to device
        Check exist model
        """
        try:
            self.model=YOLO(model=model_path).to(device=device)
            self.conf=conf
            logging.info("Load model successfully!")
        except Exception as e:
            logging.error(f"Can't load model, error {e}")
            raise #After log throw exception

        
    
    def track(self,frame: np.ndarray) -> list[TrackResult]:
        """
        This function will detect and track people in frame
        Return list of bbox 
        """
        tracking_result=self.model.track(
            source=frame,
            tracker="botsort.yaml",
            classes=[0],
            conf=self.conf,
            persist=True
        ) 
        boxes=tracking_result[0].boxes
        results=[]

        if boxes.id is not None:
            for i in range(len(boxes.id)):
                track_id=int(boxes.id[i].item())
                coord=boxes.xyxy[i].tolist()
                results.append(TrackResult(bbox=coord,track_id=track_id))
        return results





        


    
    
        