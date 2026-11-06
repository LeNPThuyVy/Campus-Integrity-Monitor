"""
Evaluate each model on its own, on images cut from the test videos. Each crop inherits the label
of its track (labels.csv), so one round of labeling serves both the model and the pipeline level.

- Uniform classifier: datasets/uniform/{Uniform,Non_Uniform}; compares every checkpoint in models/.
- Card detector     : datasets/card/{Card,No_Card}; image level (any card box = Card).
- Person detector   : recall vs. the real number of people (persons_count.csv). Optional mAP:
      python -m evaluation.eval_models --export-detector-frames 2   # 1 frame every 2 s, pre-labeled
      (fix the boxes in datasets/detector/ with CVAT / makesense.ai / Label Studio, YOLO format)
      python -m evaluation.run_all --detector-map

Usage (from Final_System):
    python -m evaluation.eval_models
"""
import argparse
import shutil

import cv2
import numpy as np

import ai.config as my_config
from ai.card_detector import CardDetector
from ai.classifier import Classifier
from evaluation import charts
from evaluation.common import (BASE, DATASETS_DIR, REPORT_DIR, fmt, list_videos, load_labels, load_person_counts,
                               md_image, md_table, pct, read_csv, write_json)
from evaluation.metrics import (average_precision, classification_report, confusion_matrix, pr_curve,
                                roc_auc, roc_curve)
from evaluation.run_pipeline import apply_desktop_config
from train.eval_uniform import load_items, predict

UNIFORM_CLASSES = ["Non_Uniform", "Uniform"]
CARD_CLASSES = ["No_Card", "Card"]
DETECTOR_DIR = DATASETS_DIR / "detector"


def export_datasets(labels: dict) -> dict:
    """Copy the sampled crops of labeled tracks into ImageFolder datasets (rebuilt every time)."""
    counts = {}
    for name, classes in (("uniform", UNIFORM_CLASSES), ("card", CARD_CLASSES)):
        shutil.rmtree(DATASETS_DIR / name, ignore_errors=True)
        for cls in classes:
            (DATASETS_DIR / name / cls).mkdir(parents=True, exist_ok=True)
    for video in list_videos():
        tracks = labels.get(video.key)
        if not tracks:
            continue
        run_dir = video.run_dir(BASE)
        for row in read_csv(run_dir / "crops.csv"):
            label = tracks.get(int(row["track_id"]))
            if label is None:
                continue
            stem = f"{video.key}__t{row['track_id']}_f{row['frame']}.png"
            for name, cls in (("uniform", UNIFORM_CLASSES[label["uniform"]]), ("card", CARD_CLASSES[label["card"]])):
                source = run_dir / row[name]
                if source.exists():
                    shutil.copy2(source, DATASETS_DIR / name / cls / stem)
                    counts[(name, cls)] = counts.get((name, cls), 0) + 1
    return counts


def uniform_checkpoints() -> list:
    current = my_config.CLASSIFY_UNIFORM_PATH
    others = sorted(p for p in current.parent.glob("kd_student*.pt") if p != current)
    return [current] + others


