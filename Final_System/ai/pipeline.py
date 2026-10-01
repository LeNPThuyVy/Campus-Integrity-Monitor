from dataclasses import dataclass
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
    card_prediction : a Prediction-compatible result derived from card_detections
                      so that downstream components (voting, UI) need zero changes
    """
    track_id: int
    bbox: list[float]
    uniform_prediction: Prediction
    card_detections: list[CardDetection] = None
    card_prediction: Prediction = None
    prediction: Prediction = None

    def __post_init__(self):
        # Derive a Prediction-compatible card result from raw detections
        # so TemporalVoting and UI code can stay unchanged
        if self.card_prediction is None and self.card_detections is not None:
            if self.card_detections:
                #Card present: pick the detection with the highest confidence
                best = max(self.card_detections, key=lambda d: d.confidence)
                self.card_prediction = Prediction(label="Card", confidence=best.confidence)
            else:
                self.card_prediction = Prediction(label="No_Card", confidence=1.0)

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

    def run(self, frame) -> list[PipelineResult]:
        """
        The process would be:
        1. Detect people in frame
            The detector returns list bbox
        2. Crop each person
        3. Classify uniform for each person (MobileNetV3 – unchanged)
        4. Detect card inside each person crop (YOLO detector – new)

        This function returns list[PipelineResult]
        """
        results = []
        results_detected = self.detector.track(frame)

        valid_results = []
        valid_crops   = []

        for result in results_detected:
            image_cropped = Utils.crop_person(frame=frame, bbox=result.bbox)
            if image_cropped is not None and image_cropped.size > 0:
                valid_results.append(result)
                valid_crops.append(image_cropped)

        if not valid_crops:
            return []

        # Uniform classification (MobileNetV3 batch – unchanged logic)
        uniform_preds = (
            self.uniform_classifier.classify_batch(valid_crops)
            if self.uniform_classifier
            else [None] * len(valid_crops)
        )

        # Card detection (YOLO batch – replaces old card_classifier.classify_batch)
        card_detections_batch = (
            self.card_detector.detect_batch(valid_crops)
            if self.card_detector
            else [None] * len(valid_crops)
        )

        for i, result in enumerate(valid_results):
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_preds[i],
                card_detections=card_detections_batch[i],
                prediction=uniform_preds[i]
            ))

        return results
