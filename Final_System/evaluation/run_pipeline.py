"""
Run the real AI pipeline (same classes as desktop/app.py, no UI, no API) on every test video and
cache the raw per-frame results, timings and sample crops under outputs/runs/<video>/<variant>/.

Variants
- base          : every frame the app would process (FRAME_SKIP respected); video time is used as
                  the clock, so voting/hold/dedup behave as in real time on any machine.
- realtime      : simulates a live camera: frames that arrive while the AI is busy are dropped.
- no_brightness : base with brightness normalization off (ablation).

Usage (from Final_System):
    python -m evaluation.run_pipeline [--variant base|realtime|no_brightness] [--rerun]
"""
import argparse
import csv
import json
import shutil
import time
from datetime import datetime, timedelta, timezone

import cv2
import torch

import ai.config as my_config
from ai.brightness import BrightnessNormalizer
from ai.card_detector import CardDetector
from ai.classifier import Classifier, Prediction
from ai.detector import Detector
from ai.my_utils import Utils
from ai.pipeline import Pipeline
from ai.temporal_voting import TemporalVoting
from ai.violation_events import ViolationEventDetector, crop_evidence
from evaluation.common import (BASE, NO_BRIGHTNESS, REALTIME, TRACK_IMAGES_DIR, Video, config_snapshot,
                               hardware_info, list_videos, override_config, sync_device, wrap_timed,
                               write_json)

DESKTOP_CONFIG_KEYS = [
    "DETECT_CONF", "CLASSIFY_CONF", "MIN_PERSON_HEIGHT_RATIO", "LABEL_HOLD_SECONDS", "DETECT_CARD_CONF",
    "DETECT_CARD_IOU", "DETECT_CARD_IMAGE_SIZE", "IOU_THRESHOLD", "FRAME_SKIP", "BRIGHTNESS_ENABLED",
    "LEN_HISTORY", "VOTING_THREDSHOLD", "VIOLATION_RATIO", "MISSING_COUNTER_THRESHOLD", "EVENT_DEDUP_SECONDS",
    "EVENT_MIN_TRACK_SECONDS",
]
# Cards are logged down to this confidence so the card threshold can be swept offline;
# the pipeline decision still uses DETECT_CARD_CONF.
CARD_LOG_CONF = 0.05
CROP_EVERY = 5           # save a crop every N appearances of a track
MAX_CROPS_PER_TRACK = 20
CLOCK_ORIGIN = datetime(2026, 1, 1, tzinfo=timezone.utc)

FRAME_FIELDS = ["frame", "t", "track_id", "x1", "y1", "x2", "y2", "uniform_label", "uniform_conf",
                "p_uniform", "card_conf", "voted_uniform", "voted_card"]
TIMING_FIELDS = ["frame", "t", "n_detected", "n_persons", "ms_brightness", "ms_detect_track", "ms_uniform",
                 "ms_card", "ms_voting_event", "ms_other", "ms_total"]
EVENT_FIELDS = ["frame", "t", "track_id", "violation_code", "uniform_confidence", "card_confidence", "image"]
CROP_FIELDS = ["track_id", "frame", "uniform", "card", "view"]
STAGES = ["ms_brightness", "ms_detect_track", "ms_uniform", "ms_card", "ms_voting_event"]


def apply_desktop_config(source: str = "desktop") -> str:
    """Use the parameters the desktop app really runs with (desktop/config.json), like App.load_parameters."""
    if source != "desktop":
        return "ai/config.py"
    from desktop.app import CONFIG_FILE_PATH, App
    try:
        with open(CONFIG_FILE_PATH, "r", encoding="utf-8") as f:
            saved = json.load(f)
    except (OSError, ValueError):
        return "ai/config.py"
    params = {key: getattr(my_config, key) for key in DESKTOP_CONFIG_KEYS}
    params.update({key: saved[key] for key in DESKTOP_CONFIG_KEYS if key in saved})
    params = App.validate_params({**params, "API_URL": "", "CAMERA_CODE": ""})
    for key in DESKTOP_CONFIG_KEYS:
        # The desktop sliders save values such as 0.6000000000000001.
        value = params[key]
        setattr(my_config, key, round(value, 4) if isinstance(value, float) else value)
    return "desktop/config.json"


