"""
End-to-end evaluation of the full pipeline, per person (not per frame).

A person = one or more labeled tracks with the same person_id. Ground truth comes from labels.csv;
the prediction is the first violation event of that person, COMPLIANT when the system reached a
decision without an event, UNDECIDED when it never left "Waiting".

Usage (from Final_System):
    python -m evaluation.eval_pipeline
"""
from collections import defaultdict

import ai.config as my_config
from evaluation import charts
from evaluation.common import (BASE, EVAL_DIR, NO_BRIGHTNESS, REALTIME, REPORT_DIR, VOTING_KEYS, gt_status,
                               list_videos, fmt, load_labels, load_person_counts, md_image, md_table, pct,
                               read_json, write_csv, write_json)
from evaluation.metrics import binary_report, confusion_matrix, summary_stats
from evaluation.replay import FRAME_LEVEL, load_run, replay, replay_mapped

GT_CLASSES = ["COMPLIANT", "UNIFORM", "NO_CARD", "BOTH"]
PRED_CLASSES = GT_CLASSES + ["UNDECIDED"]
VIOLATIONS = {"UNIFORM", "NO_CARD", "BOTH"}
SWEEPS = {
    "CLASSIFY_CONF": [0.4, 0.5, 0.6, 0.7, 0.8, 0.9],
    "DETECT_CARD_CONF": [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7],
    "VIOLATION_RATIO": [0.5, 0.55, 0.6, 0.65, 0.7, 0.8],
    "VOTING_THREDSHOLD": [1, 3, 5, 7, 10, 15],
    "LEN_HISTORY": [12, 30, 60, 100, 300],
    "EVENT_MIN_TRACK_SECONDS": [0, 0.5, 1, 1.5, 2, 3],
}
SWEEP_TITLES = {
    "CLASSIFY_CONF": "Ngưỡng tin cậy đồng phục (CLASSIFY_CONF)",
    "DETECT_CARD_CONF": "Ngưỡng tin cậy thẻ (DETECT_CARD_CONF)",
    "VIOLATION_RATIO": "Tỉ lệ phiếu vi phạm (VIOLATION_RATIO)",
    "VOTING_THREDSHOLD": "Số kết quả tối thiểu để kết luận (VOTING_THREDSHOLD)",
    "LEN_HISTORY": "Độ dài cửa sổ bỏ phiếu (LEN_HISTORY)",
    "EVENT_MIN_TRACK_SECONDS": "Thời gian quan sát tối thiểu trước khi báo (EVENT_MIN_TRACK_SECONDS)",
}
BASELINE_CONFIG = EVAL_DIR / "baseline_config.json"


def current_settings() -> dict:
    """
    Voting/event values the app runs with now (desktop/config.json once applied). They only act after
    the models, so cached runs are replayed with them instead of the values recorded at run time.
    """
    return {key: getattr(my_config, key) for key in VOTING_KEYS if hasattr(my_config, key)}


def baseline_settings() -> dict | None:
    """The configuration before tuning (baseline_config.json), for the before/after comparison."""
    saved = read_json(BASELINE_CONFIG)
    if not saved:
        return None
    return {key: value for key, value in saved.items() if key in VOTING_KEYS}


def person_results(outcomes_by_video: dict, labels: dict) -> list[dict]:
    """Group track outcomes by labeled person and derive the prediction of each person."""
    persons = {}
    for video_key, tracks in labels.items():
        outcomes = outcomes_by_video.get(video_key)
        if outcomes is None:
            continue
        for track_id, label in tracks.items():
            person = persons.setdefault(label["person"], {
                "person": label["person"], "video": video_key, "scenario": label["scenario"],
                "gt": gt_status(label["uniform"], label["card"]),
                "gt_uniform_violation": not label["uniform"], "gt_card_violation": not label["card"],
                "tracks": [], "events": [], "decided": False, "first_t": None,
            })
            person["tracks"].append(track_id)
            outcome = outcomes.get(track_id)
            if outcome is None:
                continue
            person["events"].extend(outcome.events)
            person["decided"] = person["decided"] or outcome.decided
            if person["first_t"] is None or outcome.first_t < person["first_t"]:
                person["first_t"] = outcome.first_t
    results = []
    for person in persons.values():
        person["events"].sort()
        if person["events"]:
            person["pred"] = person["events"][0][1]
            person["delay_s"] = person["events"][0][0] - person["first_t"]
        else:
            person["pred"] = "COMPLIANT" if person["decided"] else "UNDECIDED"
            person["delay_s"] = None
        results.append(person)
    return results


