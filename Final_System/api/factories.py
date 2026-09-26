"""
This file will create objects for app 
"""

from ai.classifier import Classifier
from ai.detector import Detector
from ai.temporal_voting import TemporalVoting
from ai.pipeline import Pipeline
from api.mapper import Mapper
import ai.config as my_config
from api.service import InferenceService


def create_voting() -> TemporalVoting:
    try:
        print("Create temporal successfully")
        return TemporalVoting()
    except Exception as e:
        print(f"Error: {e}")
        return


def create_pipeline() -> Pipeline:
    try:
        #Load models
        detector=Detector(my_config.DETECT_PERSON_PATH,my_config.DEVICE,my_config.DETECT_CONF)
        uniform_classifier=Classifier(my_config.CLASSIFY_UNIFORM_PATH,len(my_config.UNIFORM_LABELS),labels=my_config.UNIFORM_LABELS)
        card_classifier=Classifier(my_config.CLASSIFY_CARD_PATH,len(my_config.CARD_LABELS),labels=my_config.CARD_LABELS)
        #Create pipeline
        pipeline= Pipeline(detector=detector, uniform_classifier=uniform_classifier, card_classifier=card_classifier)
        print("Create pipeline successfully")
        return pipeline
    except Exception as e:
        print(f"Error: {e}")
        return

def create_mapper() -> Mapper:
    try:
        print("Create mapper successfully")
        return Mapper()
    except Exception as e:
        print(f"Error: {e}")
        return

def create_service() -> InferenceService:
    pipeline=create_pipeline()
    voting=create_voting()
    mapper=create_mapper()
    return InferenceService(pipeline=pipeline,voting=voting,mapper=mapper)
