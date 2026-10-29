from datetime import datetime, timedelta, timezone

import numpy as np
import pytest

import ai.config as my_config
from ai.classifier import Prediction
from ai.pipeline import PipelineResult
from ai.temporal_voting import TemporalVoting
from ai.violation_events import ViolationEventDetector, combined_status, crop_evidence
from ai.my_utils import Utils


@pytest.fixture(autouse=True)
def default_config(monkeypatch):
    monkeypatch.setattr(my_config, "VOTING_THREDSHOLD", 5)
    monkeypatch.setattr(my_config, "VIOLATION_RATIO", 0.6)
    monkeypatch.setattr(my_config, "CLASSIFY_CONF", 0.4)
    monkeypatch.setattr(my_config, "NON_UNIFORM_CONF", 0.0)
    monkeypatch.setattr(my_config, "LABEL_HOLD_SECONDS", 0.0)
    monkeypatch.setattr(my_config, "HYSTERESIS_MARGIN", 0.0)
    monkeypatch.setattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.25)
    monkeypatch.setattr(my_config, "MISSING_COUNTER_THRESHOLD", 3)
    monkeypatch.setattr(my_config, "LEN_HISTORY", 10)


def result(track_id, uniform, card, conf=0.9, bbox=(10, 10, 110, 210)):
    return PipelineResult(track_id=track_id, bbox=list(bbox), uniform_prediction=Prediction(uniform, conf),
                          card_prediction=Prediction(card, conf))


def feed(voting, frames, now=None):
    for frame in frames:
        voting.update(frame)
    return voting.vote(now=now)


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


def test_uniform_confidence_threshold_demotes_low_conf_uniform_and_keeps_non_uniform():
    voting = TemporalVoting()
    # Uniform with conf < CLASSIFY_CONF (0.4) becomes Non_Uniform in the queue
    votes = feed(voting, [[PipelineResult(
        track_id=1,
        bbox=[10, 10, 110, 210],
        uniform_prediction=Prediction("Uniform", 0.2),
        card_prediction=Prediction("Card", 0.9),
    )]] * 5)
    assert votes[1].uniform_label == "Non_Uniform"
    assert votes[1].matched_count == 5

    # Non_Uniform with conf < CLASSIFY_CONF (0.4) stays Non_Uniform when NON_UNIFORM_CONF=0.0
    voting.reset()
    votes = feed(voting, [[PipelineResult(
        track_id=1,
        bbox=[10, 10, 110, 210],
        uniform_prediction=Prediction("Non_Uniform", 0.2),
        card_prediction=Prediction("Card", 0.9),
    )]] * 5)
    assert votes[1].uniform_label == "Non_Uniform"
    assert votes[1].matched_count == 5

    # Uniform with conf >= CLASSIFY_CONF (0.4) stays Uniform in the queue
    voting.reset()
    votes = feed(voting, [[PipelineResult(
        track_id=1,
        bbox=[10, 10, 110, 210],
        uniform_prediction=Prediction("Uniform", 0.7),
        card_prediction=Prediction("Card", 0.9),
    )]] * 5)
    assert votes[1].uniform_label == "Uniform"
    assert votes[1].matched_count == 5


def test_non_uniform_confidence_threshold_flips_low_conf_to_uniform(monkeypatch):
    monkeypatch.setattr(my_config, "NON_UNIFORM_CONF", 0.70)
    monkeypatch.setattr(my_config, "CLASSIFY_CONF", 0.40)
    voting = TemporalVoting()

    # Non_Uniform with conf 0.55 (< NON_UNIFORM_CONF 0.70) is converted to Uniform
    votes = feed(voting, [[PipelineResult(
        track_id=1,
        bbox=[10, 10, 110, 210],
        uniform_prediction=Prediction("Non_Uniform", 0.55),
        card_prediction=Prediction("Card", 0.9),
    )]] * 5)
    assert votes[1].uniform_label == "Uniform"

    # Non_Uniform with conf 0.85 (>= NON_UNIFORM_CONF 0.70) stays Non_Uniform
    voting.reset()
    votes = feed(voting, [[PipelineResult(
        track_id=1,
        bbox=[10, 10, 110, 210],
        uniform_prediction=Prediction("Non_Uniform", 0.85),
        card_prediction=Prediction("Card", 0.9),
    )]] * 5)
    assert votes[1].uniform_label == "Non_Uniform"


