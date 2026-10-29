"""
This file holds helper functions that need to run before the main route can do its job.
"""
from __future__ import annotations

from dataclasses import dataclass
from typing import Callable

from fastapi import Depends, Header, Request

from api.database import Database
from api.errors import ApiError, forbidden, unauthorized
from api.mapper import Mapper
from api.security import TokenError, verify_token
from api.service import InferenceService
from api.settings import Settings

ROLE_ADMIN = "ADMIN"
ROLE_CTSV = "CTSV"
ALL_ROLES = (ROLE_ADMIN, ROLE_CTSV)


def get_service(request: Request) -> InferenceService:
    service = getattr(request.app.state, "get_service", None)
    if service is None:
        raise ApiError(503, "inference_disabled", "Dịch vụ nhận diện AI chưa được bật trên máy chủ API.")
    return service


def get_mapper(request: Request) -> Mapper:
    return request.app.state.get_mapper


def get_settings_dep(request: Request) -> Settings:
    return request.app.state.settings


def get_db(request: Request) -> Database:
    return request.app.state.db


@dataclass(frozen=True)
class CurrentUser:
    id: int
    username: str
    full_name: str
    role: str

    @property
    def is_admin(self) -> bool:
        return self.role == ROLE_ADMIN


def get_current_user(
    authorization: str | None = Header(default=None),
    settings: Settings = Depends(get_settings_dep),
    db: Database = Depends(get_db),
) -> CurrentUser:
    if not authorization or not authorization.lower().startswith("bearer "):
        raise unauthorized()
    token = authorization[7:].strip()
    try:
        payload = verify_token(token, settings.auth_secret)
    except TokenError as exc:
        raise unauthorized(str(exc) if "hết hạn" in str(exc) else "Phiên đăng nhập không hợp lệ.") from exc

    account = db.fetch_one(
        "SELECT ID, TEN_DANG_NHAP, HO_TEN, VAI_TRO, DANG_HOAT_DONG, PHIEN_BAN_TOKEN "
        "FROM dbo.TAIKHOAN WHERE ID = ?",
        (payload.account_id,),
    )
    if account is None or not account["DANG_HOAT_DONG"] or account["PHIEN_BAN_TOKEN"] != payload.version:
        raise unauthorized("Phiên đăng nhập đã bị thu hồi. Vui lòng đăng nhập lại.")
    return CurrentUser(id=account["ID"], username=account["TEN_DANG_NHAP"], full_name=account["HO_TEN"],
                       role=account["VAI_TRO"])


def require_roles(*roles: str) -> Callable[..., CurrentUser]:
    allowed = roles or ALL_ROLES

    def _dependency(user: CurrentUser = Depends(get_current_user)) -> CurrentUser:
        if user.role not in allowed:
            raise forbidden()
        return user

    return _dependency


@dataclass(frozen=True)
class AuthenticatedCamera:
    id: int
    code: str
    name: str
    area_id: int
    area_code: str
    area_name: str


def get_device_camera(
    x_camera_code: str | None = Header(default=None),
    x_device_key: str | None = Header(default=None),
    db: Database = Depends(get_db),
) -> AuthenticatedCamera:
    """Authenticate a Desktop client by camera code."""
    if not x_camera_code or len(x_camera_code) > 30:
        raise unauthorized("Thiếu hoặc sai mã camera (X-Camera-Code).")
    camera = db.fetch_one(
        "SELECT c.ID, c.MA_CAMERA, c.TEN_CAMERA, c.DANG_SU_DUNG, "
        "       k.ID AS KHU_VUC_ID, k.MA_KHU_VUC, k.TEN_KHU_VUC, k.DANG_SU_DUNG AS KHU_VUC_DANG_SU_DUNG "
        "FROM dbo.CAMERA c INNER JOIN dbo.KHU_VUC k ON k.ID = c.KHU_VUC_ID WHERE c.MA_CAMERA = ?",
        (x_camera_code.strip(),),
    )
    if camera is None:
        raise unauthorized("Mã camera không tồn tại trong hệ thống.")
    if not camera["DANG_SU_DUNG"]:
        raise forbidden("Camera đã ngừng sử dụng; không thể ghi nhận vi phạm.")
    if not camera["KHU_VUC_DANG_SU_DUNG"]:
        raise forbidden("Khu vực của camera đã ngừng sử dụng; không thể ghi nhận vi phạm.")
    return AuthenticatedCamera(id=camera["ID"], code=camera["MA_CAMERA"], name=camera["TEN_CAMERA"],
                               area_id=camera["KHU_VUC_ID"], area_code=camera["MA_KHU_VUC"],
                               area_name=camera["TEN_KHU_VUC"])
