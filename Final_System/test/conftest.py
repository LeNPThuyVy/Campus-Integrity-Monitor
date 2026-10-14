"""
Shared pytest configuration.

Integration tests (marker `db`) run against a dedicated SQL Server test database:
  CIM_TEST_DB_NAME=CampusIntegrityMonitor_Test DB_SERVER=.\\SQLEXPRESS DB_TRUSTED_CONNECTION=yes \
  DB_TRUST_SERVER_CERTIFICATE=yes python -m pytest
For safety the database name must contain "test"; the new tables of migration 001 are emptied at
the start of the session. Without CIM_TEST_DB_NAME these tests are skipped.
"""
from __future__ import annotations

import os
import sys
import tempfile
from pathlib import Path

import pytest

FINAL_SYSTEM = Path(__file__).resolve().parent.parent
if str(FINAL_SYSTEM) not in sys.path:
    sys.path.insert(0, str(FINAL_SYSTEM))

_EVIDENCE_TMP = tempfile.mkdtemp(prefix="cim-evidence-test-")
_TEST_DB = os.getenv("CIM_TEST_DB_NAME", "").strip()

# Configure the API for tests before any `api.*` import.
os.environ["CIM_ENABLE_INFERENCE"] = "false"
os.environ["CIM_ENABLE_RETENTION_JOB"] = "false"
os.environ["CIM_AUTH_SECRET"] = "pytest-secret-" + "x" * 40
os.environ["CIM_EVIDENCE_DIR"] = _EVIDENCE_TMP
os.environ["CIM_LOGIN_MAX_FAILURES"] = "3"
if _TEST_DB:
    os.environ["DB_NAME"] = _TEST_DB
else:
    # Make sure a developer .env never points the test suite at a real database.
    os.environ["DB_NAME"] = ""


def pytest_collection_modifyitems(config, items):
    if _TEST_DB and "test" in _TEST_DB.lower():
        return
    reason = ("Cần CIM_TEST_DB_NAME (tên chứa 'test') và DB_SERVER để chạy integration test SQL Server."
              if not _TEST_DB else "Tên database test phải chứa 'test' để tránh ghi vào database thật.")
    skip = pytest.mark.skip(reason=reason)
    for item in items:
        if "db" in item.keywords:
            item.add_marker(skip)


@pytest.fixture(scope="session")
def evidence_dir() -> Path:
    return Path(_EVIDENCE_TMP)


@pytest.fixture(scope="session")
def database():
    from api.database import Database
    from api.settings import get_settings

    get_settings.cache_clear()
    db = Database(get_settings().database)
    health = db.health()
    if health["status"] != "ok":
        pytest.skip(f"Database test chưa sẵn sàng: {health}")
    with db.transaction() as cursor:
        for table in ("NHAT_KY_XOA_ANH", "BAN_GHI_VIPHAM", "CAMERA", "KHU_VUC", "HOC_KY", "TAIKHOAN"):
            cursor.execute(f"DELETE FROM dbo.{table}")
    return db


@pytest.fixture(scope="session")
def client(database):
    from fastapi.testclient import TestClient

    from api.main import app

    with TestClient(app) as test_client:
        yield test_client
