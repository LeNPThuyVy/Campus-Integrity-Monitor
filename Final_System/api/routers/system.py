"""Health checks. Never return connection strings, credentials or server paths."""
from __future__ import annotations

import os

from fastapi import APIRouter, Depends, Request
from fastapi.responses import JSONResponse

from api.database import Database
from api.dependencies import get_db, get_settings_dep
from api.settings import Settings

router = APIRouter(tags=["Hệ thống"])


@router.get("/health", summary="Kiểm tra API đang chạy (không truy cập database)")
def liveness() -> dict:
    return {"status": "ok"}


@router.get("/api/v1/health", summary="Kiểm tra API, kết nối SQL Server và thư mục ảnh")
def readiness(request: Request, db: Database = Depends(get_db), settings: Settings = Depends(get_settings_dep)):
    database = db.health()
    evidence_dir = settings.evidence_dir
    try:
        evidence_dir.mkdir(parents=True, exist_ok=True)
        storage_ok = os.access(evidence_dir, os.W_OK)
    except OSError:
        storage_ok = False
    body = {
        "status": "ok" if database["status"] == "ok" and storage_ok else "degraded",
        "database": database,
        "evidence_storage": {"writable": storage_ok, "retention_days": settings.evidence_retention_days},
        "inference": {"enabled": getattr(request.app.state, "get_service", None) is not None},
        "auth_configured": bool(settings.auth_secret and len(settings.auth_secret) >= 32),
        "server_time": settings.now_local(),
    }
    return JSONResponse(content=_jsonable(body), status_code=200 if body["status"] == "ok" else 503)


def _jsonable(value):
    from fastapi.encoders import jsonable_encoder
    return jsonable_encoder(value)