def test_label_hold_seconds_prevents_rapid_class_switching(monkeypatch):
    monkeypatch.setattr(my_config, "LABEL_HOLD_SECONDS", 5.0)
    monkeypatch.setattr(my_config, "HYSTERESIS_MARGIN", 0.0)
    voting = TemporalVoting()

    # Initially decide Non_Uniform at t = 100.0
    votes = feed(voting, [[result(1, "Non_Uniform", "Card")]] * 5, now=100.0)
    assert votes[1].uniform_label == "Non_Uniform"

    # Feed 10 Uniform frames at t = 102.0 (only 2s elapsed, < 5.0s hold duration)
    # The queue is now 100% Uniform, but label MUST remain Non_Uniform due to hold timer
    votes = feed(voting, [[result(1, "Uniform", "Card")]] * 10, now=102.0)
    assert votes[1].uniform_label == "Non_Uniform"

    # At t = 106.0 (> 5.0s elapsed), voting now allows flipping to Uniform
    votes = voting.vote(now=106.0)
    assert votes[1].uniform_label == "Uniform"


def test_hysteresis_margin_prevents_boundary_flicker(monkeypatch):
    monkeypatch.setattr(my_config, "LABEL_HOLD_SECONDS", 0.0)
    monkeypatch.setattr(my_config, "VIOLATION_RATIO", 0.60)
    monkeypatch.setattr(my_config, "HYSTERESIS_MARGIN", 0.20)
    monkeypatch.setattr(my_config, "LEN_HISTORY", 10)
    monkeypatch.setattr(my_config, "VOTING_THREDSHOLD", 5)
    voting = TemporalVoting()

    # 6 Non_Uniform + 4 Uniform = 60% violation -> Non_Uniform
    frames = [[result(1, "Non_Uniform", "Card")]] * 6 + [[result(1, "Uniform", "Card")]] * 4
    votes = feed(voting, frames)
    assert votes[1].uniform_label == "Non_Uniform"

    # Now change window to 5 Non_Uniform + 5 Uniform (50% violation)
    # 50% < base ratio 60%, but > (60% - 20% = 40%) -> Hysteresis keeps Non_Uniform!
    voting.reset()
    frames = [[result(1, "Non_Uniform", "Card")]] * 6
    feed(voting, frames)
    # Feed 1 Uniform so history has 5 Non_Uniform + 1 Uniform... let's feed exact pattern:
    voting.reset()
    frames = [[result(1, "Non_Uniform", "Card")]] * 7
    feed(voting, frames)
    # Then push 5 Uniforms: history has 5 Uniform + 5 Non_Uniform (50% violation)
    votes = feed(voting, [[result(1, "Uniform", "Card")]] * 5)
    assert votes[1].uniform_label == "Non_Uniform"

    # Only when violation drops to <= 40% (e.g. 7 Uniform + 3 Non_Uniform = 30%) does it flip
    votes = feed(voting, [[result(1, "Uniform", "Card")]] * 3)
    assert votes[1].uniform_label == "Uniform"


def test_min_person_height_ratio_skips_distant_person(monkeypatch):
    monkeypatch.setattr(my_config, "MIN_PERSON_HEIGHT_RATIO", 0.25)
    frame_shape = (1080, 1920, 3)

    # Distant person: height 150px / 1080px = 0.138 < 0.25 -> False
    far_bbox = [100, 100, 200, 250]
    assert Utils.is_person_large_enough(frame_shape, far_bbox) is False

    # Close person: height 400px / 1080px = 0.370 >= 0.25 -> True
    close_bbox = [100, 100, 400, 500]
    assert Utils.is_person_large_enough(frame_shape, close_bbox) is True


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