class Components:
    """Models are loaded once; per-video state is reset like App.start_camera."""

    def __init__(self):
        self.detector = Detector(model_path=my_config.DETECT_PERSON_PATH, device=my_config.DEVICE,
                                 conf=my_config.DETECT_CONF, iou=my_config.IOU_THRESHOLD)
        self.uniform_classifier = Classifier(model_path=my_config.CLASSIFY_UNIFORM_PATH,
                                             num_class=len(my_config.UNIFORM_LABELS),
                                             labels=my_config.UNIFORM_LABELS)
        self.card_detector = CardDetector(model_path=my_config.DETECT_CARD_PATH, device=my_config.DEVICE,
                                          conf=CARD_LOG_CONF, iou=my_config.DETECT_CARD_IOU,
                                          image_size=my_config.DETECT_CARD_IMAGE_SIZE)
        self.pipeline = Pipeline(detector=self.detector, uniform_classifier=self.uniform_classifier,
                                 card_detector=self.card_detector)
        self.voting = TemporalVoting()
        self.event_detector = ViolationEventDetector()
        self.brightness = BrightnessNormalizer()

        self.stage_ms: dict = {}
        self.n_detected = 0
        wrap_timed(self.brightness, "apply", self.stage_ms, "ms_brightness")
        wrap_timed(self.detector, "track", self.stage_ms, "ms_detect_track")
        wrap_timed(self.uniform_classifier, "classify_batch", self.stage_ms, "ms_uniform")
        wrap_timed(self.card_detector, "detect_batch", self.stage_ms, "ms_card")
        timed_track = self.detector.track

        def counted_track(frame):
            tracks = timed_track(frame)
            self.n_detected = len(tracks)
            return tracks

        self.detector.track = counted_track

    def reset(self):
        self.detector.reset_tracker()
        self.voting.reset(my_config.LEN_HISTORY)
        self.event_detector.reset()
        self.brightness.reset()
        self.event_detector.dedup_seconds = my_config.EVENT_DEDUP_SECONDS


def card_prediction(result) -> tuple[Prediction, float]:
    """Pipeline rule (any card box = Card) applied at DETECT_CARD_CONF on the low-threshold detections."""
    best = max((d.confidence for d in result.card_detections), default=0.0)
    if best >= my_config.DETECT_CARD_CONF:
        return Prediction(label="Card", confidence=best), best
    return Prediction(label="No_Card", confidence=1.0), best


def p_uniform(prediction) -> float:
    return prediction.confidence if prediction.label == "Uniform" else 1.0 - prediction.confidence


