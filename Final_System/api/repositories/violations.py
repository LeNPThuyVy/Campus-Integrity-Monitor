"""
AI violation records (PHAT_HIEN_VIPHAM & ANH_VIPHAM).

Records never carry student identity. Desktop clients submit an `event_id` (UUID) that acts as
an idempotency key: resubmitting the same event returns the existing record instead of
creating a duplicate.
"""
from __future__ import annotations

import logging
import uuid
from dataclasses import dataclass
from datetime import date, datetime, time, timedelta
from typing import Any

from api.database import Database, is_unique_violation
from api.errors import ApiError, conflict, not_found, validation_error
from api.evidence import EvidenceStorage

logger = logging.getLogger(__name__)

VIOLATION_CODES = ("UNIFORM", "NO_CARD", "BOTH")
PROCESSING_STATUSES = ("MOI", "DA_XEM", "DA_XU_LY", "BO_QUA")


@dataclass(frozen=True)
class ViolationFilters:
    date_from: date | None = None
    date_to: date | None = None
    area_id: int | None = None
    camera_id: int | None = None
    type_id: int | None = None
    status: str | None = None

    def where_clause(self) -> tuple[str, list[Any]]:
        conditions: list[str] = []
        params: list[Any] = []
        if self.date_from is not None:
            conditions.append("b.THOI_DIEM >= ?")
            params.append(datetime.combine(self.date_from, time.min))
        if self.date_to is not None:
            conditions.append("b.THOI_DIEM < ?")
            params.append(datetime.combine(self.date_to + timedelta(days=1), time.min))
        if self.area_id is not None:
            conditions.append("c.KHU_VUC_ID = ?")
            params.append(self.area_id)
        if self.camera_id is not None:
            conditions.append("a.CAMERA_ID = ?")
            params.append(self.camera_id)
        if self.type_id is not None:
            conditions.append("b.LOI_ID = ?")
            params.append(self.type_id)
        if self.status is not None:
            if self.status not in PROCESSING_STATUSES:
                raise validation_error("Trạng thái xử lý không hợp lệ.")
            conditions.append("b.TINH_TRANG_XU_LY = ?")
            params.append(self.status)
        if self.date_from and self.date_to and self.date_to < self.date_from:
            raise validation_error("Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.")
        return ("WHERE " + " AND ".join(conditions)) if conditions else "", params


_SELECT = (
    "SELECT b.ID, b.MA_SU_KIEN, b.THOI_DIEM, "
    "a.ANH_KHOA, a.THOI_GIAN_LUU, a.THOI_GIAN_HET_HAN, "
    "b.TINH_TRANG_XU_LY, b.XU_LY_LUC, "
    "l.ID AS LOI_ID, l.MA_LOI, l.TEN_LOI, c.ID AS CAMERA_ID, c.MA_CAMERA, c.TEN_CAMERA, "
    "k.ID AS KHU_VUC_ID, k.MA_KHU_VUC, k.TEN_KHU_VUC "
    "FROM dbo.PHAT_HIEN_VIPHAM b "
    "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
    "INNER JOIN dbo.LOI_VIPHAM l ON l.ID = b.LOI_ID "
    "LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID "
    "LEFT JOIN dbo.KHU_VUC k ON k.ID = c.KHU_VUC_ID "
)

_COUNT_FROM = (
    "FROM dbo.PHAT_HIEN_VIPHAM b "
    "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
    "LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID "
)


def evidence_info(row: dict[str, Any], now: datetime) -> dict[str, Any]:
    key = row.get("ANH_KHOA")
    expires_at = row.get("THOI_GIAN_HET_HAN")
    saved_at = row.get("THOI_GIAN_LUU")
    if key:
        if expires_at is not None and expires_at <= now:
            status = "expired"
        else:
            status = "available"
    elif saved_at is not None:
        # Retention only clears keys after expiry, so a cleared key that is not yet expired means
        # the file vanished early (mark_evidence_missing).
        status = "missing" if expires_at is not None and expires_at > now else "deleted"
    else:
        status = "none"

    messages = {
        "available": None,
        "expired": "Ảnh bằng chứng đã hết hạn lưu trữ và đang chờ hệ thống xóa.",
        "deleted": "Ảnh bằng chứng đã được xóa tự động sau thời hạn lưu trữ.",
        "missing": "Không tìm thấy tệp ảnh bằng chứng trên máy chủ.",
        "none": "Bản ghi không có ảnh bằng chứng.",
    }
    return {
        "status": status,
        "available": status == "available",
        "expires_at": expires_at,
        "deleted_at": None,
        "message": messages.get(status),
        "url": f"/api/v1/violations/{row['ID']}/evidence" if status == "available" else None,
    }


