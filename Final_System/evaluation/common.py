"""
Shared paths and helpers for the evaluation toolkit (run everything from Final_System).
"""
import csv
import json
import os
import platform
import time
from pathlib import Path

import torch

import ai.config as my_config

EVAL_DIR = Path(__file__).resolve().parent
VIDEOS_DIR = EVAL_DIR / "videos"
LABELS_DIR = EVAL_DIR / "labels"
LABELS_CSV = LABELS_DIR / "labels.csv"
PERSONS_CSV = LABELS_DIR / "persons_count.csv"
TRACK_IMAGES_DIR = LABELS_DIR / "tracks"
OUTPUTS_DIR = EVAL_DIR / "outputs"
RUNS_DIR = OUTPUTS_DIR / "runs"
DATASETS_DIR = OUTPUTS_DIR / "datasets"
REPORT_DIR = OUTPUTS_DIR / "report"

VIDEO_EXTS = {".mp4", ".avi", ".mov", ".mkv", ".m4v", ".wmv"}
DEFAULT_SCENARIO = "chung"

BASE, REALTIME, NO_BRIGHTNESS = "base", "realtime", "no_brightness"

# Voting / event parameters that replay.py may override.
VOTING_KEYS = [
    "CLASSIFY_CONF", "DETECT_CARD_CONF", "LEN_HISTORY", "VOTING_THREDSHOLD", "VIOLATION_RATIO",
    "HYSTERESIS_MARGIN", "LABEL_HOLD_SECONDS", "MISSING_COUNTER_THRESHOLD", "EVENT_DEDUP_SECONDS",
    "EVENT_MIN_TRACK_SECONDS",
]
CONFIG_SNAPSHOT_KEYS = VOTING_KEYS + [
    "DETECT_CONF", "IOU_THRESHOLD", "DETECT_IMAGE_SIZE", "CLASSIFY_IMAGE_SIZE", "MIN_PERSON_HEIGHT_RATIO",
    "DETECT_CARD_IOU", "DETECT_CARD_IMAGE_SIZE", "FRAME_SKIP", "BRIGHTNESS_ENABLED", "DEVICE",
]


class Video:
    """One test video: <videos>/<scenario>/<name>.mp4 (a file directly in videos/ gets scenario 'chung')."""

    def __init__(self, path: Path):
        self.path = path
        relative = path.relative_to(VIDEOS_DIR)
        self.scenario = relative.parts[0] if len(relative.parts) > 1 else DEFAULT_SCENARIO
        self.key = f"{self.scenario}__{path.stem}"

    def run_dir(self, variant: str = BASE) -> Path:
        return RUNS_DIR / self.key / variant


def list_videos() -> list[Video]:
    if not VIDEOS_DIR.exists():
        return []
    paths = sorted(p for p in VIDEOS_DIR.rglob("*") if p.is_file() and p.suffix.lower() in VIDEO_EXTS)
    return [Video(p) for p in paths]


# ----------------------------------------------------------------------
# CSV / JSON
# ----------------------------------------------------------------------

def read_csv(path) -> list[dict]:
    """Read a CSV saved by this toolkit or re-saved by Excel (comma or semicolon, with/without BOM)."""
    path = Path(path)
    if not path.exists():
        return []
    with open(path, "r", encoding="utf-8-sig", newline="") as f:
        text = f.read()
    if not text.strip():
        return []
    header = text.splitlines()[0]
    delimiter = ";" if header.count(";") > header.count(",") else ","
    return [{(k or "").strip(): (v or "").strip() for k, v in row.items()}
            for row in csv.DictReader(text.splitlines(), delimiter=delimiter)]


def write_csv(path, rows: list[dict], fieldnames: list[str]) -> None:
    """utf-8-sig so Excel shows Vietnamese text correctly."""
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    try:
        with open(path, "w", encoding="utf-8-sig", newline="") as f:
            writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore")
            writer.writeheader()
            writer.writerows(rows)
    except PermissionError:
        raise SystemExit(f"\nKhông ghi được {path}\nFile đang được mở (thường là trong Excel). Hãy đóng file rồi chạy lại.")


def read_json(path, default=None):
    path = Path(path)
    if not path.exists():
        return default
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def write_json(path, data) -> None:
    path = Path(path)
    path.parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False, default=str)


def to_float(value, default=None):
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def to_flag(value):
    """'1'/'0' (also 'x', 'co', 'khong'...) -> 1/0; empty or unknown -> None."""
    text = str(value or "").strip().lower()
    if text in {"1", "x", "y", "yes", "co", "có", "true"}:
        return 1
    if text in {"0", "n", "no", "khong", "không", "false"}:
        return 0
    return None


# ----------------------------------------------------------------------
# Config / hardware
# ----------------------------------------------------------------------

class override_config:
    """Temporarily set ai.config values: `with override_config(VIOLATION_RATIO=0.7): ...`"""

    def __init__(self, **values):
        self.values = values
        self.saved = {}

    def __enter__(self):
        for key, value in self.values.items():
            self.saved[key] = getattr(my_config, key)
            setattr(my_config, key, value)
        return self

    def __exit__(self, *exc):
        for key, value in self.saved.items():
            setattr(my_config, key, value)


def config_snapshot() -> dict:
    return {key: getattr(my_config, key, None) for key in CONFIG_SNAPSHOT_KEYS}


