"""Authentication and account administration."""
from __future__ import annotations

from fastapi import APIRouter, Depends, Path

from api.database import Database
from api.dependencies import ROLE_ADMIN, CurrentUser, get_current_user, get_db, get_settings_dep, require_roles
from api.errors import ApiError
from api.repositories import accounts
from api.security import TokenError, create_token
from api.settings import Settings
from api.v1_schemas import (AccountCreateRequest, AccountUpdateRequest, ChangePasswordRequest, LoginRequest,
                            PasswordResetRequest)

router = APIRouter(prefix="/api/v1", tags=["Xác thực & tài khoản"])


@router.post("/auth/login", summary="Đăng nhập, nhận access token")
def login(body: LoginRequest, db: Database = Depends(get_db), settings: Settings = Depends(get_settings_dep)):
    result = accounts.authenticate(db, settings, body.username, body.password)
    account = result.account
    try:
        token, expires_at = create_token(account["ID"], account["PHIEN_BAN_TOKEN"], settings.auth_secret,
                                         settings.token_ttl_minutes)
    except TokenError as exc:
        raise ApiError(503, "auth_not_configured", str(exc)) from exc
    return {
        "data": {
            "access_token": token,
            "token_type": "Bearer",
            "expires_at": expires_at,
            "user": {"id": account["ID"], "username": account["TEN_DANG_NHAP"], "full_name": account["HO_TEN"],
                     "role": account["VAI_TRO"]},
        }
    }


@router.get("/auth/me", summary="Thông tin tài khoản đang đăng nhập")
def me(user: CurrentUser = Depends(get_current_user)):
    return {"data": {"id": user.id, "username": user.username, "full_name": user.full_name, "role": user.role}}


@router.post("/auth/change-password", summary="Đổi mật khẩu của chính mình (thu hồi các phiên cũ)")
def change_password(body: ChangePasswordRequest, user: CurrentUser = Depends(get_current_user),
                    db: Database = Depends(get_db)):
    accounts.change_own_password(db, user.id, body.current_password, body.new_password)
    return {"data": {"changed": True, "message": "Đã đổi mật khẩu. Vui lòng đăng nhập lại."}}


@router.get("/accounts", summary="Danh sách tài khoản (Quản trị viên)")
def list_accounts(_: CurrentUser = Depends(require_roles(ROLE_ADMIN)), db: Database = Depends(get_db)):
    return {"items": accounts.list_accounts(db)}


@router.post("/accounts", status_code=201, summary="Tạo tài khoản (Quản trị viên)")
def create_account(body: AccountCreateRequest, _: CurrentUser = Depends(require_roles(ROLE_ADMIN)),
                   db: Database = Depends(get_db)):
    return {"data": accounts.create_account(db, body.username, body.full_name, body.role, body.password)}


@router.put("/accounts/{account_id}", summary="Cập nhật tài khoản (Quản trị viên)")
def update_account(body: AccountUpdateRequest, account_id: int = Path(ge=1),
                   user: CurrentUser = Depends(require_roles(ROLE_ADMIN)), db: Database = Depends(get_db)):
    return {"data": accounts.update_account(db, account_id, user.id, body.full_name, body.role, body.active)}


@router.post("/accounts/{account_id}/reset-password", summary="Đặt lại mật khẩu (Quản trị viên)")
def reset_password(body: PasswordResetRequest, account_id: int = Path(ge=1),
                   _: CurrentUser = Depends(require_roles(ROLE_ADMIN)), db: Database = Depends(get_db)):
    accounts.reset_password(db, account_id, body.new_password)
    return {"data": {"reset": True}}
