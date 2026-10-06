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

    def run(self, frame, skip_classification: bool = False) -> list[PipelineResult]:
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

        for result in results_detected:
            image_cropped = Utils.crop_person(frame=frame, bbox=result.bbox)
            if image_cropped is not None and image_cropped.size > 0:
                valid_results.append(result)
                valid_crops.append(image_cropped)

                # Check if person is large enough for evaluation
                x1, y1, x2, y2 = result.bbox
                person_h = max(0, y2 - y1)
                person_height_ratio = person_h / image_h if image_h > 0 else 0.0

                min_ratio = getattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.1)
                if person_height_ratio >= min_ratio:
                    eval_indices.append(len(valid_crops) - 1)

        if not valid_crops:
            return []

        uniform_preds = [None] * len(valid_crops)
        card_detections_batch = [None] * len(valid_crops)

        if not skip_classification and eval_indices:
            eval_crops = [valid_crops[i] for i in eval_indices]

            # Uniform classification (MobileNetV3 batch)
            if self.uniform_classifier:
                eval_uniform_preds = self.uniform_classifier.classify_batch(eval_crops)
                for idx, crop_i in enumerate(eval_indices):
                    uniform_preds[crop_i] = eval_uniform_preds[idx]

            # Card detection (YOLO batch)
            if self.card_detector:
                eval_card_detections = self.card_detector.detect_batch(eval_crops)
                for idx, crop_i in enumerate(eval_indices):
                    card_detections_batch[crop_i] = eval_card_detections[idx]

        for i, result in enumerate(valid_results):
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_preds[i],
                card_detections=card_detections_batch[i],
                prediction=uniform_preds[i]
            ))

        return results
