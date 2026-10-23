"""
Smoke test with the real trained weights in Final_System/models and a sample photo shipped with
ultralytics (bus.jpg, contains people). It checks that the models load and the pipeline produces
well-formed results. It does NOT measure accuracy: there is no labelled campus dataset in the repo.
"""
from pathlib import Path

import cv2
import pytest

import ai.config as my_config

pytestmark = pytest.mark.models


@pytest.fixture(scope="module")
def sample_frame():
    ultralytics = pytest.importorskip("ultralytics")
    path = Path(ultralytics.__file__).parent / "assets" / "bus.jpg"
    if not path.exists():
        pytest.skip("Không có ảnh mẫu bus.jpg của ultralytics.")
    return cv2.imread(str(path))


@pytest.fixture(scope="module")
def pipeline():
    for weight in (my_config.DETECT_PERSON_PATH, my_config.CLASSIFY_UNIFORM_PATH, my_config.DETECT_CARD_PATH):
        if not Path(weight).exists():
            pytest.skip(f"Thiếu trọng số {weight}")
    from ai.classifier import Classifier
    from ai.card_detector import CardDetector
    from ai.detector import Detector
    from ai.pipeline import Pipeline

    detector = Detector(my_config.DETECT_PERSON_PATH, my_config.DEVICE, my_config.DETECT_CONF, my_config.IOU_THRESHOLD)
    uniform = Classifier(my_config.CLASSIFY_UNIFORM_PATH, 2, labels=my_config.UNIFORM_LABELS)
    card_detector = CardDetector(my_config.DETECT_CARD_PATH, my_config.DEVICE, my_config.DETECT_CARD_CONF, my_config.DETECT_CARD_IOU, my_config.DETECT_CARD_IMAGE_SIZE)
    return Pipeline(detector=detector, uniform_classifier=uniform, card_detector=card_detector)


def test_pipeline_runs_on_real_weights(pipeline, sample_frame):
    results = pipeline.run(sample_frame)
    assert results, "Detector không phát hiện người nào trong ảnh mẫu có người."
    for result in results:
        assert result.uniform_prediction.label in my_config.UNIFORM_LABELS
        assert result.card_prediction.label in my_config.CARD_LABELS
        assert 0.0 <= result.uniform_prediction.confidence <= 1.0
        assert hasattr(result, "card_detections")
        x1, y1, x2, y2 = result.bbox
        assert x2 > x1 and y2 > y1


def test_tracker_reset_restarts_ids(pipeline, sample_frame):
    pipeline.detector.reset_tracker()
    first = {r.track_id for r in pipeline.run(sample_frame)}
    pipeline.detector.reset_tracker()
    second = {r.track_id for r in pipeline.run(sample_frame)}
    assert first and second and min(second) == 1