def run_video(components: Components, video: Video, variant: str = BASE) -> dict:
    out_dir = video.run_dir(variant)
    out_dir.mkdir(parents=True, exist_ok=True)
    save_crops = variant == BASE
    if save_crops and (out_dir / "meta.json").exists():
        # Track ids may change on a rerun: keep the old run so make_labels can move labels over by IoU.
        for name in ("frames.csv", "timings.csv", "meta.json"):
            (out_dir / name).replace(out_dir / f"prev_{name}")
        shutil.rmtree(out_dir / "crops", ignore_errors=True)
        shutil.rmtree(TRACK_IMAGES_DIR / video.key, ignore_errors=True)
    shutil.rmtree(out_dir / "events", ignore_errors=True)
    (out_dir / "meta.json").unlink(missing_ok=True)
    crop_dirs = {kind: out_dir / "crops" / kind for kind in ("uniform", "card", "view")}
    if save_crops:
        for path in crop_dirs.values():
            path.mkdir(parents=True, exist_ok=True)
    (out_dir / "events").mkdir(exist_ok=True)

    capture = cv2.VideoCapture(str(video.path))
    if not capture.isOpened():
        raise RuntimeError(f"Không mở được video {video.path}")
    fps = capture.get(cv2.CAP_PROP_FPS) or 30.0
    total_frames = int(capture.get(cv2.CAP_PROP_FRAME_COUNT) or 0)
    width, height = int(capture.get(cv2.CAP_PROP_FRAME_WIDTH)), int(capture.get(cv2.CAP_PROP_FRAME_HEIGHT))
    frame_skip = max(1, int(my_config.FRAME_SKIP))

    components.reset()
    seen: dict[int, int] = {}
    saved: dict[int, int] = {}
    processed = dropped = 0
    rss_peak = 0
    try:
        import psutil
        process = psutil.Process()
        process.cpu_percent(None)
    except Exception:
        process = None
    if torch.cuda.is_available():
        torch.cuda.reset_peak_memory_stats()

    files = {name: open(out_dir / f"{name}.csv", "w", encoding="utf-8-sig", newline="")
             for name in ("frames", "timings", "events", "crops")}
    writers = {
        "frames": csv.DictWriter(files["frames"], FRAME_FIELDS),
        "timings": csv.DictWriter(files["timings"], TIMING_FIELDS),
        "events": csv.DictWriter(files["events"], EVENT_FIELDS),
        "crops": csv.DictWriter(files["crops"], CROP_FIELDS),
    }
    for writer in writers.values():
        writer.writeheader()

    wall_start = time.perf_counter()
    frame_index = -1
    try:
        while True:
            if variant == REALTIME:
                # A live camera keeps producing frames while the AI is busy: skip to "now".
                due = int((time.perf_counter() - wall_start) * fps)
                while frame_index + 1 < due:
                    if not capture.grab():
                        break
                    frame_index += 1
                    dropped += 1
            ok, frame = capture.read()
            if not ok or frame is None:
                break
            frame_index += 1
            if frame_index % frame_skip:
                continue

            t = frame_index / fps
            components.stage_ms.clear()
            sync_device()
            start = time.perf_counter()

            ai_frame = components.brightness.apply(frame)
            results = components.pipeline.run(frame=ai_frame)
            card_info = {}
            for result in results:
                result.card_prediction, card_info[result.track_id] = card_prediction(result)
            vote_start = time.perf_counter()
            components.voting.update(results)
            votes = components.voting.vote(now=t)
            events = components.event_detector.process(frame, results, votes, CLOCK_ORIGIN + timedelta(seconds=t))
            components.stage_ms["ms_voting_event"] = (time.perf_counter() - vote_start) * 1000

            sync_device()
            total_ms = (time.perf_counter() - start) * 1000
            processed += 1

            for result in results:
                vote = votes.get(result.track_id)
                writers["frames"].writerow({
                    "frame": frame_index, "t": f"{t:.3f}", "track_id": result.track_id,
                    **{k: f"{v:.1f}" for k, v in zip(("x1", "y1", "x2", "y2"), result.bbox)},
                    "uniform_label": result.uniform_prediction.label,
                    "uniform_conf": f"{result.uniform_prediction.confidence:.4f}",
                    "p_uniform": f"{p_uniform(result.uniform_prediction):.4f}",
                    "card_conf": f"{card_info[result.track_id]:.4f}",
                    "voted_uniform": vote.uniform_label if vote else "",
                    "voted_card": vote.card_label if vote else "",
                })
                if save_crops:
                    save_track_crops(writers["crops"], crop_dirs, frame, ai_frame, result, frame_index, seen, saved)

            for event in events:
                image_name = f"events/f{frame_index}_t{event.track_id}_{event.violation_code}.jpg"
                cv2.imwrite(str(out_dir / image_name), event.crop)
                writers["events"].writerow({
                    "frame": frame_index, "t": f"{t:.3f}", "track_id": event.track_id,
                    "violation_code": event.violation_code,
                    "uniform_confidence": f"{event.uniform_confidence:.4f}" if event.uniform_confidence else "",
                    "card_confidence": f"{event.card_confidence:.4f}" if event.card_confidence else "",
                    "image": image_name,
                })

            stage_sum = sum(components.stage_ms.get(k, 0.0) for k in STAGES)
            writers["timings"].writerow({
                "frame": frame_index, "t": f"{t:.3f}", "n_detected": components.n_detected,
                "n_persons": len(results),
                **{k: f"{components.stage_ms.get(k, 0.0):.2f}" for k in STAGES},
                "ms_other": f"{max(0.0, total_ms - stage_sum):.2f}", "ms_total": f"{total_ms:.2f}",
            })
            if process is not None and processed % 30 == 0:
                rss_peak = max(rss_peak, process.memory_info().rss)
            if processed % 100 == 0:
                print(f"    {video.key} [{variant}] frame {frame_index}/{total_frames}", flush=True)
    finally:
        capture.release()
        for f in files.values():
            f.close()

    wall_seconds = time.perf_counter() - wall_start
    meta = {
        "video": str(video.path), "key": video.key, "scenario": video.scenario, "variant": variant,
        "fps": fps, "frames": frame_index + 1, "width": width, "height": height,
        "duration_s": (frame_index + 1) / fps, "frame_skip": frame_skip,
        "processed_frames": processed, "dropped_frames": dropped, "wall_seconds": wall_seconds,
        "cpu_percent": process.cpu_percent(None) if process is not None else None,
        "rss_peak_mb": round(rss_peak / 1024 ** 2, 1) if rss_peak else None,
        "gpu_peak_mb": round(torch.cuda.max_memory_allocated() / 1024 ** 2, 1) if torch.cuda.is_available() else None,
        "config": config_snapshot(), "hardware": hardware_info(),
    }
    write_json(out_dir / "meta.json", meta)
    return meta


