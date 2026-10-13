"""Violation records: Desktop ingestion (device key) and Web lookup/processing (user token)."""
from __future__ import annotations

import logging
import math
import uuid
from datetime import date, datetime, timedelta

from fastapi import APIRouter, Depends, Path, Query, Request
from fastapi.concurrency import run_in_threadpool
from fastapi.responses import FileResponse, JSONResponse
from starlette.datastructures import UploadFile

from api.database import Database
from api.dependencies import (AuthenticatedCamera, CurrentUser, get_db, get_device_camera, get_settings_dep,
                              require_roles)
from api.errors import ApiError, error_body, validation_error
from api.evidence import EvidenceError, EvidenceStorage, UnsafeEvidencePath, validate_and_normalize_image
from api.repositories import violations as repo
from api.settings import Settings
from api.v1_schemas import ProcessingUpdateRequest

logger = logging.getLogger(__name__)
router = APIRouter(prefix="/api/v1", tags=["Bản ghi vi phạm"])

INGEST_FIELDS = {"event_id", "violation_code", "occurred_at", "track_id", "uniform_confidence", "card_confidence",
                 "image"}
IDENTITY_FIELDS = {"ma_sv", "mssv", "student_id", "student_code", "ho_ten", "hoten", "full_name", "student_name",
                   "name"}


def get_storage(request: Request) -> EvidenceStorage:
    return request.app.state.evidence_storage


def _parse_occurred_at(raw: str, settings: Settings) -> datetime:
    try:
        value = datetime.fromisoformat(raw.strip().replace("Z", "+00:00"))
    except (ValueError, AttributeError) as exc:
        raise validation_error("occurred_at phải theo định dạng ISO 8601, ví dụ 2026-10-13T08:15:30+07:00.") from exc
    if value.tzinfo is not None:
        value = value.astimezone(settings.local_tz).replace(tzinfo=None)
    value = value.replace(microsecond=0)
    now = settings.now_local()
    if value > now + timedelta(minutes=5):
        raise validation_error("occurred_at nằm ở tương lai; kiểm tra đồng hồ của máy Desktop.")
    if value < now - timedelta(days=settings.event_max_age_days):
        raise validation_error(f"occurred_at cũ hơn {settings.event_max_age_days} ngày; bản ghi không được nhận.")
    return value


def _parse_confidence(raw, field: str) -> float | None:
    if raw is None or (isinstance(raw, str) and raw.strip() == ""):
        return None
    try:
        value = float(raw)
    except (TypeError, ValueError) as exc:
        raise validation_error(f"{field} phải là số trong khoảng 0..1.") from exc
    if math.isnan(value) or not 0 <= value <= 1:
        raise validation_error(f"{field} phải là số trong khoảng 0..1.")
    return round(value, 4)


@router.get("/device/me", tags=["Thiết bị Desktop"], summary="Desktop kiểm tra camera/khóa thiết bị")
def device_me(camera: AuthenticatedCamera = Depends(get_device_camera), db: Database = Depends(get_db),
              settings: Settings = Depends(get_settings_dep)):
    from api.repositories.facilities import touch_camera

    touch_camera(db, camera.id, settings.now_local())
    return {"data": {"camera": {"id": camera.id, "code": camera.code, "name": camera.name},
                     "area": {"id": camera.area_id, "code": camera.area_code, "name": camera.area_name},
                     "violation_codes": list(repo.VIOLATION_CODES),
                     "max_image_bytes": settings.evidence_max_bytes}}


@router.post("/violations", status_code=201, tags=["Thiết bị Desktop"],
             summary="Desktop gửi bản ghi vi phạm + ảnh crop (multipart/form-data, idempotent theo event_id)")
async def ingest_violation(request: Request, camera: AuthenticatedCamera = Depends(get_device_camera),
                           db: Database = Depends(get_db), settings: Settings = Depends(get_settings_dep),
                           storage: EvidenceStorage = Depends(get_storage)):
    content_length = request.headers.get("content-length")
    if content_length and content_length.isdigit() and int(content_length) > settings.evidence_max_bytes + 64 * 1024:
        raise ApiError(413, "payload_too_large", "Yêu cầu vượt quá dung lượng cho phép.")
    content_type = request.headers.get("content-type", "")
    if not content_type.lower().startswith("multipart/form-data"):
        raise ApiError(415, "unsupported_media_type", "Yêu cầu phải dùng multipart/form-data.")

    form = await request.form(max_files=1, max_fields=10)
    try:
        keys = {key.lower() for key in form.keys()}
        identity = sorted(keys & IDENTITY_FIELDS)
        if identity:
            raise validation_error("API không nhận thông tin danh tính sinh viên từ AI.", {"rejected_fields": identity})
        unexpected = sorted(keys - INGEST_FIELDS)
        if unexpected:
            raise validation_error("Có trường không được hỗ trợ.", {"rejected_fields": unexpected})

        raw_event_id = form.get("event_id")
        try:
            event_id = uuid.UUID(str(raw_event_id))
        except (ValueError, TypeError) as exc:
            raise validation_error("event_id phải là UUID.") from exc

        violation_code = str(form.get("violation_code") or "").strip().upper()
        if violation_code not in repo.VIOLATION_CODES:
            raise validation_error("violation_code phải là UNIFORM, NO_CARD hoặc BOTH.")

        occurred_at = _parse_occurred_at(str(form.get("occurred_at") or ""), settings)

        track_raw = form.get("track_id")
        track_id = None
        if track_raw not in (None, ""):
            if not str(track_raw).isdigit() or int(track_raw) > 2_147_483_647:
                raise validation_error("track_id phải là số nguyên không âm.")
            track_id = int(track_raw)

        uniform_confidence = _parse_confidence(form.get("uniform_confidence"), "uniform_confidence")
        card_confidence = _parse_confidence(form.get("card_confidence"), "card_confidence")

        image = form.get("image")
        data = None
        if isinstance(image, UploadFile):
            data = await image.read(settings.evidence_max_bytes + 1)
    finally:
        await form.close()

    incoming = repo.IncomingViolation(event_id=event_id, violation_code=violation_code, occurred_at=occurred_at,
                                      track_id=track_id, uniform_confidence=uniform_confidence,
                                      card_confidence=card_confidence)
    # Database and image work are blocking: run them off the event loop.
    body, status_code = await run_in_threadpool(_store_violation, db, storage, settings, camera, incoming, data)
    return JSONResponse(content=_encode(body), status_code=status_code)