def compute_metrics(persons: list[dict], video_hours: float) -> dict:
    if not persons:
        return {"persons": 0}
    gt = [p["gt"] for p in persons]
    pred = [p["pred"] for p in persons]
    flagged = [p["pred"] in VIOLATIONS for p in persons]
    violation = binary_report([g in VIOLATIONS for g in gt], flagged)
    uniform = binary_report([p["gt_uniform_violation"] for p in persons],
                            [p["pred"] in ("UNIFORM", "BOTH") for p in persons])
    card = binary_report([p["gt_card_violation"] for p in persons],
                         [p["pred"] in ("NO_CARD", "BOTH") for p in persons])
    false_alarms = sum(1 for p in persons if p["gt"] == "COMPLIANT" and p["pred"] in VIOLATIONS)
    with_events = [p for p in persons if p["events"]]
    delays = [p["delay_s"] for p in persons if p["gt"] in VIOLATIONS and p["pred"] in VIOLATIONS]
    return {
        "persons": len(persons),
        "accuracy_4_classes": sum(g == q for g, q in zip(gt, pred)) / len(persons),
        "violation": violation,
        "uniform": uniform,
        "card": card,
        "confusion": confusion_matrix(gt, pred, GT_CLASSES, PRED_CLASSES).tolist(),
        "false_alarms": false_alarms,
        "false_alarms_per_hour": false_alarms / video_hours if video_hours > 0 else None,
        "events": sum(len(p["events"]) for p in persons),
        "duplicate_rate": (sum(1 for p in with_events if len(p["events"]) > 1) / len(with_events)
                           if with_events else 0.0),
        "undecided_rate": sum(1 for p in persons if p["pred"] == "UNDECIDED") / len(persons),
        "delay_s": summary_stats(delays),
    }


def _hours(runs: dict) -> float:
    return sum(run.meta["duration_s"] for run in runs.values()) / 3600


def evaluate_runs(runs: dict, labels: dict, **params) -> tuple[dict, list[dict]]:
    settings = {**current_settings(), **params}
    outcomes = {key: replay(run, **settings) for key, run in runs.items()}
    persons = person_results(outcomes, labels)
    return compute_metrics(persons, _hours(runs)), persons


def scenario_breakdown(persons: list[dict], scenario_hours: dict) -> dict:
    by_scenario = defaultdict(list)
    for p in persons:
        by_scenario[p["scenario"]].append(p)
    return {scenario: compute_metrics(items, scenario_hours.get(scenario, 0.0))
            for scenario, items in sorted(by_scenario.items())}


def metric_row(name: str, m: dict) -> list:
    if not m.get("persons"):
        return [name, 0] + ["-"] * 8
    v = m["violation"]
    return [name, m["persons"], v["precision"], v["recall"], v["f1"], m["accuracy_4_classes"],
            m["uniform"]["f1"], m["card"]["f1"], fmt(m["false_alarms_per_hour"], 1), fmt(m["delay_s"]["mean"], 2)]


METRIC_HEADERS = ["Cấu hình", "Số người", "Precision (VP)", "Recall (VP)", "F1 (VP)", "Accuracy 4 lớp",
                  "F1 đồng phục", "F1 thẻ", "Báo sai / giờ", "Độ trễ TB (s)"]
