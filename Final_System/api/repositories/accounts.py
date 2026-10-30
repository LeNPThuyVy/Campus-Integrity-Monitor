"""Accounts (TAIKHOAN): authentication, lockout and administration."""
from __future__ import annotations

import re
from dataclasses import dataclass
from datetime import timedelta
from typing import Any

from api.database import Database, is_unique_violation
from api.errors import ApiError, conflict, not_found, validation_error
from api.security import DUMMY_PASSWORD_HASH, hash_password, validate_password_strength, verify_password
from api.settings import Settings

USERNAME_PATTERN = re.compile(r"^[A-Za-z0-9_.-]{3,50}$")
ROLES = ("ADMIN", "CTSV")

_ACCOUNT_COLUMNS = "ID, USERNAME, FULL_NAME, ROLE, ACTIVE, LOCKED_UNTIL, LAST_LOGIN_AT"


def account_to_dict(row: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": row["ID"],
        "username": row["USERNAME"],
        "full_name": row["FULL_NAME"],
        "role": row["ROLE"],
        "active": bool(row["ACTIVE"]),
        "locked_until": row.get("LOCKED_UNTIL"),
        "last_login_at": row.get("LAST_LOGIN_AT"),
    }


def validate_username(username: str) -> str:
    username = (username or "").strip()
    if not USERNAME_PATTERN.fullmatch(username):
        raise validation_error("Tên đăng nhập gồm 3-50 ký tự: chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.")
    return username


def validate_full_name(full_name: str) -> str:
    full_name = (full_name or "").strip()
    if not full_name or len(full_name) > 100:
        raise validation_error("Họ tên là bắt buộc và tối đa 100 ký tự.")
    return full_name


def validate_role(role: str) -> str:
    if role not in ROLES:
        raise validation_error("Vai trò chỉ được là ADMIN hoặc CTSV.")
    return role


def validate_new_password(password: str) -> str:
    problems = validate_password_strength(password)
    if problems:
        raise validation_error("Mật khẩu chưa đủ mạnh.", problems)
    return password


@dataclass(frozen=True)
class LoginResult:
    account: dict[str, Any]


def authenticate(db: Database, settings: Settings, username: str, password: str) -> LoginResult:
    """Verify credentials with lockout after repeated failures. Error messages do not reveal
    whether the username exists."""
    generic = ApiError(401, "invalid_credentials", "Tên đăng nhập hoặc mật khẩu không đúng.")
    username = (username or "").strip()
    if not username or not password or len(username) > 50 or len(password) > 256:
        raise generic

    now = settings.now_local()
    with db.transaction() as cursor:
        cursor.execute(
            "SELECT ID, USERNAME, FULL_NAME, ROLE, ACTIVE, PASSWORD_HASH, FAILED_ATTEMPTS, LOCKED_UNTIL, "
            "TOKEN_VERSION FROM dbo.TAIKHOAN WITH (UPDLOCK, ROWLOCK) WHERE USERNAME = ?",
            username,
        )
        row = cursor.fetchone()
        if row is None:
            verify_password(password, DUMMY_PASSWORD_HASH)  # equalise timing
            raise generic
        columns = [column[0] for column in cursor.description]
        account = dict(zip(columns, row))

        if account["LOCKED_UNTIL"] is not None and account["LOCKED_UNTIL"] > now:
            raise ApiError(423, "account_locked",
                           "Tài khoản tạm khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau "
                           f"{account['LOCKED_UNTIL']:%H:%M}.")

        if not verify_password(password, account["PASSWORD_HASH"]):
            failures = account["FAILED_ATTEMPTS"] + 1
            locked_until = None
            if failures >= settings.login_max_failures:
                locked_until = now + timedelta(minutes=settings.login_lock_minutes)
                failures = 0
            cursor.execute("UPDATE dbo.TAIKHOAN SET FAILED_ATTEMPTS = ?, LOCKED_UNTIL = ? WHERE ID = ?",
                           failures, locked_until, account["ID"])
            cursor.commit()
            raise generic

        if not account["ACTIVE"]:
            raise ApiError(403, "account_disabled", "Tài khoản đã bị vô hiệu hóa. Liên hệ quản trị viên.")

        cursor.execute("UPDATE dbo.TAIKHOAN SET FAILED_ATTEMPTS = 0, LOCKED_UNTIL = NULL, LAST_LOGIN_AT = ? WHERE ID = ?",
                       now, account["ID"])
    account.pop("PASSWORD_HASH", None)
    return LoginResult(account=account)


