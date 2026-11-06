"""
Replay TemporalVoting + ViolationEventDetector offline from a cached run (frames.csv + timings.csv),
optionally with other parameters. The models are not run again, so threshold sweeps are fast.
"""
from collections import defaultdict
from dataclasses import dataclass, field
from datetime import timedelta

import numpy as np

import ai.config as my_config
from ai.classifier import Prediction
from ai.pipeline import PipelineResult
from ai.temporal_voting import TemporalVoting
from ai.violation_events import ViolationEventDetector, combined_status
from evaluation.common import VOTING_KEYS, iou, override_config, read_csv, read_json, to_float
from evaluation.run_pipeline import CLOCK_ORIGIN

# Every frame decides on its own: the "no temporal voting" baseline.
FRAME_LEVEL = {"LEN_HISTORY": 1, "VOTING_THREDSHOLD": 1, "LABEL_HOLD_SECONDS": 0, "HYSTERESIS_MARGIN": 0.0}


@dataclass
class TrackOutcome:
    track_id: int
    first_t: float
    last_t: float = 0.0
    frames: int = 0
    decided: bool = False                       # reached a final (non-Waiting) combined status
    events: list = field(default_factory=list)  # [(t, violation_code)]


@dataclass
class CachedRun:
    meta: dict
    frame_list: list            # [(frame_index, t)] for every processed frame
    detections: dict            # frame_index -> [row dicts]

    @property
    def tracks(self) -> dict:
        """track_id -> {frame_index: bbox}"""
        tracks = defaultdict(dict)
        for frame_index, rows in self.detections.items():
            for row in rows:
                tracks[int(row["track_id"])][frame_index] = [float(row[k]) for k in ("x1", "y1", "x2", "y2")]
        return tracks


def load_run(run_dir, prefix: str = "") -> CachedRun | None:
    """prefix='prev_' loads the run kept from before a --rerun."""
    meta = read_json(run_dir / f"{prefix}meta.json")
    if meta is None:
        return None
    frame_list = [(int(r["frame"]), float(r["t"])) for r in read_csv(run_dir / f"{prefix}timings.csv")]
    detections = defaultdict(list)
    for row in read_csv(run_dir / f"{prefix}frames.csv"):
        detections[int(row["frame"])].append(row)
    return CachedRun(meta=meta, frame_list=frame_list, detections=dict(detections))


def _results(rows) -> list[PipelineResult]:
    results = []
    for row in rows:
        card_conf = to_float(row["card_conf"], 0.0)
        card = (Prediction("Card", card_conf) if card_conf >= my_config.DETECT_CARD_CONF
                else Prediction("No_Card", 1.0))
        results.append(PipelineResult(
            track_id=int(row["track_id"]),
            bbox=[float(row[k]) for k in ("x1", "y1", "x2", "y2")],
            uniform_prediction=Prediction(row["uniform_label"], float(row["uniform_conf"])),
            card_prediction=card,
        ))
    return results


def replay(run: CachedRun, **params) -> dict[int, TrackOutcome]:
    """
    Returns one TrackOutcome per track. Voting/event values default to the ones the run was recorded
    with (meta.json); params override them.
    """
    width, height = int(run.meta.get("width") or 64), int(run.meta.get("height") or 64)
    # crop_evidence only checks the crop is a real image; the pixels are irrelevant offline.
    dummy_frame = np.random.default_rng(0).integers(0, 255, (height, width, 3), dtype=np.uint8)
    outcomes: dict[int, TrackOutcome] = {}
    recorded = run.meta.get("config", {})
    settings = {key: recorded[key] for key in VOTING_KEYS if recorded.get(key) is not None}
    settings.update(params)
    with override_config(**settings):
        voting = TemporalVoting()
        voting.reset(my_config.LEN_HISTORY)
        events = ViolationEventDetector(dedup_seconds=my_config.EVENT_DEDUP_SECONDS)
        for frame_index, t in run.frame_list:
            results = _results(run.detections.get(frame_index, []))
            voting.update(results)
            votes = voting.vote(now=t)
            for result in results:
                outcome = outcomes.setdefault(result.track_id, TrackOutcome(result.track_id, first_t=t))
                outcome.last_t = t
                outcome.frames += 1
                vote = votes.get(result.track_id)
                if vote and combined_status(vote.uniform_label, vote.card_label) is not None:
                    outcome.decided = True
            for event in events.process(dummy_frame, results, votes, CLOCK_ORIGIN + timedelta(seconds=t)):
                outcomes[event.track_id].events.append((t, event.violation_code))
    return outcomes


def map_tracks(base: CachedRun, other: CachedRun, min_iou: float = 0.5) -> dict[int, int]:
    """Match each track of another run to the base track it overlaps most often (IoU > min_iou)."""
    base_by_frame = base.detections
    votes = defaultdict(lambda: defaultdict(int))
    for frame_index, rows in other.detections.items():
        candidates = base_by_frame.get(frame_index)
        if not candidates:
            continue
        for row in rows:
            box = [float(row[k]) for k in ("x1", "y1", "x2", "y2")]
            best, best_iou = None, min_iou
            for cand in candidates:
                value = iou(box, [float(cand[k]) for k in ("x1", "y1", "x2", "y2")])
                if value > best_iou:
                    best, best_iou = int(cand["track_id"]), value
            if best is not None:
                votes[int(row["track_id"])][best] += 1
    return {tid: max(counts, key=counts.get) for tid, counts in votes.items()}


def replay_mapped(base: CachedRun, other: CachedRun, **params) -> dict[int, TrackOutcome]:
    """Replay a variant run and express its outcomes in base track ids (several may share one)."""
    mapping = map_tracks(base, other)
    raw = replay(other, **params)
    merged: dict[int, TrackOutcome] = {}
    for tid, outcome in raw.items():
        base_tid = mapping.get(tid)
        if base_tid is None:
            continue
        target = merged.get(base_tid)
        if target is None:
            merged[base_tid] = TrackOutcome(base_tid, outcome.first_t, outcome.last_t, outcome.frames,
                                            outcome.decided, list(outcome.events))
        else:
            target.first_t = min(target.first_t, outcome.first_t)
            target.last_t = max(target.last_t, outcome.last_t)
            target.frames += outcome.frames
            target.decided = target.decided or outcome.decided
            target.events = sorted(target.events + outcome.events)
    return merged