def save_track_crops(writer, crop_dirs, frame, ai_frame, result, frame_index, seen, saved):
    track_id = result.track_id
    count = seen.get(track_id, 0)
    seen[track_id] = count + 1
    if count % CROP_EVERY or saved.get(track_id, 0) >= MAX_CROPS_PER_TRACK:
        return
    crops = {
        "uniform": Utils.crop_uniform_region(frame=ai_frame, bbox=result.bbox),
        "card": Utils.crop_person(frame=ai_frame, bbox=result.bbox),
        "view": crop_evidence(frame, result.bbox),
    }
    if any(c is None or c.size == 0 for c in crops.values()):
        return
    saved[track_id] = saved.get(track_id, 0) + 1
    row = {"track_id": track_id, "frame": frame_index}
    for kind, crop in crops.items():
        name = f"t{track_id}_f{frame_index}.{'jpg' if kind == 'view' else 'png'}"
        cv2.imwrite(str(crop_dirs[kind] / name), crop)
        row[kind] = f"crops/{kind}/{name}"
    writer.writerow(row)


def run_all_videos(variants=(BASE,), rerun=False, config_source="desktop") -> list[dict]:
    videos = list_videos()
    if not videos:
        return []
    applied = apply_desktop_config(config_source)
    print(f"  Cấu hình pipeline: {applied}")
    todo = [(v, variant) for variant in variants for v in videos
            if rerun or not (v.run_dir(variant) / "meta.json").exists()]
    metas = []
    if todo:
        components = Components()
        for video, variant in todo:
            print(f"  Chạy pipeline: {video.key} [{variant}]", flush=True)
            overrides = {"BRIGHTNESS_ENABLED": 0} if variant == NO_BRIGHTNESS else {}
            with override_config(**overrides):
                metas.append(run_video(components, video, variant))
    return metas


def main():
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--variant", nargs="+", default=[BASE], choices=[BASE, REALTIME, NO_BRIGHTNESS])
    parser.add_argument("--rerun", action="store_true", help="chạy lại cả video đã có kết quả")
    parser.add_argument("--config", default="desktop", choices=["desktop", "ai"],
                        help="desktop = desktop/config.json (mặc định), ai = ai/config.py")
    args = parser.parse_args()
    if not list_videos():
        print("Chưa có video trong evaluation/videos/")
        return
    run_all_videos(args.variant, args.rerun, args.config)


if __name__ == "__main__":
    main()