def evaluate_uniform(threshold: float) -> tuple[dict, list[str]]:
    items = load_items(str(DATASETS_DIR / "uniform"))
    if not items or len({label for _, label in items}) < 2:
        return {"skipped": "cần ảnh của cả 2 lớp"}, ["Chưa đủ ảnh của cả hai lớp Uniform / Non_Uniform."]
    out = REPORT_DIR / "models"
    y_index = np.array([label for _, label in items])
    y_true = [UNIFORM_CLASSES[i] for i in y_index]
    is_violation = y_index == 0
    results, rows, roc_series, pr_series = {}, [], [], []
    current_probs = None
    for path in uniform_checkpoints():
        try:
            classifier = Classifier(path, num_class=2, labels=UNIFORM_CLASSES)
        except Exception as e:
            results[path.name] = {"error": str(e)}
            continue
        p_uniform = predict(classifier, items)[:, 1]
        if current_probs is None:
            current_probs = p_uniform
        argmax = classification_report(y_true, [UNIFORM_CLASSES[int(p >= 0.5)] for p in p_uniform], UNIFORM_CLASSES)
        rule = classification_report(y_true, [UNIFORM_CLASSES[int(p >= max(0.5, threshold))] for p in p_uniform],
                                     UNIFORM_CLASSES)
        auc = roc_auc(is_violation, 1 - p_uniform)
        ap = average_precision(is_violation, 1 - p_uniform)
        results[path.name] = {"argmax": argmax, "pipeline_rule": rule, "roc_auc": auc, "ap_non_uniform": ap}
        nu = rule["per_class"]["Non_Uniform"]
        rows.append([path.name + (" (đang dùng)" if path == my_config.CLASSIFY_UNIFORM_PATH else ""),
                     argmax["accuracy"], rule["accuracy"], nu["precision"], nu["recall"], nu["f1"],
                     rule["macro_f1"], fmt(auc), fmt(ap)])
        fpr, tpr, _ = roc_curve(is_violation, 1 - p_uniform)
        recall, precision = pr_curve(is_violation, 1 - p_uniform)
        roc_series.append((path.stem, fpr, tpr))
        pr_series.append((path.stem, recall, precision))

    md = [md_table(["Checkpoint", "Accuracy (argmax)", f"Accuracy (ngưỡng {threshold})", "Precision NU",
                    "Recall NU", "F1 NU", "Macro F1", "ROC-AUC", "AP (NU)"], rows)]
    if current_probs is None:
        return results, md
    current = results[my_config.CLASSIFY_UNIFORM_PATH.name]["pipeline_rule"]
    per_class_rows = [[cls, current["per_class"][cls]["precision"], current["per_class"][cls]["recall"],
                       current["per_class"][cls]["f1"], current["per_class"][cls]["support"]]
                      for cls in UNIFORM_CLASSES]
    matrix = confusion_matrix(y_true, [UNIFORM_CLASSES[int(p >= max(0.5, threshold))] for p in current_probs],
                              UNIFORM_CLASSES)
    confusion_png = charts.confusion(matrix, UNIFORM_CLASSES, UNIFORM_CLASSES,
                                     "Classifier đồng phục (model đang dùng)", out / "uniform_confusion.png")
    roc_png = charts.curves(roc_series, "ROC – phát hiện sai đồng phục", "False positive rate",
                            "True positive rate", out / "uniform_roc.png", diagonal=True)
    pr_png = charts.curves(pr_series, "Precision–Recall – lớp Non_Uniform", "Recall", "Precision",
                           out / "uniform_pr.png")
    thresholds = [round(x, 2) for x in np.arange(0.5, 0.96, 0.05)]
    sweep_rows, lines = [], {"Precision NU": [], "Recall NU": [], "F1 NU": [], "Accuracy": []}
    for thr in thresholds:
        r = classification_report(y_true, [UNIFORM_CLASSES[int(p >= thr)] for p in current_probs], UNIFORM_CLASSES)
        nu = r["per_class"]["Non_Uniform"]
        for key, value in (("Precision NU", nu["precision"]), ("Recall NU", nu["recall"]), ("F1 NU", nu["f1"]),
                           ("Accuracy", r["accuracy"])):
            lines[key].append(value)
        sweep_rows.append([thr, r["accuracy"], nu["precision"], nu["recall"], nu["f1"], r["macro_f1"]])
    sweep_png = charts.sweep(thresholds, lines, "Classifier đồng phục theo ngưỡng CLASSIFY_CONF", "CLASSIFY_CONF",
                             out / "uniform_threshold.png", current=threshold)
    results["threshold_sweep"] = [dict(zip(["threshold", "accuracy", "precision_nu", "recall_nu", "f1_nu",
                                            "macro_f1"], r)) for r in sweep_rows]
    md += [
        f"Kết quả chi tiết của model đang dùng (`{my_config.CLASSIFY_UNIFORM_PATH.name}`) với quy tắc của "
        f"pipeline: Uniform chỉ khi xác suất Uniform ≥ {threshold}:",
        md_table(["Lớp", "Precision", "Recall", "F1", "Số ảnh"], per_class_rows),
        md_image(confusion_png, "Ma trận nhầm lẫn classifier đồng phục"),
        md_image(roc_png, "Đường ROC các checkpoint"),
        md_image(pr_png, "Đường Precision–Recall các checkpoint"),
        md_table(["CLASSIFY_CONF", "Accuracy", "Precision NU", "Recall NU", "F1 NU", "Macro F1"], sweep_rows),
        md_image(sweep_png, "Ảnh hưởng của ngưỡng CLASSIFY_CONF"),
    ]
    return results, md


