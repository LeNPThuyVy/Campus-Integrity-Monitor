"""
One command for the whole evaluation (from Final_System):

    python -m evaluation.run_all

1st run : runs the pipeline on every video in evaluation/videos/, writes the speed report and
          creates labels/labels.csv for you to fill in.
2nd run : reuses the cached runs and writes the full report to outputs/report/report.md.

Options
    --rerun                 run the models again even for videos that already have results
    --no-realtime           skip the live-camera simulation (it takes as long as the videos)
    --ablation-brightness   also run with brightness normalization off (one more full pass)
    --detector-map          compute detector mAP on datasets/detector (after fixing its labels)
    --config ai             use ai/config.py instead of desktop/config.json
"""
import argparse
from collections import defaultdict
from datetime import datetime

import ai.config as my_config
from evaluation import eval_models, eval_pipeline, eval_speed
from evaluation.common import (BASE, LABELS_CSV, NO_BRIGHTNESS, REALTIME, REPORT_DIR, VIDEOS_DIR, VOTING_KEYS, fmt,
                               list_videos, load_labels, md_table, read_csv, read_json, write_json)
from evaluation.make_labels import make_labels
from evaluation.run_pipeline import run_all_videos

CONFIG_ROWS = [
    ("DETECT_CONF", "Ngưỡng tin cậy phát hiện người"), ("IOU_THRESHOLD", "Ngưỡng IoU (NMS/tracking)"),
    ("MIN_PERSON_HEIGHT_RATIO", "Chiều cao người tối thiểu / chiều cao frame"),
    ("CLASSIFY_CONF", "Ngưỡng tin cậy để giữ nhãn Uniform"), ("DETECT_CARD_CONF", "Ngưỡng tin cậy box thẻ"),
    ("DETECT_CARD_IMAGE_SIZE", "Kích thước ảnh vào card detector (px)"),
    ("FRAME_SKIP", "AI xử lý 1 trên N frame"), ("LEN_HISTORY", "Cửa sổ voting (số kết quả)"),
    ("VOTING_THREDSHOLD", "Số kết quả tối thiểu để kết luận"), ("VIOLATION_RATIO", "Tỉ lệ phiếu để kết luận vi phạm"),
    ("HYSTERESIS_MARGIN", "Biên trễ khi đổi nhãn"), ("LABEL_HOLD_SECONDS", "Thời gian giữ nhãn tối thiểu (s)"),
    ("MISSING_COUNTER_THRESHOLD", "Số frame mất dấu trước khi xoá track"),
    ("EVENT_DEDUP_SECONDS", "Chống báo trùng một track (s)"), ("EVENT_MIN_TRACK_SECONDS", "Quan sát tối thiểu trước khi báo (s)"), ("BRIGHTNESS_ENABLED", "Chuẩn hoá độ sáng"),
]


def dataset_section(labels: dict) -> str:
    rows = read_csv(LABELS_CSV)
    tracks = defaultdict(int)
    for row in rows:
        tracks[row["video"]] += 1
    scenarios = defaultdict(lambda: {"videos": 0, "duration": 0.0, "tracks": 0, "persons": set(), "violations": 0})
    for video in list_videos():
        meta = read_json(video.run_dir(BASE) / "meta.json")
        if meta is None:
            continue
        video_labels = labels.get(video.key, {})
        scenario = next(iter(video_labels.values()))["scenario"] if video_labels else video.scenario
        s = scenarios[scenario]
        s["videos"] += 1
        s["duration"] += meta["duration_s"]
        s["tracks"] += tracks.get(video.key, 0)
        for label in labels.get(video.key, {}).values():
            if label["person"] not in s["persons"] and not (label["uniform"] and label["card"]):
                s["violations"] += 1
            s["persons"].add(label["person"])
    table = [[name, s["videos"], fmt(s["duration"] / 60, 1), s["tracks"], len(s["persons"]), s["violations"]]
             for name, s in sorted(scenarios.items())]
    if len(table) > 1:
        table.append(["**Tổng**", sum(r[1] for r in table), fmt(sum(s["duration"] for s in scenarios.values()) / 60, 1),
                      sum(r[3] for r in table), sum(r[4] for r in table), sum(r[5] for r in table)])
    return ("## 2. Dữ liệu thực nghiệm\n\n"
            + md_table(["Kịch bản", "Số video", "Thời lượng (phút)", "Số track", "Số người đã gán nhãn",
                        "Số người vi phạm"], table) + "\n")