def violation_to_dict(row: dict[str, Any], now: datetime) -> dict[str, Any]:
    return {
        "id": row["ID"],
        "event_id": str(row["MA_SU_KIEN"]).lower(),
        "occurred_at": row["THOI_DIEM"],
        "type": {"id": row["LOI_ID"], "code": row["MA_LOI"], "name": row["TEN_LOI"]},
        "camera": {"id": row.get("CAMERA_ID"), "code": row.get("MA_CAMERA"), "name": row.get("TEN_CAMERA")},
        "area": {"id": row.get("KHU_VUC_ID"), "code": row.get("MA_KHU_VUC"), "name": row.get("TEN_KHU_VUC")},
        "track_id": None,
        "evidence": evidence_info(row, now),
        "processing": {
            "status": row.get("TINH_TRANG_XU_LY") or "MOI",
            "note": None,
            "processed_by": None,
            "processed_at": row.get("XU_LY_LUC"),
        },
    }


def list_violation_types(db: Database, active_only: bool = False) -> list[dict[str, Any]]:
    where = "WHERE DANG_SU_DUNG = 1" if active_only else ""
    rows = db.fetch_all(f"SELECT ID, MA_LOI, TEN_LOI, DIEM_TRU, DANG_SU_DUNG FROM dbo.LOI_VIPHAM {where} ORDER BY ID")
    return [{"id": r["ID"], "code": r["MA_LOI"], "name": r["TEN_LOI"], "reference_points": r["DIEM_TRU"],
             "active": bool(r["DANG_SU_DUNG"])} for r in rows]


