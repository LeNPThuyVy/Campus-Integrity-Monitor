"""
Shared SQL Server access layer (pyodbc).

- One `Database` object per process; connections come from the ODBC driver manager pool,
  so opening a connection per unit of work is cheap and avoids sharing a connection
  between threads.
- `transaction()` commits on success and rolls back on any exception.
- All queries use `?` parameters; values are never formatted into SQL text.
- Connection errors are wrapped in `DatabaseUnavailable` without leaking the connection
  string or credentials.
"""
from __future__ import annotations

import logging
import threading
from contextlib import contextmanager
from typing import Any, Iterator, Sequence

from api.settings import DatabaseSettings

try:  # pyodbc is required for SQL Server; import lazily-safe for tooling.
    import pyodbc
except ImportError:  # pragma: no cover - surfaced through DatabaseUnavailable
    pyodbc = None

logger = logging.getLogger(__name__)

REQUIRED_TABLES = (
    "KHOA", "NGANH", "LOP", "SINHVIEN", "LOI_VIPHAM", "CT_VIPHAM",
    "TAIKHOAN", "KHU_VUC", "CAMERA", "HOC_KY", "BAN_GHI_VIPHAM", "NHAT_KY_XOA_ANH",
)


class DatabaseUnavailable(RuntimeError):
    """Raised when the database cannot be reached or is not configured."""


class DatabaseConfigError(DatabaseUnavailable):
    """Raised when required connection settings are missing."""


def _odbc_value(value: str) -> str:
    """Escape a value for an ODBC connection string attribute."""
    return "{" + value.replace("}", "}}") + "}"


def build_connection_string(settings: DatabaseSettings) -> str:
    missing = settings.missing_fields()
    if missing:
        raise DatabaseConfigError("Thiếu cấu hình kết nối SQL Server: " + ", ".join(missing))
    parts = [
        f"DRIVER={_odbc_value(settings.driver)}",
        f"SERVER={_odbc_value(settings.server)}",
        f"DATABASE={_odbc_value(settings.name)}",
        f"Encrypt={settings.encrypt}",
        f"TrustServerCertificate={settings.trust_server_certificate}",
        "APP=CampusIntegrityMonitor",
    ]
    if settings.trusted_connection:
        parts.append("Trusted_Connection=yes")
    else:
        parts.append(f"UID={_odbc_value(settings.user)}")
        parts.append(f"PWD={_odbc_value(settings.password or '')}")
    return ";".join(parts) + ";"


def rows_to_dicts(cursor) -> list[dict[str, Any]]:
    if cursor.description is None:
        return []
    columns = [column[0] for column in cursor.description]
    return [dict(zip(columns, row)) for row in cursor.fetchall()]


def row_to_dict(cursor) -> dict[str, Any] | None:
    if cursor.description is None:
        return None
    row = cursor.fetchone()
    if row is None:
        return None
    return dict(zip([column[0] for column in cursor.description], row))


def is_unique_violation(error: Exception) -> bool:
    """SQL Server error 2627 (unique constraint) or 2601 (unique index)."""
    text = str(error)
    return "2627" in text or "2601" in text


def is_foreign_key_violation(error: Exception) -> bool:
    return "547" in str(error)


class Database:
    def __init__(self, settings: DatabaseSettings):
        self._settings = settings
        self._lock = threading.Lock()
        self._connection_string: str | None = None

    @property
    def settings(self) -> DatabaseSettings:
        return self._settings

    def _get_connection_string(self) -> str:
        with self._lock:
            if self._connection_string is None:
                self._connection_string = build_connection_string(self._settings)
            return self._connection_string

    def connect(self):
        if pyodbc is None:
            raise DatabaseUnavailable("Chưa cài thư viện pyodbc (pip install pyodbc).")
        connection_string = self._get_connection_string()
        try:
            connection = pyodbc.connect(connection_string, timeout=self._settings.timeout, autocommit=False)
        except pyodbc.Error as exc:
            # Log only the SQLSTATE / driver message class, never the connection string.
            state = exc.args[0] if exc.args else "unknown"
            logger.error("Không thể kết nối SQL Server (SQLSTATE %s).", state)
            raise DatabaseUnavailable("Không thể kết nối tới SQL Server.") from exc
        connection.timeout = max(self._settings.timeout * 3, 30)
        return connection

    @contextmanager
    def transaction(self) -> Iterator[Any]:
        """Yield a cursor inside a transaction; commit on success, rollback on error."""
        connection = self.connect()
        cursor = connection.cursor()
        try:
            yield cursor
            connection.commit()
        except Exception:
            try:
                connection.rollback()
            except Exception:  # pragma: no cover - connection already broken
                logger.exception("Rollback thất bại.")
            raise
        finally:
            try:
                cursor.close()
            finally:
                connection.close()

    def fetch_all(self, sql: str, params: Sequence[Any] = ()) -> list[dict[str, Any]]:
        with self.transaction() as cursor:
            cursor.execute(sql, *params)
            return rows_to_dicts(cursor)

    def fetch_one(self, sql: str, params: Sequence[Any] = ()) -> dict[str, Any] | None:
        with self.transaction() as cursor:
            cursor.execute(sql, *params)
            return row_to_dict(cursor)

    def health(self) -> dict[str, Any]:
        """Check connectivity and schema without exposing server details or credentials."""
        try:
            with self.transaction() as cursor:
                cursor.execute(
                    "SELECT name FROM sys.tables WHERE name IN ("
                    + ",".join("?" for _ in REQUIRED_TABLES) + ")",
                    *REQUIRED_TABLES,
                )
                present = {row[0].upper() for row in cursor.fetchall()}
                migrations: list[str] = []
                if "SCHEMA_MIGRATIONS" not in present:
                    cursor.execute("SELECT OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U')")
                    if cursor.fetchone()[0] is not None:
                        cursor.execute("SELECT PHIEN_BAN FROM dbo.SCHEMA_MIGRATIONS ORDER BY PHIEN_BAN")
                        migrations = [row[0] for row in cursor.fetchall()]
        except DatabaseConfigError as exc:
            return {"status": "not_configured", "message": str(exc)}
        except DatabaseUnavailable:
            return {"status": "unavailable", "message": "Không thể kết nối tới SQL Server."}
        except Exception:  # unexpected driver/query error; keep details out of the response
            logger.exception("Kiểm tra sức khỏe database thất bại.")
            return {"status": "error", "message": "Kiểm tra database thất bại."}
        missing = [table for table in REQUIRED_TABLES if table not in present]
        return {
            "status": "ok" if not missing else "schema_incomplete",
            "missing_tables": missing,
            "migrations": migrations,
        }
