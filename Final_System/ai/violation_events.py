"""
Turn voted per-track results into violation events with an evidence crop.

Rules
- A track produces an event only when BOTH criteria have a final (non-Waiting) label and at
  least one of them is a violation. Combined states map to the violation codes used by the API:
    Non_Uniform + Card    -> UNIFORM   (sai đồng phục)
    Uniform     + No_Card -> NO_CARD   (thiếu thẻ)
    Non_Uniform + No_Card -> BOTH      (vi phạm cả hai)
    Uniform     + Card    -> no event  (đúng tác phong)
- Anti-repeat: a track is reported at most once per EVENT_DEDUP_SECONDS, so one person walking
  past the camera does not create a stream of records.
- Minimum observation: no event before a track has been visible EVENT_MIN_TRACK_SECONDS, so the
  violation type is not locked in from the first few frames.
- Stable status: the combined status must stay the same for EVENT_STABLE_SECONDS and each
  criterion must have at least EVENT_MIN_VOTES samples before the event is sent. An early, still
  noisy decision (e.g. UNIFORM while the card vote is about to become No_Card -> BOTH) would
  otherwise be the only record the API ever receives for that person, because of the anti-repeat
  window. The evidence crop is taken on the frame where the status is confirmed.
- The evidence crop must be a valid, non-trivial image; otherwise no event is created for now
  (the track can still be reported on a later frame).
- No identity: events only contain time, camera, type, confidences and the crop.
"""
from __future__ import annotations

import uuid
from dataclasses import dataclass, field
from datetime import datetime

import numpy as np

import ai.config as my_config

COMPLIANT = "COMPLIANT"
VIOLATION_LABELS = {
    "UNIFORM": "Sai đồng phục",
    "NO_CARD": "Thiếu thẻ sinh viên",
    "BOTH": "Sai đồng phục và thiếu thẻ",
}


def combined_status(uniform_label: str, card_label: str) -> str | None:
    """Return UNIFORM / NO_CARD / BOTH / COMPLIANT, or None while still Waiting."""
    if uniform_label == "Waiting" or card_label == "Waiting":
        return None
    wrong_uniform = uniform_label == "Non_Uniform"
    no_card = card_label == "No_Card"
    if wrong_uniform and no_card:
        return "BOTH"
    if wrong_uniform:
        return "UNIFORM"
    if no_card:
        return "NO_CARD"
    return COMPLIANT


def crop_evidence(frame: np.ndarray, bbox, padding: float | None = None, min_size: int | None = None):
    """Crop the whole person box (with a small margin). Returns None when the crop is not usable."""
    if frame is None or not isinstance(frame, np.ndarray) or frame.ndim != 3 or frame.size == 0:
        return None
    padding = my_config.EVIDENCE_PADDING if padding is None else padding
    min_size = my_config.EVIDENCE_MIN_SIZE if min_size is None else min_size
    image_h, image_w = frame.shape[:2]
    try:
        x1, y1, x2, y2 = (float(v) for v in bbox)
    except (TypeError, ValueError):
        return None
    if not all(np.isfinite([x1, y1, x2, y2])) or x2 <= x1 or y2 <= y1:
        return None
    pad_x, pad_y = (x2 - x1) * padding, (y2 - y1) * padding
    left, top = max(0, int(x1 - pad_x)), max(0, int(y1 - pad_y))
    right, bottom = min(image_w, int(x2 + pad_x)), min(image_h, int(y2 + pad_y))
    if right - left < min_size or bottom - top < min_size:
        return None
    crop = frame[top:bottom, left:right].copy()
    # Reject frames with no information (e.g. a black frame from a disconnected camera).
    if float(crop.std()) < 2.0:
        return None
    return crop


@dataclass
class ViolationEvent:
    event_id: str
    track_id: int
    violation_code: str
    occurred_at: datetime  # timezone-aware
    uniform_confidence: float | None
    card_confidence: float | None
    crop: np.ndarray = field(repr=False)

    @property
    def label(self) -> str:
        return VIOLATION_LABELS[self.violation_code]


class ViolationEventDetector:
    def __init__(self, dedup_seconds: float | None = None, stable_seconds: float | None = None,
                 min_votes: int | None = None):
        self.dedup_seconds = my_config.EVENT_DEDUP_SECONDS if dedup_seconds is None else dedup_seconds
        # None -> read from config on every frame (so runtime/test config changes apply).
        self._stable_seconds = stable_seconds
        self._min_votes = min_votes
        self._reported: dict[int, datetime] = {}
        self._first_seen: dict[int, datetime] = {}
        self._last_seen: dict[int, datetime] = {}
        self._candidate: dict[int, tuple[str, datetime]] = {}  # track_id -> (status, since)

    @property
    def stable_seconds(self) -> float:
        if self._stable_seconds is not None:
            return self._stable_seconds
        return getattr(my_config, "EVENT_STABLE_SECONDS", 0)

    @property
    def min_votes(self) -> int:
        if self._min_votes is not None:
            return self._min_votes
        return getattr(my_config, "EVENT_MIN_VOTES", 0)

    def reset(self):
        self._reported.clear()
        self._first_seen.clear()
        self._last_seen.clear()
        self._candidate.clear()

    def process(self, frame: np.ndarray, pipeline_results, voting_results: dict, now: datetime) -> list[ViolationEvent]:
        events: list[ViolationEvent] = []
        self._forget_old(now)
        min_track_seconds = getattr(my_config, "EVENT_MIN_TRACK_SECONDS", 0)
        stable_seconds = self.stable_seconds
        min_votes = self.min_votes
        for result in pipeline_results:
            self._first_seen.setdefault(result.track_id, now)
            self._last_seen[result.track_id] = now
            vote = voting_results.get(result.track_id)
            if vote is None:
                continue
            status = combined_status(vote.uniform_label, vote.card_label)
            if status is None or status == COMPLIANT:
                self._candidate.pop(result.track_id, None)
                continue
            if result.track_id in self._reported:
                self._reported[result.track_id] = now  # still visible: extend the dedup window
                continue
            candidate = self._candidate.get(result.track_id)
            if candidate is None or candidate[0] != status:
                # New or changed status: restart the stability timer.
                candidate = (status, now)
                self._candidate[result.track_id] = candidate
            if (getattr(vote, "uniform_votes", 0) < min_votes
                    or getattr(vote, "card_votes", 0) < min_votes):
                continue
            if (now - candidate[1]).total_seconds() < stable_seconds:
                continue
            if (now - self._first_seen[result.track_id]).total_seconds() < min_track_seconds:
                continue
            crop = crop_evidence(frame, result.bbox)
            if crop is None:
                continue
            self._reported[result.track_id] = now
            self._candidate.pop(result.track_id, None)
            events.append(ViolationEvent(
                event_id=str(uuid.uuid4()),
                track_id=int(result.track_id),
                violation_code=status,
                occurred_at=now,
                uniform_confidence=vote.uniform_confidence,
                card_confidence=vote.card_confidence,
                crop=crop,
            ))
        return events

    def _forget_old(self, now: datetime):
        expired = [track_id for track_id, seen in self._reported.items()
                   if (now - seen).total_seconds() > self.dedup_seconds]
        for track_id in expired:
            del self._reported[track_id]
        gone = [track_id for track_id, seen in self._last_seen.items()
                if (now - seen).total_seconds() > self.dedup_seconds]
        for track_id in gone:
            del self._last_seen[track_id]
            self._first_seen.pop(track_id, None)
            self._candidate.pop(track_id, None)
