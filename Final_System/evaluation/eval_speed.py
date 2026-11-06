"""
Real-time performance from the cached runs: FPS, per-stage latency, latency vs. number of people,
and the live-camera simulation (frames dropped while the AI is busy). Needs no labels.

Usage (from Final_System):
    python -m evaluation.eval_speed
"""
from collections import defaultdict

import numpy as np

from evaluation import charts
from evaluation.common import (BASE, REALTIME, REPORT_DIR, fmt, list_videos, md_image, md_table, pct, read_csv,
                               read_json, write_json)
from evaluation.metrics import summary_stats

WARMUP_FRAMES = 10
STAGES = {
    "ms_brightness": "Chuẩn hoá độ sáng",
    "ms_detect_track": "Phát hiện + tracking người",
    "ms_uniform": "Phân loại đồng phục",
    "ms_card": "Phát hiện thẻ",
    "ms_voting_event": "Voting + tạo event",
    "ms_other": "Crop + xử lý khác",
}


def load_timings(run_dir) -> list[dict]:
    rows = read_csv(run_dir / "timings.csv")[WARMUP_FRAMES:]
    return [{k: float(v) for k, v in row.items()} for row in rows]


def evaluate() -> tuple[dict, str]:
    out = REPORT_DIR / "speed"
    videos = list_videos()
    pooled, per_video_rows, realtime_rows, metas = [], [], [], []
    metrics = {"videos": {}}
    for video in videos:
        meta = read_json(video.run_dir(BASE) / "meta.json")
        if meta is None:
            continue
        rows = load_timings(video.run_dir(BASE))
        if not rows:
            continue
        metas.append(meta)
        pooled += rows
        total = np.array([r["ms_total"] for r in rows])
        fps = 1000 / total.mean()
        entry = {"source_fps": meta["fps"], "frame_skip": meta["frame_skip"], "processing_fps": fps,
                 "latency_ms": summary_stats(total), "mean_persons": float(np.mean([r["n_persons"] for r in rows]))}
        per_video_rows.append([video.key, f"{meta['width']}x{meta['height']}", fmt(meta["fps"], 1),
                               fmt(meta["duration_s"], 1), fmt(entry["mean_persons"], 1), fmt(fps, 1),
                               fmt(entry["latency_ms"]["p50"], 1), fmt(entry["latency_ms"]["p95"], 1)])
        rt = read_json(video.run_dir(REALTIME) / "meta.json")
        if rt:
            seen = rt["frames"]
            # Share of the frames the app intends to analyse (1 of every FRAME_SKIP) that were lost.
            drop = max(0.0, 1 - rt["processed_frames"] * rt["frame_skip"] / seen) if seen else None
            effective = rt["processed_frames"] / rt["wall_seconds"] if rt["wall_seconds"] else None
            entry["realtime"] = {"effective_fps": effective, "drop_rate": drop, "processed": rt["processed_frames"],
                                 "frames": seen}
            realtime_rows.append([video.key, fmt(rt["fps"], 1), rt["frame_skip"], fmt(effective, 1),
                                  rt["processed_frames"], seen, pct(drop)])
        metrics["videos"][video.key] = entry

    if not pooled:
        return {"skipped": "chưa có kết quả chạy"}, "## 6. Hiệu năng realtime\n\nChưa có kết quả chạy pipeline.\n"

    total = np.array([r["ms_total"] for r in pooled])
    latency = summary_stats(total)
    fps_values = 1000 / total
    stage_means = {key: float(np.mean([r[key] for r in pooled])) for key in STAGES}
    metrics["overall"] = {
        "frames": len(pooled), "latency_ms": latency, "fps_mean": float(1000 / total.mean()),
        "fps_p5": float(np.percentile(fps_values, 5)), "fps_min": float(fps_values.min()), "stage_ms": stage_means,
    }

    by_persons = defaultdict(list)
    for r in pooled:
        by_persons[min(int(r["n_persons"]), 6)].append(r["ms_total"])
    counts = sorted(by_persons)
    persons_rows = [[f"{n}+" if n == 6 else n, len(by_persons[n]), fmt(float(np.mean(by_persons[n])), 1),
                     fmt(float(np.percentile(by_persons[n], 95)), 1), fmt(1000 / float(np.mean(by_persons[n])), 1)]
                    for n in counts]
    metrics["by_persons"] = {str(n): summary_stats(by_persons[n]) for n in counts}

    stage_png = charts.stacked_bars(["Trung bình mỗi frame"], {STAGES[k]: [v] for k, v in stage_means.items()},
                                    "Thời gian xử lý trung bình theo từng bước", "ms / frame", out / "stages.png")
    persons_png = charts.line(
        counts, [float(np.mean(by_persons[n])) for n in counts], "Latency theo số người trong khung hình",
        "Số người hợp lệ trong frame", "ms / frame", out / "latency_vs_persons.png",
        band=([float(np.percentile(by_persons[n], 5)) for n in counts],
              [float(np.percentile(by_persons[n], 95)) for n in counts]))
    hist_png = charts.histogram(total, "Phân bố latency mỗi frame", "ms / frame", out / "latency_hist.png")

    hw = metas[0]["hardware"]
    cpu = [m["cpu_percent"] for m in metas if m.get("cpu_percent") is not None]
    rss = [m["rss_peak_mb"] for m in metas if m.get("rss_peak_mb")]
    gpu = [m["gpu_peak_mb"] for m in metas if m.get("gpu_peak_mb")]
    frame_skip = metas[0]["frame_skip"]
    source_fps = float(np.mean([m["fps"] for m in metas]))
    needed = source_fps / frame_skip
    metrics["hardware"] = hw
    md = [
        "## 6. Hiệu năng realtime",
        "### 6.1 Môi trường đo",
        md_table(["Thành phần", "Giá trị"], [
            ["Hệ điều hành", hw.get("os")], ["CPU", f"{hw.get('cpu')} ({hw.get('cpu_cores')} luồng)"],
            ["RAM", f"{hw.get('ram_gb')} GB" if hw.get("ram_gb") else "-"], ["GPU", hw.get("gpu") or "Không (chạy CPU)"],
            ["Thiết bị suy luận", hw.get("device")], ["Python / PyTorch / Ultralytics",
                                                     f"{hw.get('python')} / {hw.get('torch')} / {hw.get('ultralytics')}"],
            ["FRAME_SKIP (AI xử lý 1 trên N frame)", frame_skip],
        ]),
        "### 6.2 Tốc độ xử lý",
        f"Đo trên {len(pooled)} frame (bỏ {WARMUP_FRAMES} frame khởi động mỗi video). Để theo kịp camera "
        f"{source_fps:.0f} FPS với FRAME_SKIP={frame_skip}, AI cần xử lý ≥ **{needed:.1f} frame/giây**.",
        md_table(["Chỉ số", "Giá trị"], [
            ["FPS xử lý trung bình", fmt(metrics["overall"]["fps_mean"], 1)],
            ["FPS thấp (p5) / thấp nhất", f"{fmt(metrics['overall']['fps_p5'], 1)} / {fmt(metrics['overall']['fps_min'], 1)}"],
            ["Latency mỗi frame TB / p50 / p95 (ms)",
             f"{fmt(latency['mean'], 1)} / {fmt(latency['p50'], 1)} / {fmt(latency['p95'], 1)}"],
            ["Đáp ứng realtime", "Có" if metrics["overall"]["fps_mean"] >= needed else "Không"],
            ["CPU tiến trình TB (%)", fmt(float(np.mean(cpu)), 0) if cpu else "-"],
            ["RAM tiến trình cao nhất (MB)", fmt(max(rss), 0) if rss else "-"],
            ["Bộ nhớ GPU cao nhất (MB)", fmt(max(gpu), 0) if gpu else "-"],
        ]),
        md_table(["Bước", "ms / frame (TB)", "Tỉ trọng"],
                 [[STAGES[k], fmt(v, 1), pct(v / latency["mean"])] for k, v in stage_means.items()]),
        md_image(stage_png, "Thời gian trung bình của từng bước trong pipeline"),
        md_table(["Video", "Độ phân giải", "FPS nguồn", "Thời lượng (s)", "Số người TB / frame", "FPS xử lý",
                  "Latency p50 (ms)", "Latency p95 (ms)"], per_video_rows),
        "### 6.3 Ảnh hưởng của số người trong khung hình",
        md_table(["Số người", "Số frame", "Latency TB (ms)", "Latency p95 (ms)", "FPS tương ứng"], persons_rows),
        md_image(persons_png, "Latency trung bình (vùng mờ: p5–p95) theo số người"),
        md_image(hist_png, "Phân bố latency mỗi frame"),
    ]
    if realtime_rows:
        md += ["### 6.4 Mô phỏng camera trực tiếp",
               "Video được phát theo thời gian thực; frame đến trong lúc AI đang bận sẽ bị bỏ, giống camera thật.",
               md_table(["Video", "FPS nguồn", "FRAME_SKIP", "FPS AI thực tế", "Frame được xử lý", "Tổng frame",
                         "Tỉ lệ frame bị bỏ"], realtime_rows)]
    write_json(out / "metrics.json", metrics)
    return metrics, "\n\n".join(md) + "\n"


def main():
    _, markdown = evaluate()
    (REPORT_DIR / "speed.md").write_text(markdown, encoding="utf-8")
    print(f"Đã ghi {REPORT_DIR / 'speed.md'}")


if __name__ == "__main__":
    main()
