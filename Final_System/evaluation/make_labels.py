"""
Create / update labels/labels.csv (one row per tracked person) and a 4-view image per track,
so the ground truth can be filled in quickly. Labels already filled in are never overwritten.

Usage (from Final_System):
    python -m evaluation.make_labels
"""
from collections import defaultdict

import cv2
import numpy as np

from evaluation.common import (BASE, LABELS_CSV, LABELS_DIR, PERSONS_CSV, TRACK_IMAGES_DIR, ignore_kind,
                               list_videos, read_csv, to_flag, write_csv)
from evaluation.replay import load_run, map_tracks

LABEL_FIELDS = ["kich_ban", "video", "track_id", "so_frame", "xuat_hien_tu_s", "den_s", "anh",
                "person_id", "dong_phuc", "the", "bo_qua", "ghi_chu"]
USER_FIELDS = ["person_id", "dong_phuc", "the", "bo_qua", "ghi_chu"]
PERSON_FIELDS = ["kich_ban", "video", "so_nguoi_thuc_te", "ghi_chu"]
VIEWS_PER_TRACK = 4
VIEW_HEIGHT = 300


def track_montage(run_dir, crop_rows, track_id) -> np.ndarray | None:
    rows = sorted(crop_rows, key=lambda r: int(r["frame"]))
    if not rows:
        return None
    picks = [rows[int(i)] for i in np.linspace(0, len(rows) - 1, min(VIEWS_PER_TRACK, len(rows)))]
    tiles = []
    for row in picks:
        image = cv2.imread(str(run_dir / row["view"]))
        if image is None:
            continue
        scale = VIEW_HEIGHT / image.shape[0]
        tiles.append(cv2.resize(image, (max(1, int(image.shape[1] * scale)), VIEW_HEIGHT)))
    if not tiles:
        return None
    gap = np.full((VIEW_HEIGHT, 6, 3), 255, dtype=np.uint8)
    body = tiles[0]
    for tile in tiles[1:]:
        body = np.hstack([body, gap, tile])
    header = np.full((34, body.shape[1], 3), 255, dtype=np.uint8)
    cv2.putText(header, f"track {track_id}", (8, 24), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (20, 20, 20), 2)
    return np.vstack([header, body])


def build_rows_for_video(video) -> list[dict]:
    run_dir = video.run_dir(BASE)
    frames = read_csv(run_dir / "frames.csv")
    crops = defaultdict(list)
    for row in read_csv(run_dir / "crops.csv"):
        crops[int(row["track_id"])].append(row)
    stats = {}
    for row in frames:
        tid, t = int(row["track_id"]), float(row["t"])
        s = stats.setdefault(tid, {"n": 0, "first": t, "last": t})
        s["n"] += 1
        s["last"] = t

    out_dir = TRACK_IMAGES_DIR / video.key
    out_dir.mkdir(parents=True, exist_ok=True)
    rows = []
    for tid in sorted(stats, key=lambda k: (stats[k]["first"], k)):
        image_path = out_dir / f"track_{tid}.jpg"
        if not image_path.exists():
            montage = track_montage(run_dir, crops.get(tid, []), tid)
            if montage is not None:
                cv2.imwrite(str(image_path), montage)
        rows.append({
            "kich_ban": video.scenario, "video": video.key, "track_id": tid, "so_frame": stats[tid]["n"],
            "xuat_hien_tu_s": f"{stats[tid]['first']:.1f}", "den_s": f"{stats[tid]['last']:.1f}",
            "anh": image_path.relative_to(LABELS_DIR).as_posix() if image_path.exists() else "",
            "person_id": tid, "dong_phuc": "", "the": "", "bo_qua": "", "ghi_chu": "",
        })
    return rows


def carry_labels_after_rerun(video, existing: dict) -> int:
    """
    After `--rerun` the tracker may number people differently. Move the labels of the previous run
    onto the new track ids by bbox overlap, then drop the previous run.
    """
    run_dir = video.run_dir(BASE)
    previous = load_run(run_dir, prefix="prev_")
    if previous is None:
        return 0
    current = load_run(run_dir)
    old_rows = {int(tid): existing.pop((video.key, tid))
                for (key, tid) in list(existing) if key == video.key}
    moved = 0
    for new_tid, old_tid in map_tracks(previous, current).items():
        if old_tid in old_rows:
            existing[(video.key, str(new_tid))] = old_rows[old_tid]
            moved += 1
    for name in ("frames.csv", "timings.csv", "meta.json"):
        (run_dir / f"prev_{name}").unlink(missing_ok=True)
    return moved


def make_labels() -> dict:
    existing = {(r["video"], r["track_id"]): r for r in read_csv(LABELS_CSV)}
    rows, new_rows = [], 0
    videos = [v for v in list_videos() if (v.run_dir(BASE) / "meta.json").exists()]
    # kich_ban may be edited by hand (e.g. trong_nha / ngoai_troi); new tracks of that video follow it.
    scenario_of = {video: row["kich_ban"] for (video, _), row in existing.items() if row.get("kich_ban")}
    scenario_of.update({r["video"]: r["kich_ban"] for r in read_csv(PERSONS_CSV) if r.get("kich_ban")})
    for video in videos:
        moved = carry_labels_after_rerun(video, existing)
        if moved:
            print(f"  {video.key}: đã chuyển nhãn của {moved} track sang lần chạy mới (khớp theo IoU).")
        for row in build_rows_for_video(video):
            old = existing.pop((row["video"], str(row["track_id"])), None)
            if old:
                row.update({k: old.get(k, "") for k in USER_FIELDS})
            else:
                new_rows += 1
            row["kich_ban"] = (old or {}).get("kich_ban") or scenario_of.get(video.key) or video.scenario
            rows.append(row)
    # Keep rows of videos that are not in videos/ anymore, so their labels are not lost.
    rows.extend(existing.values())
    write_csv(LABELS_CSV, rows, LABEL_FIELDS)

    persons = {r["video"]: r for r in read_csv(PERSONS_CSV)}
    person_rows = []
    for video in videos:
        old = persons.pop(video.key, {})
        person_rows.append({"kich_ban": scenario_of.get(video.key) or video.scenario, "video": video.key,
                            "so_nguoi_thuc_te": old.get("so_nguoi_thuc_te", ""), "ghi_chu": old.get("ghi_chu", "")})
    person_rows.extend(persons.values())
    write_csv(PERSONS_CSV, person_rows, PERSON_FIELDS)

    labeled = sum(1 for r in rows if ignore_kind(r.get("bo_qua"))
                  or (to_flag(r.get("dong_phuc")) is not None and to_flag(r.get("the")) is not None))
    return {"tracks": len(rows), "new": new_rows, "labeled": labeled, "unlabeled": len(rows) - labeled}


def main():
    summary = make_labels()
    print(f"labels.csv: {summary['tracks']} track, {summary['labeled']} đã gán nhãn, "
          f"{summary['unlabeled']} chưa gán ({summary['new']} mới).")


if __name__ == "__main__":
    main()