def list_violations(db: Database, filters: ViolationFilters, page: int, page_size: int, sort: str,
                    now: datetime) -> dict[str, Any]:
    where, params = filters.where_clause()
    order = "ASC" if sort == "oldest" else "DESC"
    with db.transaction() as cursor:
        cursor.execute(f"SELECT COUNT_BIG(*) {_COUNT_FROM} {where}", *params)
        total = int(cursor.fetchone()[0])
        cursor.execute(
            _SELECT + where + f" ORDER BY b.THOI_DIEM {order}, b.ID {order} OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            *params, (page - 1) * page_size, page_size,
        )
        columns = [column[0] for column in cursor.description]
        rows = [dict(zip(columns, row)) for row in cursor.fetchall()]
    return {
        "items": [violation_to_dict(row, now) for row in rows],
        "page": page,
        "page_size": page_size,
        "total": total,
        "pages": max(1, -(-total // page_size)),
    }


def get_violation(db: Database, violation_id: int, now: datetime) -> dict[str, Any]:
    row = db.fetch_one(_SELECT + "WHERE b.ID = ?", (violation_id,))
    if row is None:
        raise not_found("Không tìm thấy bản ghi vi phạm.")
    return violation_to_dict(row, now)


def update_processing(db: Database, violation_id: int, status: str, note: str | None, actor_id: int,
                      now: datetime) -> dict[str, Any]:
    if status not in PROCESSING_STATUSES:
        raise validation_error("Trạng thái xử lý không hợp lệ.")
    # Migration 007 dropped GHI_CHU_XU_LY/NGUOI_XU_LY_ID: `note` and `actor_id` are accepted for
    # API compatibility but not stored. Back to MOI clears XU_LY_LUC, same as the Laravel web.
    with db.transaction() as cursor:
        cursor.execute(
            "UPDATE dbo.PHAT_HIEN_VIPHAM SET TINH_TRANG_XU_LY = ?, "
            "XU_LY_LUC = ? WHERE ID = ?",
            status, None if status == "MOI" else now, violation_id,
        )
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy bản ghi vi phạm.")
    return get_violation(db, violation_id, now)


@dataclass(frozen=True)
class IncomingViolation:
    event_id: uuid.UUID
    violation_code: str
    occurred_at: datetime  # local naive time
    track_id: int | None = None


@dataclass(frozen=True)
class IngestResult:
    created: bool
    record: dict[str, Any]


def _find_by_event(cursor, event_id: uuid.UUID) -> tuple[int, int] | None:
    cursor.execute(
        "SELECT b.ID, a.CAMERA_ID "
        "FROM dbo.PHAT_HIEN_VIPHAM b "
        "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
        "WHERE b.MA_SU_KIEN = ?", str(event_id)
    )
    row = cursor.fetchone()
    return (row[0], row[1]) if row else None


def find_existing_event(db: Database, event_id: uuid.UUID) -> tuple[int, int] | None:
    with db.transaction() as cursor:
        return _find_by_event(cursor, event_id)


def resolve_violation_type(db: Database, code: str) -> dict[str, Any]:
    row = db.fetch_one("SELECT ID, TEN_LOI, DANG_SU_DUNG FROM dbo.LOI_VIPHAM WHERE MA_LOI = ?", (code,))
    if row is None:
        raise ApiError(409, "violation_type_missing",
                       f"Chưa cấu hình loại vi phạm có mã {code}. Hãy chạy migration 001.")
    if not row["DANG_SU_DUNG"]:
        raise ApiError(409, "violation_type_inactive",
                       f"Loại vi phạm '{row['TEN_LOI']}' đã ngừng sử dụng; bản ghi không được ghi nhận.")
    return row


def ingest_violation(db: Database, storage: EvidenceStorage, camera, incoming: IncomingViolation,
                     jpeg_bytes: bytes, width: int, height: int, now: datetime, retention_days: int) -> IngestResult:
    """Persist a violation record with its evidence. Idempotent on `event_id`."""
    existing = find_existing_event(db, incoming.event_id)
    if existing is not None:
        return duplicate_result(db, existing, camera.id, now)

    violation_type = resolve_violation_type(db, incoming.violation_code)
    stored = storage.save(jpeg_bytes, width, height, incoming.occurred_at)
    expires_at = now + timedelta(days=retention_days)
    try:
        with db.transaction() as cursor:
            cursor.execute(
                "INSERT INTO dbo.PHAT_HIEN_VIPHAM (MA_SU_KIEN, LOI_ID, THOI_DIEM, TINH_TRANG_XU_LY) "
                "OUTPUT INSERTED.ID VALUES (?, ?, ?, 'MOI')",
                str(incoming.event_id), violation_type["ID"], incoming.occurred_at,
            )
            record_id = cursor.fetchone()[0]
            cursor.execute(
                "INSERT INTO dbo.ANH_VIPHAM (PHAT_HIEN_ID, CAMERA_ID, ANH_KHOA, ANH_KICH_THUOC, THOI_GIAN_LUU, THOI_GIAN_HET_HAN) "
                "VALUES (?, ?, ?, ?, ?, ?)",
                record_id, camera.id, stored.key, stored.size, incoming.occurred_at, expires_at,
            )
    except Exception as exc:
        # Never leave an orphan file when the row was not written.
        try:
            storage.delete(stored.key)
        except Exception:  # pragma: no cover - logged only
            logger.exception("Không xóa được ảnh mồ côi sau lỗi ghi database.")
        if is_unique_violation(exc):
            existing = find_existing_event(db, incoming.event_id)
            if existing is not None:
                return duplicate_result(db, existing, camera.id, now)
        raise
    return IngestResult(created=True, record=get_violation(db, record_id, now))


def duplicate_result(db: Database, existing: tuple[int, int], camera_id: int, now: datetime) -> IngestResult:
    record_id, existing_camera = existing
    if existing_camera is not None and existing_camera != camera_id:
        raise conflict("event_id đã được sử dụng bởi một camera khác.")
    return IngestResult(created=False, record=get_violation(db, record_id, now))


def evidence_key_for(db: Database, violation_id: int, now: datetime) -> tuple[str | None, dict[str, Any]]:
    row = db.fetch_one("SELECT a.PHAT_HIEN_ID AS ID, a.ANH_KHOA, a.THOI_GIAN_HET_HAN, a.THOI_GIAN_LUU "
                       "FROM dbo.ANH_VIPHAM a WHERE a.PHAT_HIEN_ID = ?", (violation_id,))
    if row is None:
        if not db.fetch_one("SELECT 1 FROM dbo.PHAT_HIEN_VIPHAM WHERE ID = ?", (violation_id,)):
            raise not_found("Không tìm thấy bản ghi vi phạm.")
        return None, evidence_info({"ANH_KHOA": None, "THOI_GIAN_HET_HAN": None, "THOI_GIAN_LUU": None}, now)
    info = evidence_info(row, now)
    return (row["ANH_KHOA"] if info["available"] else None), info


def mark_evidence_missing(db: Database, violation_id: int) -> None:
    with db.transaction() as cursor:
        cursor.execute("UPDATE dbo.ANH_VIPHAM SET ANH_KHOA = NULL "
                       "WHERE PHAT_HIEN_ID = ?", violation_id)
