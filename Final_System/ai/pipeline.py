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
    prediction: Prediction

class Pipeline:
    def __init__(self, detector: Detector, classifier: Classifier):
        self.detector=detector
        self.classifier=classifier
    
    def run(self,frame)-> list[PipelineResult]:
        """
        The process would be:
        1. Detect people in frame
            The detector returns list bbox
        2. Crop each person
        3. Classify for each person

        This function returns Pipeline_Result
        """
        results=[]
        results_detected=self.detector.track(frame)
        for result in results_detected:
            image_cropped=Utils.crop_person(frame=frame,bbox=result.bbox)
            result_predicted= self.classifier.classify(image=image_cropped)
            results.append(PipelineResult(result.track_id,result.bbox,result_predicted))
        
        return results

