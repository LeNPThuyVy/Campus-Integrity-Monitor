from dataclasses import dataclass, field
from pathlib import Path
import cv2
import ai.config as my_config
from ai.classifier import Prediction, Classifier
from ai.card_detector import CardDetector, CardDetection
from ai.detector import Detector
from ai.my_utils import Utils


@dataclass
class PipelineResult:
    """
    Save the result after pipeline
    """
    track_id: int
    bbox: list[float]
    uniform_prediction: Prediction
    card_prediction: Prediction = None
    prediction: Prediction = None
    card_detections: list[CardDetection] = field(default_factory=list)

    def __post_init__(self):
        if self.prediction is None and self.uniform_prediction is not None:
            self.prediction = self.uniform_prediction

class Pipeline:
    def __init__(self, detector: Detector, classifier: Classifier = None, card_classifier: Classifier = None, uniform_classifier: Classifier = None, card_detector: CardDetector = None):
        self.detector = detector
        self.uniform_classifier = uniform_classifier if uniform_classifier is not None else classifier
        self.card_classifier = card_classifier
        self.card_detector = card_detector
        self.classifier = self.uniform_classifier
        self._debug_counts: dict[int, int] = {}

    def run(self, frame) -> list[PipelineResult]:
        """
        The process would be:
        1. Detect people in frame
            The detector returns list bbox
        2. Filter out people who are too far away (bbox_height / frame_height < MIN_PERSON_HEIGHT_RATIO)
        3. Crop each person
        4. Classify uniform (MobileNetV3) and detect card (YOLO CardDetector) for each person

        This function returns list[PipelineResult]
        """
        results = []
        results_detected = self.detector.track(frame)
        
        valid_results = []
        valid_crops = []
        uniform_crops = []
        crop_offsets = []

        for result in results_detected:
            if not Utils.is_person_large_enough(frame.shape, result.bbox):
                continue

            x1 = max(0, int(result.bbox[0]))
            y1 = max(0, int(result.bbox[1]))
            image_cropped = Utils.crop_person(frame=frame, bbox=result.bbox)
            uniform_cropped = Utils.crop_uniform_region(frame=frame, bbox=result.bbox)
            if image_cropped is not None and image_cropped.size > 0 and uniform_cropped.size > 0:
                valid_results.append(result)
                valid_crops.append(image_cropped)
                uniform_crops.append(uniform_cropped)
                crop_offsets.append((x1, y1))

        if not valid_crops:
            return []

        # 1. Uniform classification on the shirt region (head excluded)
        uniform_preds = self.uniform_classifier.classify_batch(uniform_crops) if self.uniform_classifier else [None] * len(uniform_crops)
        
        # 2. Card detection via YOLO (or fallback to card_classifier if present)
        card_detections_batch = []
        card_preds = []
        
        if self.card_detector is not None:
            raw_batch_detections = self.card_detector.detect_batch(valid_crops)
            for i, dets in enumerate(raw_batch_detections):
                offset_x, offset_y = crop_offsets[i]
                frame_dets = []
                for d in dets:
                    cx1, cy1, cx2, cy2 = d.bbox
                    frame_bbox = [cx1 + offset_x, cy1 + offset_y, cx2 + offset_x, cy2 + offset_y]
                    frame_dets.append(CardDetection(bbox=frame_bbox, confidence=d.confidence))
                card_detections_batch.append(frame_dets)
                
                if frame_dets:
                    best_conf = max(d.confidence for d in frame_dets)
                    card_preds.append(Prediction(label="Card", confidence=best_conf))
                else:
                    card_preds.append(Prediction(label="No_Card", confidence=1.0))
        elif self.card_classifier is not None:
            card_preds = self.card_classifier.classify_batch(valid_crops)
            card_detections_batch = [[] for _ in valid_crops]
        else:
            card_preds = [None] * len(valid_crops)
            card_detections_batch = [[] for _ in valid_crops]

        if getattr(my_config, "DEBUG_SAVE_CROPS", 0):
            self._save_debug_crops(valid_results, uniform_crops, uniform_preds)

        for i, result in enumerate(valid_results):
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_preds[i],
                card_prediction=card_preds[i],
                prediction=uniform_preds[i],
                card_detections=card_detections_batch[i]
            ))
        
        return results

    def _save_debug_crops(self, detections, crops, predictions):
        """
        Save each classified crop as <track_id>_<n>_<label>_<conf>.jpg so the raw model
        output can be inspected separately from temporal voting
        """
        out_dir = Path(my_config.DEBUG_CROPS_DIR)
        out_dir.mkdir(parents=True, exist_ok=True)
        max_per_track = getattr(my_config, "DEBUG_CROPS_MAX_PER_TRACK", 30)
        for detection, crop, pred in zip(detections, crops, predictions):
            if pred is None:
                continue
            count = self._debug_counts.get(detection.track_id, 0)
            if count >= max_per_track:
                continue
            self._debug_counts[detection.track_id] = count + 1
            name = f"{detection.track_id}_{count:03d}_{pred.label}_{pred.confidence:.2f}.jpg"
            cv2.imwrite(str(out_dir / name), crop)
