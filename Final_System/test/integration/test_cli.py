"""First-administrator bootstrap and configuration report (api.cli) against the TEST database."""
import pytest

from api import cli
from api.errors import ApiError

pytestmark = pytest.mark.db


def test_create_admin_only_once_without_flag(database, monkeypatch, capsys):
    with database.transaction() as cursor:
        cursor.execute("UPDATE dbo.TAIKHOAN SET DANG_HOAT_DONG = 0 WHERE VAI_TRO = 'ADMIN'")
    monkeypatch.setenv("CIM_ADMIN_PASSWORD", "weak")
    with pytest.raises(ApiError, match="Mật khẩu"):  # main() turns this into a message + exit code 1
        cli.create_admin(database, "cli_admin_a", "CLI Admin", additional=False)
    monkeypatch.setenv("CIM_ADMIN_PASSWORD", "CliAdmin123")
    assert cli.create_admin(database, "cli_admin_a", "CLI Admin", additional=False) == 0
    assert cli.create_admin(database, "cli_admin_b", "CLI Admin B", additional=False) == 1
    assert "Đã có" in capsys.readouterr().err
    assert cli.create_admin(database, "cli_admin_b", "CLI Admin B", additional=True) == 0
    rows = database.fetch_all("SELECT TEN_DANG_NHAP, MAT_KHAU_HASH FROM dbo.TAIKHOAN WHERE TEN_DANG_NHAP LIKE 'cli_admin_%'")
    assert {r["TEN_DANG_NHAP"] for r in rows} == {"cli_admin_a", "cli_admin_b"}
    assert all(r["MAT_KHAU_HASH"].startswith("scrypt$") and "CliAdmin123" not in r["MAT_KHAU_HASH"] for r in rows)
    with database.transaction() as cursor:
        cursor.execute("DELETE FROM dbo.TAIKHOAN WHERE TEN_DANG_NHAP LIKE 'cli_admin_%'")
        cursor.execute("UPDATE dbo.TAIKHOAN SET DANG_HOAT_DONG = 1 WHERE VAI_TRO = 'ADMIN'")


def test_main_reports_errors_without_traceback(database, monkeypatch, capsys):
    monkeypatch.setenv("CIM_ADMIN_PASSWORD", "CliAdmin123")
    assert cli.main(["create-admin", "--username", "a b", "--full-name", "X", "--additional"]) == 1
    assert "Tên đăng nhập" in capsys.readouterr().err


def test_check_config_hides_secrets(database, monkeypatch, capsys):
    monkeypatch.setenv("DB_PASSWORD", "super-secret-value")
    assert cli.check_config() == 0
    output = capsys.readouterr().out
    assert "super-secret-value" not in output and "pytest-secret" not in output
    assert "Kết nối và schema SQL Server" in output
