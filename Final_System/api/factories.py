"""
This file will create objects for app
"""
import logging

import ai.config as my_config
from ai.classifier import Classifier
from ai.card_detector import CardDetector
from ai.detector import Detector
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from api.mapper import Mapper
from api.service import InferenceService

logger = logging.getLogger(__name__)


def create_voting() -> TemporalVoting:
    return TemporalVoting()


def create_mapper() -> Mapper:
    return Mapper()


def create_service() -> InferenceService:
    """
    Load the classifier and card detector once and build one detector/tracker per camera on demand.
    Model loading errors are raised (fail fast) instead of returning a half-built service.
    """
    uniform_classifier = Classifier(my_config.CLASSIFY_UNIFORM_PATH, len(my_config.UNIFORM_LABELS),
                                    labels=my_config.UNIFORM_LABELS)
    card_detector = CardDetector(my_config.DETECT_CARD_PATH, my_config.DEVICE,
                                 my_config.DETECT_CARD_CONF, my_config.DETECT_CARD_IOU,
                                 my_config.DETECT_CARD_IMAGE_SIZE)

    def pipeline_factory() -> Pipeline:
        detector = Detector(my_config.DETECT_PERSON_PATH, my_config.DEVICE, my_config.DETECT_CONF)
        return Pipeline(detector=detector, uniform_classifier=uniform_classifier, card_detector=card_detector)

    # Build one pipeline now so a broken detector weight file is reported at startup.
    pipeline_factory()
    logger.info("Đã nạp mô hình AI (detector người + classifier đồng phục + detector thẻ YOLO).")
    return InferenceService(pipeline_factory=pipeline_factory, voting_factory=create_voting, mapper=create_mapper())
