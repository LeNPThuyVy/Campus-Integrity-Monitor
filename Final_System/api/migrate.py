"""
Database setup/upgrade runner.

  python -m api.migrate status                 # show base tables and applied migrations
  python -m api.migrate apply                  # apply pending migrations (Database/migrations/*.sql)
  python -m api.migrate apply --init-base      # ALSO run SQL_Querysql.sql, only if the database is empty

Safety
- SQL_Querysql.sql (tables + sample data) is executed ONLY when none of its tables exist, so an
  existing database is never re-initialised or duplicated.
- Migrations are idempotent and transactional; already applied versions are skipped.
- Rollback scripts (*.rollback.sql) are never run automatically.
"""
from __future__ import annotations

import argparse
import re
import sys
from pathlib import Path

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")

from api.database import Database, DatabaseUnavailable
from api.settings import REPO_ROOT, get_settings

BASE_SCRIPT = REPO_ROOT / "SQL_Querysql.sql"
MIGRATIONS_DIR = REPO_ROOT / "Database" / "migrations"
BASE_TABLES = ("KHOA", "NGANH", "LOP", "SINHVIEN", "LOI_VIPHAM", "CT_VIPHAM")
_GO = re.compile(r"^\s*GO\s*;?\s*$", re.IGNORECASE | re.MULTILINE)


def split_batches(script: str) -> list[str]:
    return [batch.strip() for batch in _GO.split(script) if batch.strip()]


def migration_files() -> list[Path]:
    return sorted(p for p in MIGRATIONS_DIR.glob("*.sql") if not p.name.endswith(".rollback.sql"))


def run_script(db: Database, path: Path) -> None:
    connection = db.connect()
    try:
        connection.autocommit = True  # each script manages its own transaction
        cursor = connection.cursor()
        for batch in split_batches(path.read_text(encoding="utf-8-sig")):
            cursor.execute(batch)
            while cursor.nextset():
                pass
    finally:
        connection.close()


def existing_tables(db: Database) -> set[str]:
    return {row["name"].upper() for row in db.fetch_all("SELECT name FROM sys.tables")}


def applied_versions(db: Database) -> set[str]:
    if "SCHEMA_MIGRATIONS" not in existing_tables(db):
        return set()
    return {row["PHIEN_BAN"] for row in db.fetch_all("SELECT PHIEN_BAN FROM dbo.SCHEMA_MIGRATIONS")}


def status(db: Database) -> int:
    tables = existing_tables(db)
    present = [t for t in BASE_TABLES if t in tables]
    print(f"Bảng gốc ({len(present)}/{len(BASE_TABLES)}): {', '.join(present) or 'chưa có'}")
    applied = applied_versions(db)
    for path in migration_files():
        version = path.name.split("_", 1)[0]
        print(f"  [{'x' if version in applied else ' '}] {path.name}")
    return 0


def apply(db: Database, init_base: bool) -> int:
    tables = existing_tables(db)
    present = [t for t in BASE_TABLES if t in tables]
    if not present:
        if not init_base:
            print("Database chưa có schema gốc. Chạy lại với --init-base để tạo bảng và dữ liệu mẫu từ "
                  "SQL_Querysql.sql (chỉ chạy khi database trống).")
            return 2
        print(f"Khởi tạo schema gốc từ {BASE_SCRIPT.name} ...")
        run_script(db, BASE_SCRIPT)
    elif len(present) < len(BASE_TABLES):
        print("Database có schema gốc KHÔNG đầy đủ: " + ", ".join(present) + ". Dừng lại để tránh làm hỏng dữ liệu; "
              "hãy kiểm tra thủ công.")
        return 3
    elif init_base:
        print("Schema gốc đã tồn tại: bỏ qua --init-base (không chạy lại script khởi tạo).")

    applied = applied_versions(db)
    for path in migration_files():
        version = path.name.split("_", 1)[0]
        if version in applied:
            print(f"Đã áp dụng trước đó: {path.name}")
            continue
        print(f"Áp dụng {path.name} ...")
        run_script(db, path)
    print("Hoàn tất.")
    return 0


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Khởi tạo / nâng cấp database Campus Integrity Monitor.")
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("status")
    apply_parser = sub.add_parser("apply")
    apply_parser.add_argument("--init-base", action="store_true",
                              help="Chạy SQL_Querysql.sql nếu database chưa có bảng nào của hệ thống.")
    args = parser.parse_args(argv)
    db = Database(get_settings().database)
    try:
        if args.command == "status":
            return status(db)
        return apply(db, args.init_base)
    except DatabaseUnavailable as exc:
        print(f"Lỗi kết nối: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
