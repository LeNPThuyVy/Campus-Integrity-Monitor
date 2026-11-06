import numpy as np
import pytest

import ai.config as my_config
from evaluation.common import gt_status, iou, override_config, to_flag
from evaluation.eval_pipeline import compute_metrics, person_results
from evaluation.metrics import binary_report, classification_report, confusion_matrix, roc_auc
from evaluation.replay import FRAME_LEVEL, CachedRun, TrackOutcome, map_tracks, replay


def row(frame, track_id, uniform="Non_Uniform", conf=0.9, card_conf=0.0, box=(10, 10, 110, 310)):
    return {"frame": str(frame), "t": str(frame / 10), "track_id": str(track_id),
            **{k: str(v) for k, v in zip(("x1", "y1", "x2", "y2"), box)},
            "uniform_label": uniform, "uniform_conf": str(conf), "card_conf": str(card_conf)}


def cached_run(detections: dict, frames: int, **config) -> CachedRun:
    base = {"LEN_HISTORY": 10, "VOTING_THREDSHOLD": 5, "VIOLATION_RATIO": 0.6, "CLASSIFY_CONF": 0.5,
            "DETECT_CARD_CONF": 0.5, "LABEL_HOLD_SECONDS": 0, "HYSTERESIS_MARGIN": 0.0,
            "MISSING_COUNTER_THRESHOLD": 5, "EVENT_DEDUP_SECONDS": 600}
    return CachedRun(meta={"width": 640, "height": 480, "duration_s": frames / 10, "config": {**base, **config}},
                     frame_list=[(i, i / 10) for i in range(frames)], detections=detections)


def test_binary_and_classification_reports():
    report = binary_report([True, True, False, False], [True, False, True, False])
    assert report["precision"] == pytest.approx(0.5) and report["recall"] == pytest.approx(0.5)
    assert report["accuracy"] == pytest.approx(0.5)
    cls = classification_report(["A", "A", "B"], ["A", "B", "B"], ["A", "B"])
    assert cls["accuracy"] == pytest.approx(2 / 3)
    assert cls["per_class"]["B"]["precision"] == pytest.approx(0.5)


def test_confusion_matrix_and_auc():
    matrix = confusion_matrix(["A", "A", "B"], ["A", "C", "B"], ["A", "B"], ["A", "B", "C"])
    assert matrix.tolist() == [[1, 0, 1], [0, 1, 0]]
    assert roc_auc([1, 1, 0, 0], [0.9, 0.8, 0.2, 0.1]) == pytest.approx(1.0)
    assert roc_auc([1, 1], [0.9, 0.8]) is None


def test_label_helpers():
    assert gt_status(1, 1) == "COMPLIANT" and gt_status(0, 1) == "UNIFORM"
    assert gt_status(1, 0) == "NO_CARD" and gt_status(0, 0) == "BOTH"
    assert to_flag("1") == 1 and to_flag("Không") == 0 and to_flag("") is None
    assert iou([0, 0, 10, 10], [0, 0, 10, 10]) == pytest.approx(1.0)
    assert iou([0, 0, 10, 10], [20, 20, 30, 30]) == 0.0


def test_override_config_restores_value():
    before = my_config.VIOLATION_RATIO
    with override_config(VIOLATION_RATIO=0.99):
        assert my_config.VIOLATION_RATIO == 0.99
    assert my_config.VIOLATION_RATIO == before


def test_replay_needs_votes_and_respects_parameters():
    detections = {i: [row(i, 1, card_conf=0.8)] for i in range(10)}
    outcome = replay(cached_run(detections, 10))[1]
    assert outcome.events and outcome.events[0][1] == "UNIFORM"
    assert outcome.events[0][0] == pytest.approx(0.4)  # 5th result reaches VOTING_THREDSHOLD
    # Raising the card threshold above the card confidence turns it into a missing card.
    assert replay(cached_run(detections, 10), DETECT_CARD_CONF=0.9)[1].events[0][1] == "BOTH"
    # Without temporal voting the first frame already decides.
    assert replay(cached_run(detections, 10), **FRAME_LEVEL)[1].events[0][0] == pytest.approx(0.0)


def test_replay_compliant_track_is_decided_without_event():
    detections = {i: [row(i, 1, uniform="Uniform", card_conf=0.9)] for i in range(10)}
    outcome = replay(cached_run(detections, 10))[1]
    assert outcome.decided and not outcome.events


def test_map_tracks_by_overlap():
    base = cached_run({i: [row(i, 1), row(i, 2, box=(300, 10, 400, 310))] for i in range(5)}, 5)
    other = cached_run({i: [row(i, 7, box=(302, 12, 402, 312)), row(i, 8, box=(500, 10, 600, 300))]
                        for i in range(5)}, 5)
    assert map_tracks(base, other) == {7: 2}


