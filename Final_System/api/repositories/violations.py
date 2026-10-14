"""
AI violation records (BAN_GHI_VIPHAM).

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
EVIDENCE_PUBLIC_STATUS = {"AVAILABLE": "available", "DELETED": "deleted", "MISSING": "missing", "NONE": "none"}


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
            conditions.append("b.KHU_VUC_ID = ?")
            params.append(self.area_id)
        if self.camera_id is not None:
            conditions.append("b.CAMERA_ID = ?")
            params.append(self.camera_id)
        if self.type_id is not None:
            conditions.append("b.LOI_ID = ?")
            params.append(self.type_id)
        if self.status is not None:
            if self.status not in PROCESSING_STATUSES:
                raise validation_error("Trạng thái xử lý không hợp lệ.")
            conditions.append("b.TRANG_THAI_XU_LY = ?")
            params.append(self.status)
        if self.date_from and self.date_to and self.date_to < self.date_from:
            raise validation_error("Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.")
        return ("WHERE " + " AND ".join(conditions)) if conditions else "", params


_SELECT = (
    "SELECT b.ID, b.MA_SU_KIEN, b.THOI_DIEM, b.TRACK_ID, b.DO_TIN_CAY_DONG_PHUC, b.DO_TIN_CAY_THE, "
    "b.ANH_TRANG_THAI, b.ANH_HET_HAN_LUC, b.ANH_DA_XOA_LUC, b.TRANG_THAI_XU_LY, b.GHI_CHU_XU_LY, b.XU_LY_LUC, "
    "b.NGAY_TAO, l.ID AS LOI_ID, l.MA_LOI, l.TEN_LOI, c.ID AS CAMERA_ID, c.MA_CAMERA, c.TEN_CAMERA, "
    "k.ID AS KHU_VUC_ID, k.MA_KHU_VUC, k.TEN_KHU_VUC, t.HO_TEN AS NGUOI_XU_LY "
    "FROM dbo.BAN_GHI_VIPHAM b "
    "INNER JOIN dbo.LOI_VIPHAM l ON l.ID = b.LOI_ID "
    "INNER JOIN dbo.CAMERA c ON c.ID = b.CAMERA_ID "
    "INNER JOIN dbo.KHU_VUC k ON k.ID = b.KHU_VUC_ID "
    "LEFT JOIN dbo.TAIKHOAN t ON t.ID = b.NGUOI_XU_LY_ID "
)


def evidence_info(row: dict[str, Any], now: datetime) -> dict[str, Any]:
    status = EVIDENCE_PUBLIC_STATUS.get(row["ANH_TRANG_THAI"], "none")
    expires_at = row["ANH_HET_HAN_LUC"]
    if status == "available" and expires_at is not None and expires_at <= now:
        status = "expired"
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
        "deleted_at": row["ANH_DA_XOA_LUC"],
        "message": messages[status],
        "url": f"/api/v1/violations/{row['ID']}/evidence" if status == "available" else None,
    }


def violation_to_dict(row: dict[str, Any], now: datetime) -> dict[str, Any]:
    def _float(value):
        return float(value) if value is not None else None

    return {
        "id": row["ID"],
        "event_id": str(row["MA_SU_KIEN"]).lower(),
        "occurred_at": row["THOI_DIEM"],
        "type": {"id": row["LOI_ID"], "code": row["MA_LOI"], "name": row["TEN_LOI"]},
        "camera": {"id": row["CAMERA_ID"], "code": row["MA_CAMERA"], "name": row["TEN_CAMERA"]},
        "area": {"id": row["KHU_VUC_ID"], "code": row["MA_KHU_VUC"], "name": row["TEN_KHU_VUC"]},
        "track_id": row["TRACK_ID"],
        "uniform_confidence": _float(row["DO_TIN_CAY_DONG_PHUC"]),
        "card_confidence": _float(row["DO_TIN_CAY_THE"]),
        "evidence": evidence_info(row, now),
        "processing": {
            "status": row["TRANG_THAI_XU_LY"],
            "note": row["GHI_CHU_XU_LY"],
            "processed_by": row["NGUOI_XU_LY"],
            "processed_at": row["XU_LY_LUC"],
        },
        "created_at": row["NGAY_TAO"],
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
        cursor.execute(f"SELECT COUNT_BIG(*) FROM dbo.BAN_GHI_VIPHAM b {where}", *params)
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
    note = (note or "").strip() or None
    if note is not None and len(note) > 500:
        raise validation_error("Ghi chú tối đa 500 ký tự.")
    with db.transaction() as cursor:
        cursor.execute(
            "UPDATE dbo.BAN_GHI_VIPHAM SET TRANG_THAI_XU_LY = ?, GHI_CHU_XU_LY = ?, NGUOI_XU_LY_ID = ?, "
            "XU_LY_LUC = ? WHERE ID = ?",
            status, note, actor_id, now, violation_id,
        )
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy bản ghi vi phạm.")
    return get_violation(db, violation_id, now)


@dataclass(frozen=True)
class IncomingViolation:
    event_id: uuid.UUID
    violation_code: str
    occurred_at: datetime  # local naive time
    track_id: int | None
    uniform_confidence: float | None
    card_confidence: float | None


@dataclass(frozen=True)
class IngestResult:
    created: bool
    record: dict[str, Any]


def _find_by_event(cursor, event_id: uuid.UUID) -> tuple[int, int] | None:
    cursor.execute("SELECT ID, CAMERA_ID FROM dbo.BAN_GHI_VIPHAM WHERE MA_SU_KIEN = ?", str(event_id))
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
                "INSERT INTO dbo.BAN_GHI_VIPHAM (MA_SU_KIEN, LOI_ID, CAMERA_ID, KHU_VUC_ID, THOI_DIEM, TRACK_ID, "
                "DO_TIN_CAY_DONG_PHUC, DO_TIN_CAY_THE, ANH_KHOA, ANH_SHA256, ANH_KICH_THUOC, ANH_TRANG_THAI, "
                "ANH_HET_HAN_LUC) OUTPUT INSERTED.ID VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'AVAILABLE', ?)",
                str(incoming.event_id), violation_type["ID"], camera.id, camera.area_id, incoming.occurred_at,
                incoming.track_id, incoming.uniform_confidence, incoming.card_confidence,
                stored.key, stored.sha256, stored.size, expires_at,
            )
            record_id = cursor.fetchone()[0]
            cursor.execute("UPDATE dbo.CAMERA SET LAN_CUOI_KET_NOI = ? WHERE ID = ?", now, camera.id)
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
    if existing_camera != camera_id:
        raise conflict("event_id đã được sử dụng bởi một camera khác.")
    return IngestResult(created=False, record=get_violation(db, record_id, now))


def evidence_key_for(db: Database, violation_id: int, now: datetime) -> tuple[str | None, dict[str, Any]]:
    row = db.fetch_one("SELECT ID, ANH_KHOA, ANH_TRANG_THAI, ANH_HET_HAN_LUC, ANH_DA_XOA_LUC "
                       "FROM dbo.BAN_GHI_VIPHAM WHERE ID = ?", (violation_id,))
    if row is None:
        raise not_found("Không tìm thấy bản ghi vi phạm.")
    info = evidence_info(row, now)
    return (row["ANH_KHOA"] if info["available"] else None), info


def mark_evidence_missing(db: Database, violation_id: int) -> None:
    with db.transaction() as cursor:
        cursor.execute("UPDATE dbo.BAN_GHI_VIPHAM SET ANH_TRANG_THAI = 'MISSING' "
                       "WHERE ID = ? AND ANH_TRANG_THAI = 'AVAILABLE'", violation_id)