def evaluate_card(threshold: float) -> tuple[dict, list[str]]:
    items = []
    for index, cls in enumerate(CARD_CLASSES):
        items += [(p, index) for p in sorted((DATASETS_DIR / "card" / cls).glob("*"))]
    if not items or len({label for _, label in items}) < 2:
        return {"skipped": "cần ảnh của cả 2 lớp"}, ["Chưa đủ ảnh của cả hai lớp Card / No_Card."]
    out = REPORT_DIR / "models"
    detector = CardDetector(model_path=my_config.DETECT_CARD_PATH, device=my_config.DEVICE, conf=0.01,
                            iou=my_config.DETECT_CARD_IOU, image_size=my_config.DETECT_CARD_IMAGE_SIZE)
    scores = []
    for start in range(0, len(items), 32):
        images = [cv2.imdecode(np.fromfile(str(p), np.uint8), cv2.IMREAD_COLOR) for p, _ in items[start:start + 32]]
        for dets in detector.detect_batch(images):
            scores.append(max((d.confidence for d in dets), default=0.0))
    scores = np.array(scores)
    y_index = np.array([label for _, label in items])
    y_true = [CARD_CLASSES[i] for i in y_index]
    rule = classification_report(y_true, [CARD_CLASSES[int(s >= threshold)] for s in scores], CARD_CLASSES)
    is_missing = y_index == 0
    auc = roc_auc(is_missing, 1 - scores)
    matrix = confusion_matrix(y_true, [CARD_CLASSES[int(s >= threshold)] for s in scores], CARD_CLASSES)
    confusion_png = charts.confusion(matrix, CARD_CLASSES, CARD_CLASSES, "Card detector (mức ảnh)",
                                     out / "card_confusion.png")
    thresholds = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9]
    sweep_rows, lines = [], {"Precision No_Card": [], "Recall No_Card": [], "F1 No_Card": [], "Accuracy": []}
    for thr in thresholds:
        r = classification_report(y_true, [CARD_CLASSES[int(s >= thr)] for s in scores], CARD_CLASSES)
        nc = r["per_class"]["No_Card"]
        for key, value in (("Precision No_Card", nc["precision"]), ("Recall No_Card", nc["recall"]),
                           ("F1 No_Card", nc["f1"]), ("Accuracy", r["accuracy"])):
            lines[key].append(value)
        sweep_rows.append([thr, r["accuracy"], nc["precision"], nc["recall"], nc["f1"], r["macro_f1"]])
    sweep_png = charts.sweep(thresholds, lines, "Card detector theo ngưỡng DETECT_CARD_CONF", "DETECT_CARD_CONF",
                             out / "card_threshold.png", current=threshold)
    per_class_rows = [[cls, rule["per_class"][cls]["precision"], rule["per_class"][cls]["recall"],
                       rule["per_class"][cls]["f1"], rule["per_class"][cls]["support"]] for cls in CARD_CLASSES]
    results = {"pipeline_rule": rule, "roc_auc_no_card": auc,
               "threshold_sweep": [dict(zip(["threshold", "accuracy", "precision_nc", "recall_nc", "f1_nc",
                                             "macro_f1"], r)) for r in sweep_rows]}
    md = [
        f"Quy tắc của pipeline: có ít nhất một box thẻ với độ tin cậy ≥ {threshold} → Card. "
        f"Accuracy = **{pct(rule['accuracy'])}**, Macro F1 = **{fmt(rule['macro_f1'])}**, "
        f"ROC-AUC (No_Card) = {fmt(auc)}.",
        md_table(["Lớp", "Precision", "Recall", "F1", "Số ảnh"], per_class_rows),
        md_image(confusion_png, "Ma trận nhầm lẫn card detector"),
        md_table(["DETECT_CARD_CONF", "Accuracy", "Precision No_Card", "Recall No_Card", "F1 No_Card", "Macro F1"],
                 sweep_rows),
        md_image(sweep_png, "Ảnh hưởng của ngưỡng DETECT_CARD_CONF"),
        "*Lưu ý: nhãn thẻ được gán theo cả lượt xuất hiện, nên ở vài frame thẻ có thể bị che hoặc quay "
        "ngược dù người đó có đeo thẻ — Recall lớp Card ở mức ảnh vì vậy thường thấp hơn mức người.*",
    ]
    return results, md