def hardware_info() -> dict:
    info = {
        "os": f"{platform.system()} {platform.release()}",
        "cpu": platform.processor() or platform.machine(),
        "cpu_cores": os.cpu_count(),
        "python": platform.python_version(),
        "torch": torch.__version__,
        "device": my_config.DEVICE,
        "gpu": torch.cuda.get_device_name(0) if torch.cuda.is_available() else None,
    }
    try:
        import ultralytics
        info["ultralytics"] = ultralytics.__version__
    except Exception:
        pass
    try:
        import psutil
        info["ram_gb"] = round(psutil.virtual_memory().total / 1024 ** 3, 1)
    except Exception:
        pass
    return info


# ----------------------------------------------------------------------
# Timing
# ----------------------------------------------------------------------

def sync_device() -> None:
    if torch.cuda.is_available():
        torch.cuda.synchronize()


def wrap_timed(obj, method_name: str, timings: dict, key: str) -> None:
    """Replace obj.method_name with a wrapper that adds its run time (ms) to timings[key]."""
    original = getattr(obj, method_name)

    def timed(*args, **kwargs):
        sync_device()
        start = time.perf_counter()
        result = original(*args, **kwargs)
        sync_device()
        timings[key] = timings.get(key, 0.0) + (time.perf_counter() - start) * 1000
        return result

    setattr(obj, method_name, timed)


def iou(a, b) -> float:
    x1, y1 = max(a[0], b[0]), max(a[1], b[1])
    x2, y2 = min(a[2], b[2]), min(a[3], b[3])
    inter = max(0.0, x2 - x1) * max(0.0, y2 - y1)
    union = (a[2] - a[0]) * (a[3] - a[1]) + (b[2] - b[0]) * (b[3] - b[1]) - inter
    return inter / union if union > 0 else 0.0


# ----------------------------------------------------------------------
# Ground truth
# ----------------------------------------------------------------------

def gt_status(uniform_ok: int, card_ok: int) -> str:
    if not uniform_ok and not card_ok:
        return "BOTH"
    if not uniform_ok:
        return "UNIFORM"
    if not card_ok:
        return "NO_CARD"
    return "COMPLIANT"


NOT_A_PERSON, UNCLEAR_PERSON = 1, 2


def ignore_kind(value) -> int:
    """bo_qua: 1 = not a person (false detection), 2 = a person whose uniform/card cannot be judged, else 0."""
    text = str(value or "").strip()
    if text == "2":
        return UNCLEAR_PERSON
    return NOT_A_PERSON if to_flag(text) == 1 else 0


def load_labels() -> tuple[dict, dict]:
    """
    Returns (labels, counts):
      labels[video_key][track_id] = {"person": "<video>/<person_id>", "uniform": 1/0, "card": 1/0, "scenario"}
      counts = {"total", "labeled", "not_person", "unclear", "unlabeled", "detected"}
      counts["detected"][video_key] = person ids the detector really found: labeled tracks and
      bo_qua=2 tracks (bo_qua=1 is a false detection and does not count).
    Ignored (bo_qua=1/2) and not yet labeled tracks are left out of labels.
    """
    labels = {}
    counts = {"total": 0, "labeled": 0, "not_person": 0, "unclear": 0, "unlabeled": 0, "detected": {}}
    for row in read_csv(LABELS_CSV):
        counts["total"] += 1
        person = f"{row['video']}/{(row.get('person_id') or row['track_id']).strip()}"
        kind = ignore_kind(row.get("bo_qua"))
        if kind == NOT_A_PERSON:
            counts["not_person"] += 1
            continue
        if kind == UNCLEAR_PERSON:
            counts["unclear"] += 1
            counts["detected"].setdefault(row["video"], set()).add(person)
            continue
        uniform, card = to_flag(row.get("dong_phuc")), to_flag(row.get("the"))
        if uniform is None or card is None:
            counts["unlabeled"] += 1
            continue
        counts["labeled"] += 1
        counts["detected"].setdefault(row["video"], set()).add(person)
        labels.setdefault(row["video"], {})[int(row["track_id"])] = {
            "person": person, "uniform": uniform, "card": card,
            "scenario": row.get("kich_ban") or DEFAULT_SCENARIO,
        }
    return labels, counts


def load_person_counts() -> dict:
    """video_key -> real number of people (only rows where it was filled in)."""
    counts = {}
    for row in read_csv(PERSONS_CSV):
        value = to_float(row.get("so_nguoi_thuc_te"))
        if value is not None:
            counts[row["video"]] = int(value)
    return counts


# ----------------------------------------------------------------------
# Markdown report helpers
# ----------------------------------------------------------------------

def fmt(value, digits: int = 3) -> str:
    if value is None:
        return "-"
    if isinstance(value, float):
        return f"{value:.{digits}f}"
    return str(value)


def pct(value) -> str:
    return "-" if value is None else f"{value * 100:.1f}%"


def md_table(headers: list[str], rows: list[list]) -> str:
    lines = ["| " + " | ".join(headers) + " |", "|" + "|".join("---" for _ in headers) + "|"]
    lines += ["| " + " | ".join(fmt(c) for c in row) + " |" for row in rows]
    return "\n".join(lines)


def md_image(path, caption: str) -> str:
    return f"![{caption}]({Path(path).relative_to(REPORT_DIR).as_posix()})\n\n*{caption}*"
