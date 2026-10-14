from datetime import datetime, timedelta, timezone

import numpy as np
import pytest

import ai.config as my_config
from ai.classifier import Prediction
from ai.pipeline import PipelineResult
from ai.temporal_voting import TemporalVoting
from ai.violation_events import ViolationEventDetector, combined_status, crop_evidence


@pytest.fixture(autouse=True)
def default_config(monkeypatch):
    monkeypatch.setattr(my_config, "VOTING_THREDSHOLD", 5)
    monkeypatch.setattr(my_config, "VIOLATION_RATIO", 0.6)
    monkeypatch.setattr(my_config, "CLASSIFY_CONF", 0.4)
    monkeypatch.setattr(my_config, "MISSING_COUNTER_THRESHOLD", 3)
    monkeypatch.setattr(my_config, "LEN_HISTORY", 10)


def result(track_id, uniform, card, conf=0.9, bbox=(10, 10, 110, 210)):
    return PipelineResult(track_id=track_id, bbox=list(bbox), uniform_prediction=Prediction(uniform, conf),
                          card_prediction=Prediction(card, conf))


def feed(voting, frames):
    for frame in frames:
        voting.update(frame)
    return voting.vote()


def test_waiting_until_minimum_frames():
    voting = TemporalVoting()
    votes = feed(voting, [[result(1, "Non_Uniform", "No_Card")]] * 4)
    assert votes[1].uniform_label == "Waiting" and votes[1].card_label == "Waiting"
    votes = feed(voting, [[result(1, "Non_Uniform", "No_Card")]])
    assert votes[1].uniform_label == "Non_Uniform" and votes[1].card_label == "No_Card"


def test_majority_ratio_prevents_false_alarm():
    """Old rule flagged 'Uniform' with only 7/20 votes; now a violation needs >= 60% of frames."""
    voting = TemporalVoting()
    frames = [[result(1, "Non_Uniform", "Card")]] * 5 + [[result(1, "Uniform", "Card")]] * 5
    votes = feed(voting, frames)
    assert votes[1].uniform_label == "Uniform"  # 50% violation < 60% -> no violation
    # Window of 10 now holds 4 Uniform + 6 Non_Uniform -> 60% -> violation.
    votes = feed(voting, [[result(1, "Non_Uniform", "Card")]] * 6)
    assert votes[1].uniform_label == "Non_Uniform"


def test_low_confidence_predictions_are_ignored():
    voting = TemporalVoting()
    votes = feed(voting, [[result(1, "Non_Uniform", "No_Card", conf=0.2)]] * 10)
    assert votes[1].uniform_label == "Waiting"
    assert votes[1].matched_count == 0


def test_track_removed_after_missing_frames_and_reset():
    voting = TemporalVoting()
    feed(voting, [[result(1, "Uniform", "Card")]] * 5)
    feed(voting, [[]] * 3)
    assert 1 not in voting.vote()
    feed(voting, [[result(2, "Uniform", "Card")]])
    voting.reset()
    assert voting.vote() == {} and voting.card_histories == {}


def test_combined_status_four_states():
    assert combined_status("Uniform", "Card") == "COMPLIANT"
    assert combined_status("Non_Uniform", "Card") == "UNIFORM"
    assert combined_status("Uniform", "No_Card") == "NO_CARD"
    assert combined_status("Non_Uniform", "No_Card") == "BOTH"
    assert combined_status("Waiting", "No_Card") is None


def _frame():
    rng = np.random.default_rng(0)
    return rng.integers(0, 255, size=(480, 640, 3), dtype=np.uint8)


def test_crop_evidence_validation():
    frame = _frame()
    crop = crop_evidence(frame, (100, 100, 200, 300))
    assert crop is not None and crop.shape[0] > 200 and crop.shape[1] > 100
    assert crop_evidence(frame, (100, 100, 110, 110)) is None  # too small
    assert crop_evidence(np.zeros((480, 640, 3), np.uint8), (100, 100, 200, 300)) is None  # blank frame
    assert crop_evidence(None, (1, 2, 3, 4)) is None
    assert crop_evidence(frame, (200, 200, 100, 100)) is None  # inverted box


def test_event_detector_reports_once_per_track_and_respects_dedup_window():
    voting = TemporalVoting()
    detector = ViolationEventDetector(dedup_seconds=60)
    frame = _frame()
    now = datetime(2026, 10, 13, 8, 0, tzinfo=timezone(timedelta(hours=7)))
    events = []
    for index in range(8):
        frame_results = [result(1, "Non_Uniform", "No_Card"), result(2, "Uniform", "Card", bbox=(300, 50, 400, 300))]
        voting.update(frame_results)
        events += detector.process(frame, frame_results, voting.vote(), now + timedelta(seconds=index))
    assert len(events) == 1
    event = events[0]
    assert event.track_id == 1 and event.violation_code == "BOTH" and event.crop.size > 0
    assert 0.0 < event.uniform_confidence <= 1.0
    # Track disappears for longer than the window -> may be reported again.
    later = now + timedelta(minutes=5)
    frame_results = [result(1, "Non_Uniform", "No_Card")]
    events = detector.process(frame, frame_results, voting.vote(), later)
    assert len(events) == 1


def test_no_event_for_compliant_or_waiting():
    voting = TemporalVoting()
    detector = ViolationEventDetector()
    frame = _frame()
    now = datetime.now(timezone.utc)
    frame_results = [result(1, "Uniform", "Card")]
    for _ in range(6):
        voting.update(frame_results)
    assert detector.process(frame, frame_results, voting.vote(), now) == []
    fresh = TemporalVoting()
    fresh.update([result(5, "Non_Uniform", "No_Card")])
    assert detector.process(frame, [result(5, "Non_Uniform", "No_Card")], fresh.vote(), now) == []
