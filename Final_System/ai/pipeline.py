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
        
        valid_results = []
        valid_crops = []
        
        for result in results_detected:
            image_cropped=Utils.crop_person(frame=frame,bbox=result.bbox)
            if image_cropped is not None and image_cropped.size > 0:
                valid_results.append(result)
                valid_crops.append(image_cropped)
                
        if not valid_crops:
            return []

        uniform_preds = self.uniform_classifier.classify_batch(valid_crops) if self.uniform_classifier else [None]*len(valid_crops)
        card_preds = self.card_classifier.classify_batch(valid_crops) if self.card_classifier else [None]*len(valid_crops)

        for i, result in enumerate(valid_results):
            results.append(PipelineResult(
                track_id=result.track_id,
                bbox=result.bbox,
                uniform_prediction=uniform_preds[i],
                card_prediction=card_preds[i],
                prediction=uniform_preds[i]
            ))
        
        return results

