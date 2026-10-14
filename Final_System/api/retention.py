"""
Evidence retention: delete evidence images after their expiry date (default 30 days).

Safety rules
- Only rows with ANH_TRANG_THAI = 'AVAILABLE' and ANH_HET_HAN_LUC <= now are processed.
- The storage key must match the server-generated pattern and resolve inside the evidence root;
  otherwise nothing is deleted and the attempt is logged as UNSAFE_PATH.
- A file that is already gone is treated as deleted (ALREADY_MISSING) so the job is re-runnable.
- Violation rows are NEVER deleted: time, area, camera and type stay for statistics.
- Concurrency: an in-process lock plus a SQL Server application lock (sp_getapplock) ensure only
  one purge runs at a time, even across several API processes or a scheduled CLI run.

CLI (e.g. Windows Task Scheduler / cron):  python -m api.retention --once
"""
from __future__ import annotations

import argparse
import logging
import threading
from dataclasses import dataclass, field
from datetime import datetime

from api.database import Database, DatabaseUnavailable
from api.evidence import EvidenceStorage, UnsafeEvidencePath

logger = logging.getLogger("cim.retention")
APP_LOCK_RESOURCE = "cim_evidence_retention"
_process_lock = threading.Lock()


@dataclass
class PurgeReport:
    skipped_locked: bool = False
    deleted: int = 0
    already_missing: int = 0
    unsafe: int = 0
    errors: int = 0
    processed_ids: list[int] = field(default_factory=list)

    def as_dict(self) -> dict:
        return {"skipped_locked": self.skipped_locked, "deleted": self.deleted,
                "already_missing": self.already_missing, "unsafe": self.unsafe, "errors": self.errors}


def _log(cursor, record_id: int, result: str, detail: str | None, now: datetime) -> None:
    cursor.execute("INSERT INTO dbo.NHAT_KY_XOA_ANH (BAN_GHI_ID, KET_QUA, CHI_TIET, THOI_DIEM) VALUES (?, ?, ?, ?)",
                   record_id, result, (detail or "")[:500] or None, now)


def purge_expired_evidence(db: Database, storage: EvidenceStorage, now: datetime, batch_size: int = 500) -> PurgeReport:
    report = PurgeReport()
    if not _process_lock.acquire(blocking=False):
        report.skipped_locked = True
        return report
    try:
        connection = db.connect()
        try:
            connection.autocommit = True
            cursor = connection.cursor()
            cursor.execute("DECLARE @r INT; EXEC @r = sp_getapplock @Resource = ?, @LockMode = 'Exclusive', "
                           "@LockOwner = 'Session', @LockTimeout = 0; SELECT @r;", APP_LOCK_RESOURCE)
            if cursor.fetchone()[0] < 0:
                report.skipped_locked = True
                logger.info("Bỏ qua: một tiến trình khác đang xóa ảnh hết hạn.")
                return report
            try:
                while True:
                    cursor.execute(
                        "SELECT TOP (?) ID, ANH_KHOA FROM dbo.BAN_GHI_VIPHAM "
                        "WHERE ANH_TRANG_THAI = 'AVAILABLE' AND ANH_HET_HAN_LUC <= ? ORDER BY ANH_HET_HAN_LUC, ID",
                        batch_size, now,
                    )
                    rows = cursor.fetchall()
                    if not rows:
                        break
                    processed_before = len(report.processed_ids)
                    for record_id, key in rows:
                        _purge_one(connection, storage, record_id, key, now, report)
                    # Stop when the batch was partial, or when nothing could be processed
                    # (rows that failed stay AVAILABLE and would otherwise be selected forever).
                    if len(rows) < batch_size or len(report.processed_ids) == processed_before:
                        break
            finally:
                cursor.execute("EXEC sp_releaseapplock @Resource = ?, @LockOwner = 'Session';", APP_LOCK_RESOURCE)
        finally:
            connection.close()
    finally:
        _process_lock.release()
    if report.deleted or report.already_missing or report.unsafe or report.errors:
        logger.info("Xóa ảnh hết hạn: %s", report.as_dict())
    return report


def _purge_one(connection, storage: EvidenceStorage, record_id: int, key: str | None, now: datetime,
               report: PurgeReport) -> None:
    cursor = connection.cursor()
    try:
        try:
            removed = storage.delete(key) if key else False
            result = "DELETED" if removed else "ALREADY_MISSING"
            detail = None if removed else "Tệp ảnh không còn trên đĩa trước khi job chạy."
        except UnsafeEvidencePath as exc:
            # Do not touch the file system; mark as deleted so it is no longer served, and log it.
            result, detail = "UNSAFE_PATH", str(exc)
        except OSError as exc:
            report.errors += 1
            logger.error("Không xóa được ảnh của bản ghi %s: %s", record_id, type(exc).__name__)
            connection.autocommit = False
            _log(cursor, record_id, "ERROR", f"Lỗi hệ thống tệp: {type(exc).__name__}", now)
            connection.commit()
            return
        connection.autocommit = False
        cursor.execute("UPDATE dbo.BAN_GHI_VIPHAM SET ANH_TRANG_THAI = 'DELETED', ANH_DA_XOA_LUC = ? "
                       "WHERE ID = ? AND ANH_TRANG_THAI = 'AVAILABLE'", now, record_id)
        _log(cursor, record_id, result, detail, now)
        connection.commit()
        report.processed_ids.append(record_id)
        if result == "DELETED":
            report.deleted += 1
        elif result == "ALREADY_MISSING":
            report.already_missing += 1
        else:
            report.unsafe += 1
    except Exception:
        connection.rollback()
        report.errors += 1
        logger.exception("Lỗi khi cập nhật trạng thái ảnh của bản ghi %s.", record_id)
    finally:
        connection.autocommit = True
        cursor.close()


class RetentionScheduler:
    """Background thread running the purge periodically inside the API process."""

    def __init__(self, db: Database, storage: EvidenceStorage, interval_minutes: int, now_func):
        self._db = db
        self._storage = storage
        self._interval = max(1, interval_minutes) * 60
        self._now = now_func
        self._stop = threading.Event()
        self._thread: threading.Thread | None = None

    def start(self) -> None:
        if self._thread is not None:
            return
        self._thread = threading.Thread(target=self._run, name="evidence-retention", daemon=True)
        self._thread.start()

    def _run(self) -> None:
        while not self._stop.is_set():
            try:
                purge_expired_evidence(self._db, self._storage, self._now())
            except DatabaseUnavailable:
                logger.warning("Job xóa ảnh: chưa kết nối được database, sẽ thử lại ở chu kỳ sau.")
            except Exception:
                logger.exception("Job xóa ảnh gặp lỗi không mong muốn.")
            self._stop.wait(self._interval)

    def stop(self) -> None:
        self._stop.set()
        if self._thread is not None:
            self._thread.join(timeout=5)
            self._thread = None


def main() -> int:
    from api.settings import get_settings

    parser = argparse.ArgumentParser(description="Xóa ảnh bằng chứng đã hết hạn lưu trữ.")
    parser.add_argument("--once", action="store_true", help="Chạy một lần rồi thoát (dùng cho Task Scheduler/cron).")
    parser.parse_args()
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
    settings = get_settings()
    report = purge_expired_evidence(Database(settings.database), EvidenceStorage(settings.evidence_dir),
                                    settings.now_local())
    print(report.as_dict())
    return 1 if report.errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