def _store_violation(db: Database, storage: EvidenceStorage, settings: Settings, camera: AuthenticatedCamera,
                     incoming: repo.IncomingViolation, data: bytes | None) -> tuple[dict, int]:
    # Idempotency first: a resend of an already stored event must not require the image again.
    existing = repo.find_existing_event(db, incoming.event_id)
    if existing is not None:
        result = repo.duplicate_result(db, existing, camera.id, settings.now_local())
        return {"data": result.record, "duplicate": True}, 200
    if data is None:
        raise validation_error("Thiếu tệp ảnh bằng chứng (trường image).")
    try:
        jpeg, width, height = validate_and_normalize_image(data, settings.evidence_max_bytes,
                                                           settings.evidence_max_dimension)
    except EvidenceError as exc:
        raise ApiError(exc.status_code, "invalid_image", str(exc)) from exc
    result = repo.ingest_violation(db, storage, camera, incoming, jpeg, width, height, settings.now_local(),
                                   settings.evidence_retention_days)
    return {"data": result.record, "duplicate": not result.created}, (201 if result.created else 200)


def _encode(value):
    from fastapi.encoders import jsonable_encoder
    return jsonable_encoder(value)


@router.get("/violation-types", summary="Danh sách loại vi phạm")
def violation_types(_: CurrentUser = Depends(require_roles()), db: Database = Depends(get_db)):
    return {"items": repo.list_violation_types(db)}


@router.get("/violations", summary="Tra cứu bản ghi vi phạm (lọc, phân trang, sắp xếp theo thời gian)")
def list_violations(
    date_from: date | None = None,
    date_to: date | None = None,
    area_id: int | None = Query(default=None, ge=1),
    camera_id: int | None = Query(default=None, ge=1),
    type_id: int | None = Query(default=None, ge=1),
    status: str | None = Query(default=None, pattern="^(MOI|DA_XEM|DA_XU_LY|BO_QUA)$"),
    page: int = Query(default=1, ge=1, le=100000),
    page_size: int = Query(default=20, ge=1, le=100),
    sort: str = Query(default="newest", pattern="^(newest|oldest)$"),
    _: CurrentUser = Depends(require_roles()),
    db: Database = Depends(get_db),
    settings: Settings = Depends(get_settings_dep),
):
    filters = repo.ViolationFilters(date_from=date_from, date_to=date_to, area_id=area_id, camera_id=camera_id,
                                    type_id=type_id, status=status)
    return repo.list_violations(db, filters, page, page_size, sort, settings.now_local())


@router.get("/violations/{violation_id}", summary="Chi tiết bản ghi vi phạm")
def get_violation(violation_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles()),
                  db: Database = Depends(get_db), settings: Settings = Depends(get_settings_dep)):
    return {"data": repo.get_violation(db, violation_id, settings.now_local())}


@router.patch("/violations/{violation_id}", summary="Cập nhật trạng thái xử lý (không gán danh tính, không trừ điểm)")
def update_violation(body: ProcessingUpdateRequest, violation_id: int = Path(ge=1),
                     user: CurrentUser = Depends(require_roles()), db: Database = Depends(get_db),
                     settings: Settings = Depends(get_settings_dep)):
    return {"data": repo.update_processing(db, violation_id, body.status, body.note, user.id, settings.now_local())}


@router.get("/violations/{violation_id}/evidence", summary="Ảnh crop bằng chứng (nếu còn hạn)")
def get_evidence(violation_id: int = Path(ge=1), _: CurrentUser = Depends(require_roles()),
                 db: Database = Depends(get_db), settings: Settings = Depends(get_settings_dep),
                 storage: EvidenceStorage = Depends(get_storage)):
    key, info = repo.evidence_key_for(db, violation_id, settings.now_local())
    if key is None:
        return JSONResponse(error_body("evidence_unavailable", info["message"], {"status": info["status"]}),
                            status_code=410)
    try:
        path = storage.resolve(key)
    except UnsafeEvidencePath:
        logger.error("Khóa ảnh không hợp lệ ở bản ghi %s.", violation_id)
        return JSONResponse(error_body("evidence_unavailable", "Không thể đọc ảnh bằng chứng."), status_code=410)
    if not path.is_file():
        repo.mark_evidence_missing(db, violation_id)
        return JSONResponse(error_body("evidence_unavailable", "Không tìm thấy tệp ảnh bằng chứng trên máy chủ.",
                                       {"status": "missing"}), status_code=410)
    return FileResponse(path, media_type="image/jpeg", filename=f"vi-pham-{violation_id}.jpg",
                        content_disposition_type="inline",
                        headers={"Cache-Control": "private, no-store", "X-Content-Type-Options": "nosniff"})

