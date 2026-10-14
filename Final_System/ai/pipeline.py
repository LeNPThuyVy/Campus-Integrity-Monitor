from dataclasses import dataclass
import ai.config as my_config
from ai.classifier import Prediction, Classifier
from ai.card_detector import CardDetector, CardDetection
from ai.detector import Detector
from ai.my_utils import Utils


@dataclass
class PipelineResult:
    """
    Save the result after pipeline.
    card_detections : list of CardDetection boxes found inside the person crop
                      (empty list  → no card detected)
    """
    track_id: int
    bbox: list[float]
    uniform_prediction: Prediction
    card_detections: list[CardDetection] = None
    prediction: Prediction = None

    def __post_init__(self):
        if self.prediction is None and self.uniform_prediction is not None:
            self.prediction = self.uniform_prediction


class Pipeline:
    def __init__(self, detector: Detector, classifier: Classifier = None,
                 card_detector: CardDetector = None, uniform_classifier: Classifier = None):
        """
        detector          : YOLO person tracker (unchanged)
        uniform_classifier: MobileNetV3 classifier for uniform detection (unchanged)
        card_detector     : YOLO card detector (replaces old MobileNetV3 card classifier)
        classifier        : legacy alias for uniform_classifier (kept for backwards-compat)
        """
        self.detector           = detector
        self.uniform_classifier = uniform_classifier if uniform_classifier is not None else classifier
        self.card_detector      = card_detector
        self.classifier         = self.uniform_classifier

    def run(self, frame, frame_count: int = 0, last_classified: dict = None, current_labels: dict = None) -> list[PipelineResult]:
        """
        The process would be:
        1. Detect people in frame
            The detector returns list bbox
        2. Crop each person
        3. Filter out people too far (person_height_ratio < MIN_PERSON_HEIGHT_RATIO)
        4. Classify uniform for evaluated persons (MobileNetV3)
        5. Detect card inside evaluated person crops (YOLO detector)

        This function returns list[PipelineResult]
        """
        results = []
        results_detected = self.detector.track(frame)

        valid_results = []
        valid_crops   = []
        eval_indices  = []

        image_h = frame.shape[0] if frame is not None else 0
        last_classified = last_classified if last_classified is not None else {}
        current_labels = current_labels if current_labels is not None else {}

        cadence_new = getattr(my_config, "CLASSIFY_CADENCE_NEW", 2)
        cadence_confirmed = getattr(my_config, "CLASSIFY_CADENCE_CONFIRMED", 15)

        for result in results_detected:
            image_cropped = Utils.crop_person(frame=frame, bbox=result.bbox)
            if image_cropped is not None and image_cropped.size > 0:
                valid_results.append(result)
                valid_crops.append(image_cropped)

                # Check if person is large enough for evaluation
                x1, y1, x2, y2 = result.bbox
                person_h = max(0, y2 - y1)
                person_height_ratio = person_h / image_h if image_h > 0 else 0.0

                min_ratio = getattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.06)
                if person_height_ratio >= min_ratio:
                    tid = result.track_id
                    lbl = current_labels.get(tid, "Waiting")
                    last_f = last_classified.get(tid, -999)
                    
                    cadence = cadence_confirmed if lbl != "Waiting" else cadence_new
                    if frame_count - last_f >= cadence:
                        eval_indices.append(len(valid_crops) - 1)
                        last_classified[tid] = frame_count

        if not valid_crops:
            return []

        uniform_preds = [None] * len(valid_crops)
        card_detections_batch = [None] * len(valid_crops)

        if eval_indices:
            eval_crops = [valid_crops[i] for i in eval_indices]

            # Uniform classification (MobileNetV3 batch)
            if self.uniform_classifier:
                eval_uniform_preds = self.uniform_classifier.classify_batch(eval_crops)
                for idx, crop_i in enumerate(eval_indices):
                    uniform_preds[crop_i] = eval_uniform_preds[idx]

            # Card detection (YOLO batch)
            if self.card_detector:
                card_roi = getattr(my_config, "CARD_ROI", (0.1, 0.6))
                card_crops = []
                for crop in eval_crops:
                    h, w = crop.shape[:2]
                    top = int(h * card_roi[0])
                    bottom = int(h * card_roi[1])
                    card_crops.append(crop[top:bottom, :])
                    
                eval_card_detections = self.card_detector.detect_batch(card_crops)
                for idx, crop_i in enumerate(eval_indices):
                    top_offset = int(valid_crops[crop_i].shape[0] * card_roi[0])
                    detections = []
                    for d in eval_card_detections[idx]:
                        shifted_bbox = [d.bbox[0], d.bbox[1] + top_offset, d.bbox[2], d.bbox[3] + top_offset]
                        detections.append(CardDetection(bbox=shifted_bbox, confidence=d.confidence))
                    card_detections_batch[crop_i] = detections

        for i, result in enumerate(valid_results):
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_preds[i],
                card_detections=card_detections_batch[i],
                prediction=uniform_preds[i]
            ))

        return results
