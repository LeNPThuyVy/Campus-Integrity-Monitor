"""
Application settings loaded from environment variables.

Values are read from the process environment first; a `.env` file at the repository root
(or in Final_System/) is loaded as a fallback so API, Desktop and Web can share one file.
Secrets are never hard-coded here: missing required values produce a clear error at the
point where they are needed (e.g. the database connection), not at import time.
"""
from __future__ import annotations

import os
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from functools import lru_cache
from pathlib import Path

from dotenv import load_dotenv

FINAL_SYSTEM_DIR = Path(__file__).resolve().parent.parent
REPO_ROOT = FINAL_SYSTEM_DIR.parent

# Process environment wins over .env (override=False).
for _env_file in (REPO_ROOT / ".env", FINAL_SYSTEM_DIR / ".env"):
    if _env_file.is_file():
        load_dotenv(_env_file, override=False)


def _env(name: str, default: str | None = None) -> str | None:
    value = os.getenv(name)
    if value is None or value.strip() == "":
        return default
    return value.strip()


def _env_bool(name: str, default: bool) -> bool:
    value = _env(name)
    if value is None:
        return default
    return value.lower() in {"1", "true", "yes", "on"}


def _env_int(name: str, default: int) -> int:
    value = _env(name)
    if value is None:
        return default
    try:
        return int(value)
    except ValueError as exc:
        raise ValueError(f"Biến môi trường {name} phải là số nguyên.") from exc


@dataclass(frozen=True)
class DatabaseSettings:
    driver: str
    server: str | None
    name: str | None
    user: str | None
    password: str | None
    trusted_connection: bool
    encrypt: str
    trust_server_certificate: str
    timeout: int

    def missing_fields(self) -> list[str]:
        missing = []
        if not self.server:
            missing.append("DB_SERVER")
        if not self.name:
            missing.append("DB_NAME")
        if not self.trusted_connection:
            if not self.user:
                missing.append("DB_USER")
            if self.password is None:
                missing.append("DB_PASSWORD")
        return missing


@dataclass(frozen=True)
class Settings:
    database: DatabaseSettings
    auth_secret: str | None
    token_ttl_minutes: int
    login_max_failures: int
    login_lock_minutes: int
    evidence_dir: Path
    evidence_retention_days: int
    evidence_max_bytes: int
    evidence_max_dimension: int
    retention_interval_minutes: int
    enable_retention_job: bool
    enable_inference: bool
    utc_offset_minutes: int
    event_max_age_days: int
    pdf_font_path: str | None
    allowed_hosts: list[str] = field(default_factory=list)
    r2_access_key: str | None = None
    r2_secret_key: str | None = None
    r2_endpoint: str | None = None
    r2_bucket: str = "my-budget-graduate-project"
    r2_presign_ttl_seconds: int = 3600

    @property
    def local_tz(self) -> timezone:
        return timezone(timedelta(minutes=self.utc_offset_minutes))

    def now_local(self) -> datetime:
        """Current local wall-clock time (naive), matching the DATETIME2 columns."""
        return datetime.now(self.local_tz).replace(tzinfo=None, microsecond=0)


def _resolve_dir(raw: str | None, default: Path) -> Path:
    if not raw:
        return default
    path = Path(raw)
    if not path.is_absolute():
        path = REPO_ROOT / path
    return path


@lru_cache(maxsize=1)
def get_settings() -> Settings:
    trusted = _env_bool("DB_TRUSTED_CONNECTION", False)
    database = DatabaseSettings(
        driver=_env("DB_DRIVER", "ODBC Driver 18 for SQL Server"),
        server=_env("DB_SERVER"),
        name=_env("DB_NAME"),
        user=_env("DB_USER"),
        password=os.getenv("DB_PASSWORD"),
        trusted_connection=trusted,
        encrypt=_env("DB_ENCRYPT", "yes"),
        trust_server_certificate=_env("DB_TRUST_SERVER_CERTIFICATE", "no"),
        timeout=_env_int("DB_TIMEOUT", 10),
    )
    hosts = _env("CIM_ALLOWED_HOSTS", "")
    return Settings(
        database=database,
        auth_secret=_env("CIM_AUTH_SECRET"),
        token_ttl_minutes=_env_int("CIM_TOKEN_TTL_MINUTES", 480),
        login_max_failures=_env_int("CIM_LOGIN_MAX_FAILURES", 5),
        login_lock_minutes=_env_int("CIM_LOGIN_LOCK_MINUTES", 15),
        evidence_dir=_resolve_dir(_env("CIM_EVIDENCE_DIR"), FINAL_SYSTEM_DIR / "storage" / "evidence"),
        evidence_retention_days=_env_int("CIM_EVIDENCE_RETENTION_DAYS", 30),
        evidence_max_bytes=_env_int("CIM_EVIDENCE_MAX_BYTES", 2 * 1024 * 1024),
        evidence_max_dimension=_env_int("CIM_EVIDENCE_MAX_DIMENSION", 4096),
        retention_interval_minutes=_env_int("CIM_RETENTION_INTERVAL_MINUTES", 60),
        enable_retention_job=_env_bool("CIM_ENABLE_RETENTION_JOB", True),
        enable_inference=_env_bool("CIM_ENABLE_INFERENCE", True),
        utc_offset_minutes=_env_int("CIM_UTC_OFFSET_MINUTES", 420),
        event_max_age_days=_env_int("CIM_EVENT_MAX_AGE_DAYS", 7),
        pdf_font_path=_env("CIM_PDF_FONT_PATH"),
        allowed_hosts=[h.strip() for h in (hosts or "").split(",") if h.strip()],
        r2_access_key=_env("CLOUDFLARE_ACCESS_KEY"),
        r2_secret_key=_env("CLOUDFLARE_SECRECT_ACCESS_KEY"),
        r2_endpoint=_env("CLOUDFLARE_S3_API_ENDPOINT"),
        r2_bucket=_env("CLOUDFLARE_R2_BUCKET", "my-budget-graduate-project"),
        r2_presign_ttl_seconds=_env_int("CLOUDFLARE_R2_PRESIGN_TTL", 3600),
    )
