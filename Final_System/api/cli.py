"""
Administrative commands.

  python -m api.cli check-config
  python -m api.cli check-db
  python -m api.cli create-admin --username admin_ctsv --full-name "Quản trị hệ thống"

`create-admin` is meant for the FIRST administrator: it refuses when an active administrator
already exists (add more accounts from the Web page "Tài khoản & phân quyền", or pass
--additional deliberately). The password is read interactively and never echoed; automated setups
may provide it through CIM_ADMIN_PASSWORD. No default account or password exists in the code.
"""
from __future__ import annotations

import argparse
import getpass
import json
import os
import sys

from api.database import Database, DatabaseUnavailable
from api.errors import ApiError
from api.repositories import accounts
from api.security import MIN_SECRET_LENGTH
from api.settings import FINAL_SYSTEM_DIR, REPO_ROOT, get_settings

SECRET_VARS = {"DB_PASSWORD", "CIM_AUTH_SECRET", "CIM_DESKTOP_DEVICE_KEY", "GEMINI_API_KEY", "CIM_ADMIN_PASSWORD"}


def _read_password() -> str:
    env_password = os.getenv("CIM_ADMIN_PASSWORD")
    if env_password:
        print("Dùng mật khẩu từ biến môi trường CIM_ADMIN_PASSWORD (hãy xóa biến này sau khi tạo xong).")
        return env_password
    # Note: on Windows getpass reads the console directly, so run this in a PowerShell/cmd window
    # (or provide CIM_ADMIN_PASSWORD for unattended setups).
    first = getpass.getpass("Mật khẩu (tối thiểu 8 ký tự, có chữ hoa, chữ thường, chữ số): ")
    second = getpass.getpass("Nhập lại mật khẩu: ")
    if first != second:
        raise SystemExit("Hai lần nhập mật khẩu không khớp.")
    return first


def _line(ok: bool | None, label: str, detail: str = "") -> bool:
    mark = {True: "[ OK ]", False: "[LỖI]", None: "[ -- ]"}[ok]
    print(f"{mark} {label}" + (f": {detail}" if detail else ""))
    return ok is not False


def check_config() -> int:
    """Print a configuration report without revealing secret values."""
    settings = get_settings()
    db = settings.database
    healthy = True
    env_files = [str(p) for p in (REPO_ROOT / ".env", FINAL_SYSTEM_DIR / ".env") if p.is_file()]
    _line(bool(env_files) or None, "File .env", ", ".join(env_files) or "không có (dùng biến môi trường hệ thống)")

    for name in ("DB_SERVER", "DB_NAME", "DB_DRIVER", "DB_TRUSTED_CONNECTION", "DB_USER", "DB_PASSWORD",
                 "DB_ENCRYPT", "DB_TRUST_SERVER_CERTIFICATE", "CIM_AUTH_SECRET", "CIM_EVIDENCE_DIR", "CIM_API_BASE_URL"):
        value = os.getenv(name)
        shown = "(đã đặt)" if name in SECRET_VARS and value else (value if value else "(chưa đặt)")
        print(f"       {name} = {shown}")

    missing = db.missing_fields()
    healthy &= _line(not missing, "Thông tin kết nối SQL Server", "thiếu " + ", ".join(missing) if missing else
                     ("Windows Authentication" if db.trusted_connection else f"SQL Authentication, user {db.user}"))
    try:
        import pyodbc
        drivers = [d for d in pyodbc.drivers() if "SQL Server" in d]
        healthy &= _line(db.driver in drivers, "ODBC driver", f"cấu hình '{db.driver}', máy có: {drivers or 'không có'}")
    except ImportError:
        healthy &= _line(False, "pyodbc", "chưa cài (pip install -r requirements.txt)")

    secret_ok = bool(settings.auth_secret and len(settings.auth_secret) >= MIN_SECRET_LENGTH)
    healthy &= _line(secret_ok, "CIM_AUTH_SECRET",
                     "đủ độ dài" if secret_ok else f"chưa đặt hoặc ngắn hơn {MIN_SECRET_LENGTH} ký tự")

    # Read-only check: the API creates the folder on start-up.
    target = settings.evidence_dir
    probe = target if target.exists() else next((p for p in target.parents if p.exists()), target)
    writable = os.access(probe, os.W_OK)
    healthy &= _line(writable, "Thư mục ảnh bằng chứng",
                     str(target) + ("" if target.exists() else " (chưa có, API sẽ tạo khi khởi động)"))

    if not missing:
        health = Database(db).health()
        status = health["status"]
        detail = {"ok": "đủ bảng, migration " + ", ".join(health.get("migrations", [])),
                  "schema_incomplete": "thiếu bảng " + ", ".join(health.get("missing_tables", []))
                  + " -> chạy: python -m api.migrate apply"}.get(status, health.get("message", status))
        healthy &= _line(status == "ok", "Kết nối và schema SQL Server", detail)
    return 0 if healthy else 1


def create_admin(db: Database, username: str, full_name: str, additional: bool) -> int:
    health = db.health()
    if health["status"] != "ok":
        print("Database chưa sẵn sàng: " + json.dumps(health, ensure_ascii=False), file=sys.stderr)
        print("Chạy 'python -m api.migrate apply' (hoặc 'python -m api.cli check-config') trước.", file=sys.stderr)
        return 1
    existing = db.fetch_one("SELECT COUNT(*) AS N FROM dbo.TAIKHOAN WHERE VAI_TRO = 'ADMIN' AND DANG_HOAT_DONG = 1")
    if existing["N"] > 0 and not additional:
        print(f"Đã có {existing['N']} quản trị viên đang hoạt động. Hãy tạo thêm tài khoản trên trang Web "
              "'Tài khoản & phân quyền', hoặc chạy lại với --additional nếu thật sự cần.", file=sys.stderr)
        return 1
    account = accounts.create_account(db, username, full_name, "ADMIN", _read_password())
    print(f"Đã tạo quản trị viên '{account['username']}' (ID {account['id']}). Đăng nhập Web để tiếp tục cấu hình.")
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Công cụ quản trị Campus Integrity Monitor.")
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("check-config", help="Kiểm tra cấu hình .env, ODBC driver, kết nối và schema")
    sub.add_parser("check-db", help="Kiểm tra kết nối và schema database (JSON)")
    create = sub.add_parser("create-admin", help="Tạo tài khoản Quản trị viên đầu tiên")
    create.add_argument("--username", required=True)
    create.add_argument("--full-name", required=True)
    create.add_argument("--additional", action="store_true",
                        help="Cho phép tạo thêm admin khi đã có admin đang hoạt động")
    args = parser.parse_args(argv)

    if args.command == "check-config":
        return check_config()
    db = Database(get_settings().database)
    try:
        if args.command == "check-db":
            result = db.health()
            print(json.dumps(result, ensure_ascii=False, indent=2))
            return 0 if result["status"] == "ok" else 1
        return create_admin(db, args.username, args.full_name, args.additional)
    except ApiError as exc:
        details = f" {exc.details}" if exc.details else ""
        print(f"Lỗi: {exc.message}{details}", file=sys.stderr)
        return 1
    except DatabaseUnavailable as exc:
        print(f"Lỗi kết nối: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
