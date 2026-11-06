from ultralytics import YOLO
from dataclasses import dataclass
import numpy as np
import logging
import ai.config as my_config

@dataclass
class TrackResult:
    bbox: list[float]
    track_id: int


class Detector:
    def __init__(self,model_path, device,conf, iou=0.7, image_size=None):
        """
        Init model -> Load YOLO to device
        Check exist model
        """
        try:
            self.device = str(device)
            self.model=YOLO(model=model_path).to(device=device)
            self.conf=conf
            self.iou=iou
            self.image_size = image_size if image_size is not None else getattr(my_config, "DETECT_IMAGE_SIZE", 480)
            self._warmup()
            logging.info("Load model successfully!")
        except Exception as e:
            logging.error(f"Can't load model, error {e}")
            raise #After log throw exception

    def _warmup(self):
        try:
            dummy = np.zeros((self.image_size, self.image_size, 3), dtype=np.uint8)
            self.model.predict(source=dummy, imgsz=self.image_size, verbose=False)
        except Exception:
            pass

    def track(self,frame: np.ndarray) -> list[TrackResult]:
        """
        This function will detect and track people in frame
        Return list of bbox 
        """
        imgsz = getattr(my_config, "DETECT_IMAGE_SIZE", self.image_size)
        tracking_result=self.model.track(
            source=frame,
            tracker="botsort.yaml",
            classes=[0],
            conf=self.conf,
            iou=self.iou,
            imgsz=imgsz,
            half=(self.device == "cuda"),
            persist=True,
            verbose=False
        ) 
        boxes=tracking_result[0].boxes
        results=[]

        if boxes.id is not None:
            for i in range(len(boxes.id)):
                track_id=int(boxes.id[i].item())
                coord=boxes.xyxy[i].tolist()
                results.append(TrackResult(bbox=coord,track_id=track_id))
        return results

    def reset_tracker(self):
        """
        Forget all tracks (new video source). Track IDs start again from 1 afterwards.
        """
        predictor = getattr(self.model, "predictor", None)
        for tracker in getattr(predictor, "trackers", None) or []:
            try:
                tracker.reset()
            except Exception as e:
                logging.warning(f"Can't reset tracker: {e}")