CAUSE_OK, CAUSE_EARLY, CAUSE_UNDECIDED = "Đúng", "Chốt sớm (logic voting)", "Chưa kết luận"
CAUSE_UNIFORM, CAUSE_CARD = "Model đồng phục", "Model thẻ"
SUSPECT_LABEL = " (nghi nhãn sai, xem lại ảnh)"
SUSPECT_LABEL_SHARE = 0.9   # share of frames contradicting the label


def frame_stats(runs: dict, labels: dict) -> dict:
    """
    Per person, over every frame of every track: share of frames the models judged Uniform / Card,
    with the current thresholds, and the status a whole-visit majority vote gives.
    """
    counts = defaultdict(lambda: {"frames": 0, "uniform": 0, "card": 0})
    uniform_thr = max(0.5, float(my_config.CLASSIFY_CONF))
    card_thr = float(my_config.DETECT_CARD_CONF)
    ratio = float(my_config.VIOLATION_RATIO)
    for key, run in runs.items():
        for rows in run.detections.values():
            for row in rows:
                label = labels.get(key, {}).get(int(row["track_id"]))
                if label is None:
                    continue
                c = counts[label["person"]]
                c["frames"] += 1
                c["uniform"] += float(row["p_uniform"]) >= uniform_thr
                c["card"] += float(row["card_conf"]) >= card_thr
    stats = {}
    for person, c in counts.items():
        uniform_share, card_share = c["uniform"] / c["frames"], c["card"] / c["frames"]
        uniform_ok = int(1 - uniform_share < ratio)   # Non_Uniform needs VIOLATION_RATIO of the votes
        card_ok = int(1 - card_share < ratio)
        stats[person] = {"frames": c["frames"], "uniform_share": uniform_share, "card_share": card_share,
                         "uniform_ok": uniform_ok, "card_ok": card_ok, "majority": gt_status(uniform_ok, card_ok)}
    return stats


def error_cause(person: dict, stat: dict | None) -> str:
    if person["pred"] == person["gt"]:
        return CAUSE_OK
    if person["pred"] == "UNDECIDED" or stat is None:
        return CAUSE_UNDECIDED
    if stat["majority"] == person["gt"]:
        return CAUSE_EARLY
    causes, contradicting = [], []
    if stat["uniform_ok"] == person["gt_uniform_violation"]:
        causes.append(CAUSE_UNIFORM)
        contradicting.append(stat["uniform_share"] if stat["uniform_ok"] else 1 - stat["uniform_share"])
    if stat["card_ok"] == person["gt_card_violation"]:
        causes.append(CAUSE_CARD)
        contradicting.append(stat["card_share"] if stat["card_ok"] else 1 - stat["card_share"])
    cause = " + ".join(causes)
    # The models almost never disagree this strongly by chance: check the label first.
    if min(contradicting) >= SUSPECT_LABEL_SHARE:
        cause += SUSPECT_LABEL
    return cause


def majority_persons(persons: list[dict], stats: dict) -> list[dict]:
    """The same persons judged by a majority vote over their whole visit (upper bound for voting)."""
    result = []
    for p in persons:
        stat = stats.get(p["person"])
        result.append({**p, "pred": stat["majority"] if stat else "UNDECIDED", "events": [], "delay_s": None})
    return result