def export_detector_frames(every_seconds: float) -> int:
    """Sample frames from each video with detector pre-labels (YOLO format) for manual correction."""
    from ultralytics import YOLO
    if DETECTOR_DIR.exists():
        print(f"{DETECTOR_DIR} đã tồn tại, không ghi đè (xoá thư mục nếu muốn xuất lại).")
        return 0
    model = YOLO(str(my_config.DETECT_PERSON_PATH))
    (DETECTOR_DIR / "images").mkdir(parents=True)
    (DETECTOR_DIR / "labels").mkdir(parents=True)
    count = 0
    for video in list_videos():
        capture = cv2.VideoCapture(str(video.path))
        fps = capture.get(cv2.CAP_PROP_FPS) or 30.0
        step = max(1, int(round(fps * every_seconds)))
        index = 0
        while True:
            ok, frame = capture.read()
            if not ok:
                break
            if index % step == 0:
                name = f"{video.key}_f{index}"
                cv2.imwrite(str(DETECTOR_DIR / "images" / f"{name}.jpg"), frame)
                boxes = model.predict(frame, classes=[0], conf=my_config.DETECT_CONF, verbose=False)[0].boxes
                lines = [f"0 {x:.6f} {y:.6f} {w:.6f} {h:.6f}" for x, y, w, h in boxes.xywhn.tolist()]
                (DETECTOR_DIR / "labels" / f"{name}.txt").write_text("\n".join(lines), encoding="utf-8")
                count += 1
            index += 1
        capture.release()
    names = "\n".join(f"  {i}: {n}" for i, n in model.names.items())
    (DETECTOR_DIR / "data.yaml").write_text(
        f"path: {DETECTOR_DIR.as_posix()}\ntrain: images\nval: images\nnames:\n{names}\n", encoding="utf-8")
    print(f"Đã xuất {count} frame vào {DETECTOR_DIR}. Hãy sửa lại box người (lớp 0) rồi chạy với --detector-map.")
    return count


def evaluate_detector_map() -> dict:
    from ultralytics import YOLO
    data = DETECTOR_DIR / "data.yaml"
    if not data.exists():
        return {"skipped": "chưa có datasets/detector/data.yaml"}
    model = YOLO(str(my_config.DETECT_PERSON_PATH))
    metrics = model.val(data=str(data), classes=[0], imgsz=my_config.DETECT_IMAGE_SIZE, device=my_config.DEVICE,
                        project=str(REPORT_DIR / "models"), name="detector_val", exist_ok=True, verbose=False)
    box = metrics.box
    return {"precision": float(box.mp), "recall": float(box.mr), "map50": float(box.map50), "map50_95": float(box.map)}