def config_section() -> str:
    config = {}
    for video in list_videos():
        meta = read_json(video.run_dir(BASE) / "meta.json")
        if meta:
            config = meta.get("config", {})
            break
    # Voting/event values are applied offline with the current app config (see eval_pipeline.current_settings).
    config.update({k: getattr(my_config, k) for k in VOTING_KEYS if hasattr(my_config, k)})
    rows = [["Model phân loại đồng phục", "Checkpoint đang dùng", my_config.CLASSIFY_UNIFORM_PATH.name]]
    rows += [[f"`{k}`", d, config.get(k)] for k, d in CONFIG_ROWS]
    return ("## 1. Cấu hình pipeline khi thực nghiệm\n\n"
            + md_table(["Tham số", "Ý nghĩa", "Giá trị"], rows) + "\n")


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--rerun", action="store_true")
    parser.add_argument("--no-realtime", action="store_true")
    parser.add_argument("--ablation-brightness", action="store_true")
    parser.add_argument("--detector-map", action="store_true")
    parser.add_argument("--config", default="desktop", choices=["desktop", "ai"])
    args = parser.parse_args()

    videos = list_videos()
    if not videos:
        VIDEOS_DIR.mkdir(parents=True, exist_ok=True)
        print(f"Chưa có video. Hãy chép video vào {VIDEOS_DIR}\\<kich_ban>\\ (ví dụ sang\\, toi\\) rồi chạy lại.")
        return
    print(f"[1/4] Chạy pipeline trên {len(videos)} video")
    variants = [BASE] + ([] if args.no_realtime else [REALTIME]) + ([NO_BRIGHTNESS] if args.ablation_brightness else [])
    run_all_videos(variants, args.rerun, args.config)

    print("[2/4] Cập nhật file nhãn")
    summary = make_labels()
    labels, _ = load_labels()

    print("[3/4] Đánh giá tốc độ")
    REPORT_DIR.mkdir(parents=True, exist_ok=True)
    speed_metrics, speed_md = eval_speed.evaluate()

    sections = [
        f"# Báo cáo đánh giá thực nghiệm\n\nTạo lúc {datetime.now():%d/%m/%Y %H:%M}. "
        "Số liệu sinh tự động bởi `python -m evaluation.run_all`.\n",
        config_section(),
        dataset_section(labels),
    ]
    metrics = {"speed": speed_metrics}
    if labels:
        print("[4/4] Đánh giá từng model và toàn pipeline")
        metrics["models"], models_md = eval_models.evaluate(args.detector_map)
        metrics["pipeline"], pipeline_md = eval_pipeline.evaluate()
        sections += [models_md, pipeline_md]
    else:
        print("[4/4] Bỏ qua đánh giá độ chính xác: chưa có nhãn")
        sections.append("## 3–5. Độ chính xác\n\nChưa có nhãn trong `labels/labels.csv`.\n")
    sections.append(speed_md)
    report = REPORT_DIR / "report.md"
    report.write_text("\n".join(sections), encoding="utf-8")
    write_json(REPORT_DIR / "metrics.json", metrics)

    print(f"\nBáo cáo: {report}")
    if summary["unlabeled"]:
        print(f"\nCòn {summary['unlabeled']}/{summary['tracks']} track chưa gán nhãn trong {LABELS_CSV}.\n"
              "Mở ảnh trong labels\\tracks\\, điền cột dong_phuc / the (1 = có, 0 = không), sửa person_id nếu "
              "2 track là cùng một người, bo_qua = 1 nếu không phải người.\n"
              "Điền so_nguoi_thuc_te trong labels\\persons_count.csv, rồi chạy lại lệnh này.")


if __name__ == "__main__":
    main()