def evaluate() -> tuple[dict, str]:
    labels, label_counts = load_labels()
    videos = list_videos()
    runs = {v.key: load_run(v.run_dir(BASE)) for v in videos}
    runs = {k: r for k, r in runs.items() if r is not None and k in labels}
    if not runs:
        return {"skipped": "chưa có nhãn"}, ("## 4. Đánh giá toàn pipeline\n\nChưa có track nào được gán nhãn "
                                             "trong `labels/labels.csv`, nên chưa tính được độ chính xác.\n")
    out = REPORT_DIR / "pipeline"
    base_metrics, persons = evaluate_runs(runs, labels)
    confusion_png = charts.confusion(base_metrics["confusion"], GT_CLASSES, PRED_CLASSES,
                                     "Ma trận nhầm lẫn theo người (toàn pipeline)", out / "confusion_persons.png")

    # Per scenario: the scenario of a video is the kich_ban written in its labels
    scenario_hours = defaultdict(float)
    for key, run in runs.items():
        scenario = next(iter(labels[key].values()))["scenario"]
        scenario_hours[scenario] += run.meta["duration_s"] / 3600
    scenario_metrics = scenario_breakdown(persons, scenario_hours)
    scenario_rows = [metric_row(scenario, sm) for scenario, sm in scenario_metrics.items()]

    # Before / after tuning the voting parameters (same cached model outputs)
    tuning, tuning_rows, changed = {}, [], []
    baseline = baseline_settings()
    if baseline:
        before_metrics, before_persons = evaluate_runs(runs, labels, **baseline)
        before_by_scenario = scenario_breakdown(before_persons, scenario_hours)
        tuned = current_settings()
        tuning = {"before": before_metrics, "after": base_metrics, "baseline_config": baseline, "tuned_config": tuned}
        tuning_rows = [metric_row("Tất cả – cấu hình gốc", before_metrics),
                       metric_row("Tất cả – sau tinh chỉnh", base_metrics)]
        for scenario in scenario_metrics:
            tuning_rows.append(metric_row(f"{scenario} – cấu hình gốc", before_by_scenario.get(scenario, {})))
            tuning_rows.append(metric_row(f"{scenario} – sau tinh chỉnh", scenario_metrics[scenario]))
        changed = [[f"`{k}`", v, tuned.get(k)] for k, v in baseline.items() if v != tuned.get(k)]

    # Detection coverage: labeled people found vs. people really there
    real_counts = load_person_counts()
    coverage_rows = []
    detected = label_counts["detected"]
    for key in sorted(runs):
        found = len(detected.get(key, ()))
        real = real_counts.get(key)
        coverage_rows.append([key, found, fmt(real), pct(found / real) if real else "-"])
    total_real = sum(real_counts.get(k, 0) for k in runs if k in real_counts)
    total_found = sum(len(detected.get(k, ())) for k in runs if k in real_counts)
    detection_recall = total_found / total_real if total_real else None

    # Error analysis: was a wrong person caused by a model or by deciding too early?
    stats = frame_stats(runs, labels)
    cause_counts = defaultdict(int)
    error_rows = []
    for p in sorted(persons, key=lambda x: x["person"]):
        stat = stats.get(p["person"])
        cause = error_cause(p, stat)
        p["cause"] = cause
        cause_counts[cause] += 1
        error_rows.append([p["person"], p["gt"], p["pred"], stat["frames"] if stat else 0,
                           pct(stat["uniform_share"]) if stat else "-", pct(stat["card_share"]) if stat else "-",
                           stat["majority"] if stat else "-", cause])

    write_csv(out / "persons.csv", [{
        **{k: p[k] for k in ("scenario", "video", "person", "gt", "pred", "delay_s", "first_t")},
        "tracks": " ".join(map(str, p["tracks"])), "n_events": len(p["events"]), "cause": p["cause"],
        "events": " ".join(f"{t:.1f}s:{c}" for t, c in p["events"]),
    } for p in persons], ["scenario", "video", "person", "gt", "pred", "cause", "n_events", "delay_s",
                          "first_t", "tracks", "events"])
    # Ablations: frame-level decisions, whole-visit majority and variant runs
    variants = {"Pipeline đầy đủ (temporal voting)": base_metrics,
                "Không temporal voting (quyết định từng frame)": evaluate_runs(runs, labels, **FRAME_LEVEL)[0],
                "Đa số cả lượt xuất hiện (giới hạn trên của voting)":
                    compute_metrics(majority_persons(persons, stats), _hours(runs))}
    for variant, title in ((REALTIME, "Mô phỏng realtime (rớt frame)"), (NO_BRIGHTNESS, "Tắt chuẩn hoá độ sáng")):
        outcomes = {}
        for v in videos:
            if v.key not in runs:
                continue
            other = load_run(v.run_dir(variant))
            if other is not None:
                outcomes[v.key] = replay_mapped(runs[v.key], other, **current_settings())
        if outcomes:
            persons_v = person_results(outcomes, {k: labels[k] for k in outcomes})
            hours = sum(runs[k].meta["duration_s"] for k in outcomes) / 3600
            variants[title] = compute_metrics(persons_v, hours)

    # Threshold sweeps (replay only)
    sweep_results, sweep_md = {}, []
    settings = current_settings()
    for param, values in SWEEPS.items():
        if param == "VOTING_THREDSHOLD":
            values = [v for v in values if v <= int(settings["LEN_HISTORY"])]
        if param == "LEN_HISTORY":
            values = [v for v in values if v >= int(settings["VOTING_THREDSHOLD"])]
        current = settings.get(param)
        rows, lines = [], {"Precision": [], "Recall": [], "F1": []}
        for value in values:
            m, _ = evaluate_runs(runs, labels, **{param: value})
            sweep_results.setdefault(param, []).append({"value": value, **m})
            for name, key in (("Precision", "precision"), ("Recall", "recall"), ("F1", "f1")):
                lines[name].append(m["violation"][key])
            rows.append([value, m["violation"]["precision"], m["violation"]["recall"], m["violation"]["f1"],
                         m["uniform"]["f1"], m["card"]["f1"], fmt(m["false_alarms_per_hour"], 1),
                         fmt(m["delay_s"]["mean"], 2), pct(m["undecided_rate"])])
        png = charts.sweep(values, lines, SWEEP_TITLES[param], param, out / f"sweep_{param}.png",
                           current=current)
        sweep_md.append(f"#### {SWEEP_TITLES[param]}\n\nGiá trị đang dùng: **{fmt(current)}**\n\n"
                        + md_table([param, "Precision (VP)", "Recall (VP)", "F1 (VP)", "F1 đồng phục", "F1 thẻ",
                                    "Báo sai / giờ", "Độ trễ TB (s)", "Chưa kết luận"], rows) + "\n\n" + md_image(png, f"Ảnh hưởng của {param}"))

    label_counts = {k: v for k, v in label_counts.items() if k != "detected"}
    metrics = {"labels": label_counts, "error_causes": dict(cause_counts), "overall": base_metrics, "by_scenario": scenario_metrics, "tuning": tuning,
               "detection_recall": detection_recall, "variants": variants, "sweeps": sweep_results}
    write_json(out / "metrics.json", metrics)

    m = base_metrics
    delay = m["delay_s"]
    criteria_rows = [[name, r["precision"], r["recall"], r["f1"], r["support"]] for name, r in
                     (("Có vi phạm (bất kỳ)", m["violation"]), ("Sai đồng phục", m["uniform"]),
                      ("Thiếu thẻ", m["card"]))]
    md = [
        "## 4. Đánh giá toàn pipeline (end-to-end, theo từng người)",
        f"Đơn vị đánh giá là **một lượt người xuất hiện** trước camera (gộp các track cùng `person_id`). "
        f"Số track đã gán nhãn: {label_counts['labeled']}, không phải người (bo_qua=1): "
        f"{label_counts['not_person']}, người không xác định được nhãn (bo_qua=2): {label_counts['unclear']}, "
        f"chưa gán: {label_counts['unlabeled']}. Số người đánh giá: **{m['persons']}**.",
        "### 4.1 Kết quả tổng hợp",
        md_table(["Tiêu chí", "Precision", "Recall", "F1", "Số người vi phạm thực tế"], criteria_rows),
        md_table(["Chỉ số", "Giá trị"], [
            ["Accuracy phân loại 4 trạng thái", pct(m["accuracy_4_classes"])],
            ["Tổng số cảnh báo (event)", m["events"]],
            ["Cảnh báo sai (người đúng tác phong bị báo vi phạm)", m["false_alarms"]],
            ["Cảnh báo sai / giờ video", fmt(m["false_alarms_per_hour"], 2)],
            ["Tỉ lệ báo trùng (1 người > 1 event)", pct(m["duplicate_rate"])],
            ["Tỉ lệ chưa kết luận (UNDECIDED)", pct(m["undecided_rate"])],
            ["Độ trễ phát hiện TB / p50 / p95 (s)",
             f"{fmt(delay['mean'], 2)} / {fmt(delay['p50'], 2)} / {fmt(delay['p95'], 2)}"],
            ["Recall phát hiện người (so với số người thực tế)", pct(detection_recall)],
        ]),
        md_image(confusion_png, "Ma trận nhầm lẫn theo người: hàng = thực tế, cột = hệ thống kết luận"),
        "### 4.2 Theo kịch bản",
        md_table(["Kịch bản"] + METRIC_HEADERS[1:], scenario_rows),
        "### 4.3 Độ phủ phát hiện người theo video",
        md_table(["Video", "Số người hệ thống bắt được", "Số người thực tế", "Recall"], coverage_rows),
        "### 4.4 Phân tích lỗi theo từng người",
        "Mỗi người được đối chiếu thêm với dự đoán thô của model trên **mọi** frame. Nếu đa số frame đã đúng "
        "mà kết luận vẫn sai thì lỗi nằm ở logic chốt kết luận (event được tạo quá sớm và bị khoá loại vi "
        "phạm), không phải ở model. Nếu model nói ngược nhãn ở ≥ 90% số frame thì nhiều khả năng nhãn bị "
        "điền nhầm: hãy mở lại ảnh track để kiểm tra trước khi kết luận model sai.",
        md_table(["Nguyên nhân", "Số người", "Tỉ lệ"],
                 [[cause, n, pct(n / len(persons))] for cause, n in sorted(cause_counts.items(), key=lambda x: -x[1])]),
        md_table(["Người", "Thực tế", "Hệ thống", "Số frame", "% frame Uniform", "% frame có thẻ",
                  "Đa số frame", "Nguyên nhân"], error_rows),
        "## 5. Ablation study",
        *([
            "### 5.0 Tinh chỉnh tham số pipeline (không thay đổi model)",
            "Các tham số bỏ phiếu và tạo cảnh báo được tinh chỉnh trên cùng đầu ra của model (chạy lại "
            "voting offline). Model, ảnh đầu vào và nhãn giữ nguyên.",
            md_table(["Tham số", "Cấu hình gốc", "Sau tinh chỉnh"], changed),
            md_table(METRIC_HEADERS, tuning_rows),
            "*Lưu ý: tham số được chọn trên chính tập thử nghiệm. Mức cải thiện đồng đều giữa các kịch bản "
            "ở bảng trên cho thấy tham số không chỉ khớp riêng một nhóm video.*",
        ] if tuning else []),
        "### 5.1 So sánh các cấu hình",
        md_table(METRIC_HEADERS, [metric_row(name, vm) for name, vm in variants.items()]),
        "### 5.2 Ảnh hưởng của các tham số (chạy lại voting offline trên cùng dữ liệu)",
        *sweep_md,
    ]
    return metrics, "\n\n".join(md) + "\n"


def main():
    from evaluation.run_pipeline import apply_desktop_config
    apply_desktop_config()
    REPORT_DIR.mkdir(parents=True, exist_ok=True)
    _, markdown = evaluate()
    path = REPORT_DIR / "pipeline.md"
    path.write_text(markdown, encoding="utf-8")
    print(f"Đã ghi {path}")


if __name__ == "__main__":
    main()