def get_account(db: Database, account_id: int) -> dict[str, Any]:
    row = db.fetch_one(f"SELECT {_ACCOUNT_COLUMNS} FROM dbo.TAIKHOAN WHERE ID = ?", (account_id,))
    if row is None:
        raise not_found("Không tìm thấy tài khoản.")
    return account_to_dict(row)


def list_accounts(db: Database) -> list[dict[str, Any]]:
    rows = db.fetch_all(f"SELECT {_ACCOUNT_COLUMNS} FROM dbo.TAIKHOAN ORDER BY ROLE, USERNAME")
    return [account_to_dict(row) for row in rows]


def create_account(db: Database, username: str, full_name: str, role: str, password: str) -> dict[str, Any]:
    username = validate_username(username)
    full_name = validate_full_name(full_name)
    role = validate_role(role)
    validate_new_password(password)
    password_hash = hash_password(password)
    try:
        with db.transaction() as cursor:
            cursor.execute(
                "INSERT INTO dbo.TAIKHOAN (USERNAME, FULL_NAME, PASSWORD_HASH, ROLE) OUTPUT INSERTED.ID "
                "VALUES (?, ?, ?, ?)",
                username, full_name, password_hash, role,
            )
            account_id = cursor.fetchone()[0]
    except Exception as exc:
        if is_unique_violation(exc):
            raise conflict("Tên đăng nhập đã tồn tại.") from exc
        raise
    return get_account(db, account_id)


def _count_other_active_admins(cursor, account_id: int) -> int:
    cursor.execute("SELECT COUNT(*) FROM dbo.TAIKHOAN WITH (UPDLOCK, HOLDLOCK) "
                   "WHERE ROLE = 'ADMIN' AND ACTIVE = 1 AND ID <> ?", account_id)
    return cursor.fetchone()[0]


def update_account(db: Database, account_id: int, actor_id: int, full_name: str, role: str,
                   active: bool) -> dict[str, Any]:
    full_name = validate_full_name(full_name)
    role = validate_role(role)
    with db.transaction() as cursor:
        cursor.execute("SELECT ROLE, ACTIVE FROM dbo.TAIKHOAN WITH (UPDLOCK) WHERE ID = ?", account_id)
        current = cursor.fetchone()
        if current is None:
            raise not_found("Không tìm thấy tài khoản.")
        if account_id == actor_id and (not active or role != "ADMIN"):
            raise conflict("Không thể tự vô hiệu hóa hoặc tự hạ quyền tài khoản đang đăng nhập.")
        demoting_admin = current[0] == "ADMIN" and current[1] and (role != "ADMIN" or not active)
        if demoting_admin and _count_other_active_admins(cursor, account_id) == 0:
            raise conflict("Phải còn ít nhất một quản trị viên đang hoạt động.")
        bump = 1 if (current[0] != role or bool(current[1]) != active) else 0
        cursor.execute(
            "UPDATE dbo.TAIKHOAN SET FULL_NAME = ?, ROLE = ?, ACTIVE = ?, "
            "TOKEN_VERSION = TOKEN_VERSION + ? WHERE ID = ?",
            full_name, role, 1 if active else 0, bump, account_id,
        )
    return get_account(db, account_id)


def reset_password(db: Database, account_id: int, new_password: str) -> None:
    validate_new_password(new_password)
    with db.transaction() as cursor:
        cursor.execute(
            "UPDATE dbo.TAIKHOAN SET PASSWORD_HASH = ?, TOKEN_VERSION = TOKEN_VERSION + 1, FAILED_ATTEMPTS = 0, "
            "LOCKED_UNTIL = NULL WHERE ID = ?",
            hash_password(new_password), account_id,
        )
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy tài khoản.")


def change_own_password(db: Database, account_id: int, current_password: str, new_password: str) -> None:
    row = db.fetch_one("SELECT PASSWORD_HASH FROM dbo.TAIKHOAN WHERE ID = ?", (account_id,))
    if row is None:
        raise not_found("Không tìm thấy tài khoản.")
    if not verify_password(current_password or "", row["PASSWORD_HASH"]):
        raise ApiError(400, "wrong_password", "Mật khẩu hiện tại không đúng.")
    if current_password == new_password:
        raise validation_error("Mật khẩu mới phải khác mật khẩu hiện tại.")
    reset_password(db, account_id, new_password)
