import queue
import numpy as np
import ai.config as my_config
from ai.card_detector import CardDetection
from ai.classifier import Prediction
from ai.detector import TrackResult
from ai.pipeline import Pipeline
from desktop.app import App
from desktop.ui import CampusMonitorUI

BASE_PARAMS = {
    "DETECT_CONF": 0.5, "CLASSIFY_CONF": 0.6, "IOU_THRESHOLD": 0.45, "FRAME_SKIP": 2, "LEN_HISTORY": 300,
    "VOTING_THREDSHOLD": 10, "VIOLATION_RATIO": 0.55, "MISSING_COUNTER_THRESHOLD": 5, "EVENT_DEDUP_SECONDS": 60,
    "EVENT_MIN_TRACK_SECONDS": 1, "API_URL": "", "CAMERA_CODE": "",
}


def test_long_voting_window_is_kept():
    params = App.validate_params(BASE_PARAMS)
    assert params["LEN_HISTORY"] == 300
    assert params["VOTING_THREDSHOLD"] == 10
    assert params["EVENT_MIN_TRACK_SECONDS"] == 1.0


def test_voting_window_limits_still_apply():
    assert App.validate_params({**BASE_PARAMS, "LEN_HISTORY": 5000})["LEN_HISTORY"] == 1000
    assert App.validate_params({**BASE_PARAMS, "LEN_HISTORY": 1})["LEN_HISTORY"] == 3
    # The minimum number of votes can never exceed the window.
    assert App.validate_params({**BASE_PARAMS, "LEN_HISTORY": 8})["VOTING_THREDSHOLD"] == 8


def test_put_latest_always_delivers_eof_when_queue_full():
    q = queue.Queue(maxsize=2)
    q.put(("frame1", [], {}, 0.0))
    q.put(("frame2", [], {}, 0.0))
    assert q.full()
    App._put_latest(q, ("EOF", None, None, None))
    items = [q.get_nowait(), q.get_nowait()]
    assert items[-1][0] == "EOF"


def test_truncate_filename_keeps_long_names_compact():
    short = "video.mp4"
    assert CampusMonitorUI._truncate_filename(short, max_chars=24) == short
    long_name = "1793282322679_212616707037960741_623267756836308336.mp4"
    truncated = CampusMonitorUI._truncate_filename(long_name, max_chars=24)
    assert len(truncated) <= 24
    assert truncated.endswith(".mp4")
    assert "..." in truncated


def test_pipeline_card_skip_when_locked(monkeypatch):
    class DummyDetector:
        def track(self, frame):
            return [TrackResult(bbox=[50, 20, 250, 420], track_id=7)]

    class DummyUniform:
        def classify_batch(self, crops):
            return [Prediction(label="Uniform", confidence=0.92) for _ in crops]

    class DummyCard:
        def __init__(self):
            self.calls = 0

        def detect_batch(self, crops):
            self.calls += 1
            return [[CardDetection(bbox=[10, 10, 40, 40], confidence=0.88)] for _ in crops]

    monkeypatch.setattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.25)
    monkeypatch.setattr(my_config, "CARD_SKIP_WHEN_LOCKED", 1)
    monkeypatch.setattr(my_config, "CARD_SKIP_CONSECUTIVE_FRAMES", 3)

    card_det = DummyCard()
    pipe = Pipeline(detector=DummyDetector(), uniform_classifier=DummyUniform(), card_detector=card_det)
    frame = np.zeros((480, 640, 3), dtype=np.uint8)

    # First 3 frames call card_detector; 4th and 5th frames reuse the locked Card state
    for _ in range(5):
        out = pipe.run(frame)
        assert len(out) == 1
        assert out[0].card_prediction.label == "Card"
    assert card_det.calls == 3

    # After reset, card_detector runs again
    pipe.reset()
    pipe.run(frame)
    assert card_det.calls == 4