def evaluate(detector_map: bool = False) -> tuple[dict, str]:
    labels, label_counts = load_labels()
    # Current app values, like eval_pipeline.current_settings: cached runs keep the thresholds of their run time.
    uniform_threshold = round(float(my_config.CLASSIFY_CONF), 4)
    card_threshold = round(float(my_config.DETECT_CARD_CONF), 4)
    md = ["## 3. Đánh giá từng model"]
    if not labels:
        md.append("Chưa có track nào được gán nhãn, nên chưa tạo được tập ảnh đánh giá từng model.")
        return {"skipped": "chưa có nhãn"}, "\n\n".join(md) + "\n"

    counts = export_datasets(labels)
    md += [
        "Tập ảnh đánh giá được trích từ chính các video thực nghiệm (mỗi track lấy tối đa 20 ảnh, cách nhau "
        "5 lần xuất hiện), nhãn kế thừa từ nhãn của người đó. Ảnh không trùng tập huấn luyện.",
        md_table(["Tập", "Lớp", "Số ảnh"], [[n, c, v] for (n, c), v in sorted(counts.items())]),
    ]
    metrics = {"datasets": {f"{n}/{c}": v for (n, c), v in counts.items()}}

    metrics["uniform"], uniform_md = evaluate_uniform(uniform_threshold)
    md += ["### 3.1 Classifier đồng phục (MobileNetV3, knowledge distillation)", *uniform_md]
    metrics["card"], card_md = evaluate_card(card_threshold)
    md += ["### 3.2 Card detector (YOLO)", *card_md]

    md.append("### 3.3 Person detector (YOLO + BoT-SORT)")
    real = load_person_counts()
    found = sum(len(label_counts["detected"].get(k, ())) for k in real)
    total = sum(real.values())
    metrics["person_recall"] = found / total if total else None
    metrics["false_detections"] = label_counts["not_person"]
    md.append(f"Recall mức người (số người hệ thống bắt được ÷ số người thực tế trong `persons_count.csv`): "
              f"**{pct(metrics['person_recall'])}** ({found}/{total})." if total else
              "Điền `so_nguoi_thuc_te` trong `labels/persons_count.csv` để tính recall của detector.")
    md.append(f"Số track không phải người (phát hiện nhầm, `bo_qua=1`): **{label_counts['not_person']}** "
              f"trên tổng {label_counts['total']} track.")
    if detector_map:
        metrics["detector_map"] = result = evaluate_detector_map()
        if "skipped" not in result:
            md.append(md_table(["Precision", "Recall", "mAP@0.5", "mAP@0.5:0.95"],
                               [[result["precision"], result["recall"], result["map50"], result["map50_95"]]]))
        else:
            md.append(f"mAP: {result['skipped']}.")
    else:
        md.append("mAP của detector (tuỳ chọn): xuất frame bằng `--export-detector-frames`, sửa nhãn rồi chạy "
                  "`run_all --detector-map`.")
    write_json(REPORT_DIR / "models" / "metrics.json", metrics)
    return metrics, "\n\n".join(md) + "\n"


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--export-detector-frames", type=float, metavar="GIAY",
                        help="xuất 1 frame mỗi GIAY giây kèm nhãn gợi ý để tính mAP detector")
    parser.add_argument("--detector-map", action="store_true", help="tính mAP trên datasets/detector đã sửa nhãn")
    args = parser.parse_args()
    if args.export_detector_frames:
        export_detector_frames(args.export_detector_frames)
        return
    apply_desktop_config()
    _, markdown = evaluate(args.detector_map)
    (REPORT_DIR / "models.md").write_text(markdown, encoding="utf-8")
    print(f"Đã ghi {REPORT_DIR / 'models.md'}")


if __name__ == "__main__":
    main()
