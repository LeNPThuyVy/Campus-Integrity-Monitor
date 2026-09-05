from dataclasses import dataclass
from ai.classifier import Prediction,Classifier
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

    def __post_init__(self):
        if self.prediction is None and self.uniform_prediction is not None:
            self.prediction = self.uniform_prediction

class Pipeline:
    def __init__(self, detector: Detector, classifier: Classifier = None, card_classifier: Classifier = None, uniform_classifier: Classifier = None):
        self.detector = detector
        self.uniform_classifier = uniform_classifier if uniform_classifier is not None else classifier
        self.card_classifier = card_classifier
        self.classifier = self.uniform_classifier
    
    def run(self,frame)-> list[PipelineResult]:
        """
        The process would be:
        1. Detect people in frame
            The detector returns list bbox
        2. Crop each person
        3. Classify uniform and card for each person

        This function returns list[PipelineResult]
        """
        results=[]
        results_detected=self.detector.track(frame)
        for result in results_detected:
            image_cropped=Utils.crop_person(frame=frame,bbox=result.bbox)
            uniform_pred = self.uniform_classifier.classify(image=image_cropped) if self.uniform_classifier else None
            card_pred = self.card_classifier.classify(image=image_cropped) if self.card_classifier else None
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_pred,
                card_prediction=card_pred,
                prediction=uniform_pred
            ))
        
        return results

