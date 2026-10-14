import json
from datetime import datetime, timezone

import numpy as np
import pytest
import requests

from ai.violation_events import ViolationEvent
from desktop.violation_sender import ApiConfig, ViolationSender


class FakeResponse:
    def __init__(self, status_code, body=None):
        self.status_code = status_code
        self._body = body or {}

    def json(self):
        return self._body


class FakeSession:
    def __init__(self, outcomes):
        self.outcomes = list(outcomes)
        self.calls = []

    def post(self, url, headers, data, files, timeout):
        self.calls.append({"url": url, "headers": headers, "data": dict(data)})
        outcome = self.outcomes.pop(0)
        if isinstance(outcome, Exception):
            raise outcome
        return outcome


def make_event(event_id="11111111-1111-4111-8111-111111111111"):
    crop = np.random.default_rng(2).integers(0, 255, size=(80, 40, 3), dtype=np.uint8)
    return ViolationEvent(event_id=event_id, track_id=4, violation_code="UNIFORM",
                          occurred_at=datetime(2026, 10, 13, 8, 0, tzinfo=timezone.utc), uniform_confidence=0.91,
                          card_confidence=None, crop=crop)


@pytest.fixture
def sender(tmp_path):
    return ViolationSender(tmp_path, ApiConfig("http://api.local", "CAM01", "secret-key"))


def test_enqueue_persists_event_without_identity(sender):
    assert sender.enqueue(make_event(), "CAM01")
    files = sorted(p.name for p in sender.pending_dir.iterdir())
    assert files == ["11111111-1111-4111-8111-111111111111.jpg", "11111111-1111-4111-8111-111111111111.json"]
    metadata = json.loads((sender.pending_dir / files[1]).read_text(encoding="utf-8"))
    assert metadata["violation_code"] == "UNIFORM" and metadata["camera_code"] == "CAM01"
    assert not {"ma_sv", "ho_ten", "student_id"} & set(metadata)
    assert sender.status.pending == 1


def test_network_error_keeps_event_then_resend_succeeds(sender):
    sender.enqueue(make_event(), "CAM01")
    meta = next(sender.pending_dir.glob("*.json"))
    session = FakeSession([requests.ConnectionError("down"), FakeResponse(201)])
    assert sender.send_one(meta, session) == "retry"
    assert meta.exists() and "Mất kết nối" in sender.status.last_error
    assert sender.send_one(meta, session) == "sent"
    assert not meta.exists() and sender.status.sent == 1
    # Same event_id on both attempts -> the API can deduplicate.
    assert session.calls[0]["data"]["event_id"] == session.calls[1]["data"]["event_id"]
    assert session.calls[0]["headers"] == {"X-Camera-Code": "CAM01", "X-Device-Key": "secret-key"}


def test_duplicate_answer_counts_as_delivered(sender):
    sender.enqueue(make_event(), "CAM01")
    meta = next(sender.pending_dir.glob("*.json"))
    assert sender.send_one(meta, FakeSession([FakeResponse(200, {"duplicate": True})])) == "sent"
    assert list(sender.pending_dir.iterdir()) == []


def test_server_error_is_retried_auth_error_pauses(sender):
    sender.enqueue(make_event(), "CAM01")
    meta = next(sender.pending_dir.glob("*.json"))
    assert sender.send_one(meta, FakeSession([FakeResponse(503, {"error": {"message": "db down"}})])) == "retry"
    assert sender.send_one(meta, FakeSession([FakeResponse(403, {"error": {"message": "Camera đã ngừng"}})])) == "auth"
    assert meta.exists() and "Camera đã ngừng" in sender.status.paused_reason


def test_rejected_event_moves_to_failed_with_reason(sender):
    sender.enqueue(make_event(), "CAM01")
    meta = next(sender.pending_dir.glob("*.json"))
    response = FakeResponse(422, {"error": {"message": "occurred_at cũ hơn 7 ngày"}})
    assert sender.send_one(meta, FakeSession([response])) == "failed"
    failed = json.loads((sender.failed_dir / meta.name).read_text(encoding="utf-8"))
    assert "422" in failed["failure_reason"]
    assert (sender.failed_dir / meta.with_suffix(".jpg").name).exists()


def test_event_for_other_camera_is_not_sent(sender):
    sender.enqueue(make_event(), "CAM_OLD")
    meta = next(sender.pending_dir.glob("*.json"))
    session = FakeSession([])
    assert sender.send_one(meta, session) == "failed"
    assert session.calls == []
