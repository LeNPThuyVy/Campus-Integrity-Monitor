from ultralytics import YOLO
from dataclasses import dataclass
import numpy as np
import logging
import ai.config


@dataclass
class CardDetection:
    """
    Represents one card bounding box detected inside a person crop.
    bbox   : [x1, y1, x2, y2] in the coordinate space of the crop
    confidence : detection confidence score (0.0 – 1.0)
    """
    bbox: list[float]
    confidence: float


class CardDetector:

    def __init__(self, model_path, device, conf, iou, image_size):
        """
        Load a YOLO model trained to detect student ID cards.
        There must be: load model, put model to device, set runtime thresholds.

        Unlike the old MobileNetV3 classifier (which received a person crop and
        output a class label), this YOLO detector receives the same crop and
        outputs bounding boxes for any cards it finds inside that crop.
        A card is considered present when at least one detection exists.
        """
        try:
            self.model = YOLO(model=model_path).to(device=device)
            self.conf       = conf
            self.iou        = iou
            self.image_size = image_size
            logging.info("CardDetector: model loaded successfully!")
        except Exception as e:
            logging.error(f"CardDetector: cannot load model – {e}")
            raise  #After log throw exception


    def detect(self, image: np.ndarray) -> list[CardDetection]:
        """
        Run YOLO detection on a single person-crop image.
        Returns a list of CardDetection; empty list means no card found.

        Steps:
        1. Run model inference with configured conf / iou / image_size
        2. Parse boxes from the first (and only) result
        3. Map each box to a CardDetection dataclass
        """
        inference_result = self.model.predict(
            source=image,
            conf=self.conf,
            iou=self.iou,
            imgsz=self.image_size,
            verbose=False
        )
        boxes = inference_result[0].boxes
        detections = []

        for i in range(len(boxes)):
            coord      = boxes.xyxy[i].tolist()
            confidence = boxes.conf[i].item()
            detections.append(CardDetection(bbox=coord, confidence=confidence))

        return detections


    def detect_batch(self, images: list[np.ndarray]) -> list[list[CardDetection]]:
        """
        Run YOLO detection on a batch of person-crop images.
        Returns a list-of-lists: one inner list of CardDetection per image.

        YOLO natively supports batch inference, so we pass the whole list at
        once instead of looping – this keeps GPU utilisation high and avoids
        the per-frame Python overhead that would occur with a loop over detect().
        """
        if not images:
            return []

        inference_results = self.model.predict(
            source=images,
            conf=self.conf,
            iou=self.iou,
            imgsz=self.image_size,
            verbose=False
        )

        batch_detections = []
        for result in inference_results:
            boxes = result.boxes
            per_image = []
            for i in range(len(boxes)):
                coord      = boxes.xyxy[i].tolist()
                confidence = boxes.conf[i].item()
                per_image.append(CardDetection(bbox=coord, confidence=confidence))
            batch_detections.append(per_image)

        return batch_detections