def test_person_metrics_merge_tracks_of_the_same_person():
    labels = {"v": {
        1: {"person": "v/1", "uniform": 0, "card": 1, "scenario": "s"},
        2: {"person": "v/1", "uniform": 0, "card": 1, "scenario": "s"},   # same person, 2nd track
        3: {"person": "v/3", "uniform": 1, "card": 1, "scenario": "s"},
        4: {"person": "v/4", "uniform": 1, "card": 0, "scenario": "s"},
    }}
    outcomes = {"v": {
        1: TrackOutcome(1, first_t=1.0, decided=True, events=[(2.0, "UNIFORM")]),
        2: TrackOutcome(2, first_t=5.0, decided=True, events=[(6.0, "UNIFORM")]),
        3: TrackOutcome(3, first_t=0.0, decided=True, events=[(1.0, "NO_CARD")]),   # false alarm
        4: TrackOutcome(4, first_t=0.0, decided=False),                            # never decided
    }}
    persons = {p["person"]: p for p in person_results(outcomes, labels)}
    assert persons["v/1"]["pred"] == "UNIFORM" and persons["v/1"]["delay_s"] == pytest.approx(1.0)
    assert persons["v/4"]["pred"] == "UNDECIDED"
    m = compute_metrics(list(persons.values()), video_hours=1.0)
    assert m["persons"] == 3
    assert m["violation"]["tp"] == 1 and m["violation"]["fp"] == 1 and m["violation"]["fn"] == 1
    assert m["false_alarms_per_hour"] == pytest.approx(1.0)
    assert m["duplicate_rate"] == pytest.approx(0.5)
    assert m["undecided_rate"] == pytest.approx(1 / 3)
    assert np.array(m["confusion"]).sum() == 3


def test_bo_qua_kinds(tmp_path, monkeypatch):
    from evaluation import common
    from evaluation.common import ignore_kind, load_labels, write_csv
    assert ignore_kind("1") == 1 and ignore_kind("2") == 2 and ignore_kind("") == 0 and ignore_kind("0") == 0
    path = tmp_path / "labels.csv"
    fields = ["kich_ban", "video", "track_id", "person_id", "dong_phuc", "the", "bo_qua"]
    write_csv(path, [
        {"kich_ban": "s", "video": "v", "track_id": 1, "person_id": 1, "dong_phuc": 1, "the": 1, "bo_qua": ""},
        {"kich_ban": "s", "video": "v", "track_id": 2, "person_id": 2, "dong_phuc": "", "the": "", "bo_qua": 2},
        {"kich_ban": "s", "video": "v", "track_id": 3, "person_id": 3, "dong_phuc": "", "the": "", "bo_qua": 1},
        {"kich_ban": "s", "video": "v", "track_id": 4, "person_id": 1, "dong_phuc": 1, "the": 1, "bo_qua": ""},
    ], fields)
    monkeypatch.setattr(common, "LABELS_CSV", path)
    labels, counts = load_labels()
    assert set(labels["v"]) == {1, 4}                       # only judged tracks are evaluated
    assert counts["detected"]["v"] == {"v/1", "v/2"}        # unclear person still counts as detected
    assert counts["not_person"] == 1 and counts["unclear"] == 1


def test_error_cause_separates_model_errors_from_early_decisions():
    from evaluation.eval_pipeline import CAUSE_CARD, CAUSE_EARLY, CAUSE_OK, CAUSE_UNIFORM, error_cause
    person = {"gt": "BOTH", "pred": "NO_CARD", "gt_uniform_violation": True, "gt_card_violation": True}
    majority_right = {"uniform_ok": 0, "card_ok": 0, "majority": "BOTH"}
    assert error_cause(person, majority_right) == CAUSE_EARLY
    majority_wrong = {"uniform_ok": 1, "card_ok": 0, "majority": "NO_CARD", "uniform_share": 0.7, "card_share": 0.1}
    assert error_cause(person, majority_wrong) == CAUSE_UNIFORM
    both_wrong = {"uniform_ok": 1, "card_ok": 1, "majority": "COMPLIANT", "uniform_share": 0.7, "card_share": 0.6}
    assert error_cause(person, both_wrong) == f"{CAUSE_UNIFORM} + {CAUSE_CARD}"
    assert error_cause({**person, "pred": "BOTH"}, majority_wrong) == CAUSE_OK


def test_error_cause_flags_probable_label_mistake():
    from evaluation.eval_pipeline import SUSPECT_LABEL, error_cause
    person = {"gt": "BOTH", "pred": "COMPLIANT", "gt_uniform_violation": True, "gt_card_violation": True}
    confident = {"uniform_ok": 1, "card_ok": 1, "majority": "COMPLIANT", "uniform_share": 1.0, "card_share": 0.96}
    assert error_cause(person, confident).endswith(SUSPECT_LABEL)
    unsure = {**confident, "card_share": 0.6}
    assert not error_cause(person, unsure).endswith(SUSPECT_LABEL)
