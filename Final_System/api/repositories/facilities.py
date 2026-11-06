"""Areas (KHU_VUC), cameras (CAMERA) and semesters (HOCKY)."""
from __future__ import annotations

import re
from datetime import date
from typing import Any

from api.database import Database, is_foreign_key_violation, is_unique_violation
from api.errors import conflict, not_found, validation_error

CODE_PATTERN = re.compile(r"^[A-Za-z0-9_-]+$")


def _code(value: str, field: str, max_length: int) -> str:
    value = (value or "").strip()
    if not value or len(value) > max_length or not CODE_PATTERN.fullmatch(value):
        raise validation_error(f"{field} bắt buộc, tối đa {max_length} ký tự, chỉ gồm chữ không dấu, số, _ hoặc -.")
    return value.upper()


def _text(value: str | None, field: str, max_length: int, required: bool = True) -> str | None:
    value = (value or "").strip()
    if not value:
        if required:
            raise validation_error(f"{field} là bắt buộc.")
        return None
    if len(value) > max_length:
        raise validation_error(f"{field} tối đa {max_length} ký tự.")
    return value


def _translate_write_error(exc: Exception, duplicate_message: str) -> None:
    if is_unique_violation(exc):
        raise conflict(duplicate_message) from exc
    if is_foreign_key_violation(exc):
        raise conflict("Dữ liệu tham chiếu không hợp lệ hoặc đang được sử dụng.") from exc
    raise exc


# ----------------------------------------------------------------------------------- areas

def area_to_dict(row: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": row["ID"], "code": row["MA_KHU_VUC"], "name": row["TEN_KHU_VUC"], "description": row["MO_TA"],
        "active": bool(row["DANG_SU_DUNG"]), "camera_count": row.get("SO_CAMERA", 0),
    }


def list_areas(db: Database, include_inactive: bool = True) -> list[dict[str, Any]]:
    where = "" if include_inactive else "WHERE k.DANG_SU_DUNG = 1"
    rows = db.fetch_all(
        "SELECT k.*, (SELECT COUNT(*) FROM dbo.CAMERA c WHERE c.KHU_VUC_ID = k.ID) AS SO_CAMERA "
        f"FROM dbo.KHU_VUC k {where} ORDER BY k.MA_KHU_VUC"
    )
    return [area_to_dict(row) for row in rows]


def get_area(db: Database, area_id: int) -> dict[str, Any]:
    row = db.fetch_one("SELECT k.*, (SELECT COUNT(*) FROM dbo.CAMERA c WHERE c.KHU_VUC_ID = k.ID) AS SO_CAMERA "
                       "FROM dbo.KHU_VUC k WHERE k.ID = ?", (area_id,))
    if row is None:
        raise not_found("Không tìm thấy khu vực.")
    return area_to_dict(row)


def save_area(db: Database, area_id: int | None, code: str, name: str, description: str | None,
              active: bool) -> dict[str, Any]:
    code = _code(code, "Mã khu vực", 20)
    name = _text(name, "Tên khu vực", 100)
    description = _text(description, "Mô tả", 255, required=False)
    try:
        with db.transaction() as cursor:
            if area_id is None:
                cursor.execute("INSERT INTO dbo.KHU_VUC (MA_KHU_VUC, TEN_KHU_VUC, MO_TA, DANG_SU_DUNG) "
                               "OUTPUT INSERTED.ID VALUES (?, ?, ?, ?)", code, name, description, 1 if active else 0)
                area_id = cursor.fetchone()[0]
            else:
                cursor.execute("UPDATE dbo.KHU_VUC SET MA_KHU_VUC = ?, TEN_KHU_VUC = ?, MO_TA = ?, DANG_SU_DUNG = ? "
                               "WHERE ID = ?",
                               code, name, description, 1 if active else 0, area_id)
                if cursor.rowcount == 0:
                    raise not_found("Không tìm thấy khu vực.")
    except Exception as exc:
        if getattr(exc, "status_code", None):
            raise
        _translate_write_error(exc, "Mã khu vực đã tồn tại.")
    return get_area(db, area_id)


# --------------------------------------------------------------------------------- cameras

def camera_to_dict(row: dict[str, Any], include_sensitive: bool = False) -> dict[str, Any]:
    return {
        "id": row["ID"], "code": row["MA_CAMERA"], "name": row["TEN_CAMERA"],
        "area": {"id": row["KHU_VUC_ID"], "code": row["MA_KHU_VUC"], "name": row["TEN_KHU_VUC"]},
        "location": row["VI_TRI"], "active": bool(row["DANG_SU_DUNG"]),
    }


_CAMERA_SELECT = ("SELECT c.ID, c.MA_CAMERA, c.TEN_CAMERA, c.KHU_VUC_ID, c.VI_TRI, c.DANG_SU_DUNG, "
                  "k.MA_KHU_VUC, k.TEN_KHU_VUC FROM dbo.CAMERA c INNER JOIN dbo.KHU_VUC k ON k.ID = c.KHU_VUC_ID ")


def list_cameras(db: Database, include_sensitive: bool = False, area_id: int | None = None) -> list[dict[str, Any]]:
    params: tuple = ()
    where = ""
    if area_id is not None:
        where = "WHERE c.KHU_VUC_ID = ? "
        params = (area_id,)
    rows = db.fetch_all(_CAMERA_SELECT + where + "ORDER BY c.MA_CAMERA", params)
    return [camera_to_dict(row, include_sensitive) for row in rows]


def get_camera(db: Database, camera_id: int, include_sensitive: bool = False) -> dict[str, Any]:
    row = db.fetch_one(_CAMERA_SELECT + "WHERE c.ID = ?", (camera_id,))
    if row is None:
        raise not_found("Không tìm thấy camera.")
    return camera_to_dict(row, include_sensitive)


def save_camera(db: Database, camera_id: int | None, code: str, name: str, area_id: int, location: str | None,
                active: bool) -> dict[str, Any]:
    code = _code(code, "Mã camera", 30)
    name = _text(name, "Tên camera", 100)
    location = _text(location, "Vị trí", 255, required=False)
    if not isinstance(area_id, int) or area_id < 1:
        raise validation_error("Khu vực không hợp lệ.")
    try:
        with db.transaction() as cursor:
            cursor.execute("SELECT 1 FROM dbo.KHU_VUC WHERE ID = ?", area_id)
            if cursor.fetchone() is None:
                raise validation_error("Khu vực không tồn tại.")
            if camera_id is None:
                cursor.execute(
                    "INSERT INTO dbo.CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, DANG_SU_DUNG) "
                    "OUTPUT INSERTED.ID VALUES (?, ?, ?, ?, ?)",
                    code, name, area_id, location, 1 if active else 0,
                )
                camera_id = cursor.fetchone()[0]
            else:
                cursor.execute(
                    "UPDATE dbo.CAMERA SET MA_CAMERA = ?, TEN_CAMERA = ?, KHU_VUC_ID = ?, VI_TRI = ?, "
                    "DANG_SU_DUNG = ? WHERE ID = ?",
                    code, name, area_id, location, 1 if active else 0, camera_id,
                )
                if cursor.rowcount == 0:
                    raise not_found("Không tìm thấy camera.")
    except Exception as exc:
        if getattr(exc, "status_code", None):
            raise
        _translate_write_error(exc, "Mã camera đã tồn tại.")
    return get_camera(db, camera_id, include_sensitive=False)


# ------------------------------------------------------------------------------- semesters
# HOCKY is keyed by (NAM_HOC, HOC_KY); the display name is derived, never stored.

ACADEMIC_YEAR_PATTERN = re.compile(r"^(\d{4})-(\d{4})$")


def semester_key(academic_year: str, term: int) -> tuple[str, int]:
    match = ACADEMIC_YEAR_PATTERN.fullmatch((academic_year or "").strip())
    if not match or int(match.group(2)) != int(match.group(1)) + 1:
        raise validation_error("Năm học phải có dạng YYYY-YYYY, năm sau lớn hơn năm trước 1 (ví dụ 2025-2026).")
    if term not in (1, 2, 3):
        raise validation_error("Học kỳ phải là 1, 2 hoặc 3.")
    return match.group(0), term


def semester_name(academic_year: str, term: int) -> str:
    return f"Học kỳ {term} ({academic_year})"


def semester_to_dict(row: dict[str, Any], today: date | None = None) -> dict[str, Any]:
    today = today or date.today()
    start_date = row["NGAY_BAT_DAU"]
    end_date = row["NGAY_KET_THUC"]
    is_current = bool(start_date <= today <= end_date)
    academic_year, term = row["NAM_HOC"].strip(), int(row["HOC_KY"])
    return {
        "academic_year": academic_year, "term": term, "name": semester_name(academic_year, term),
        "start_date": start_date, "end_date": end_date, "is_current": is_current,
    }


def list_semesters(db: Database) -> list[dict[str, Any]]:
    return [semester_to_dict(row) for row in
            db.fetch_all("SELECT * FROM dbo.HOCKY ORDER BY NGAY_BAT_DAU DESC")]


def get_semester(db: Database, academic_year: str, term: int) -> dict[str, Any]:
    academic_year, term = semester_key(academic_year, term)
    row = db.fetch_one("SELECT * FROM dbo.HOCKY WHERE NAM_HOC = ? AND HOC_KY = ?", (academic_year, term))
    if row is None:
        raise not_found("Không tìm thấy học kỳ.")
    return semester_to_dict(row)


def current_semester(db: Database, today: date) -> dict[str, Any] | None:
    row = db.fetch_one("SELECT TOP (1) * FROM dbo.HOCKY WHERE ? BETWEEN NGAY_BAT_DAU AND NGAY_KET_THUC "
                       "ORDER BY NGAY_BAT_DAU DESC", (today,))
    return semester_to_dict(row, today) if row else None


def save_semester(db: Database, academic_year: str, term: int, start_date: date, end_date: date,
                  creating: bool) -> dict[str, Any]:
    """Create a semester, or (creating=False) change its dates; the key itself never changes."""
    academic_year, term = semester_key(academic_year, term)
    if end_date < start_date:
        raise validation_error("Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.")
    try:
        with db.transaction() as cursor:
            cursor.execute(
                "SELECT TOP (1) NAM_HOC, HOC_KY FROM dbo.HOCKY WHERE NOT (NAM_HOC = ? AND HOC_KY = ?) "
                "AND NGAY_BAT_DAU <= ? AND NGAY_KET_THUC >= ?",
                academic_year, term, end_date, start_date,
            )
            overlap = cursor.fetchone()
            if overlap is not None:
                raise conflict(f"Khoảng thời gian bị trùng với {semester_name(overlap[0].strip(), overlap[1])}.")
            if creating:
                cursor.execute("INSERT INTO dbo.HOCKY (NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC) "
                               "VALUES (?, ?, ?, ?)", academic_year, term, start_date, end_date)
            else:
                cursor.execute("UPDATE dbo.HOCKY SET NGAY_BAT_DAU = ?, NGAY_KET_THUC = ? "
                               "WHERE NAM_HOC = ? AND HOC_KY = ?", start_date, end_date, academic_year, term)
                if cursor.rowcount == 0:
                    raise not_found("Không tìm thấy học kỳ.")
    except Exception as exc:
        if getattr(exc, "status_code", None):
            raise
        _translate_write_error(exc, f"{semester_name(academic_year, term)} đã tồn tại.")
    return get_semester(db, academic_year, term)


def delete_semester(db: Database, academic_year: str, term: int) -> None:
    """Semesters are linked to CT_VIPHAM via (NAM_HOC, HOC_KY); deleting a referenced semester is disallowed."""
    academic_year, term = semester_key(academic_year, term)
    with db.transaction() as cursor:
        cursor.execute("SELECT COUNT(*) FROM dbo.CT_VIPHAM WHERE NAM_HOC = ? AND HOC_KY = ?", academic_year, term)
        count = cursor.fetchone()[0]
        if count > 0:
            raise conflict(f"Không thể xóa học kỳ vì đã có {count} chi tiết vi phạm liên kết trong hệ thống.")
        cursor.execute("DELETE FROM dbo.HOCKY WHERE NAM_HOC = ? AND HOC_KY = ?", academic_year, term)
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy học kỳ.")


def delete_area(db: Database, area_id: int) -> None:
    with db.transaction() as cursor:
        cursor.execute("SELECT COUNT(*) FROM dbo.CAMERA WHERE KHU_VUC_ID = ?", area_id)
        cam_count = cursor.fetchone()[0]
        if cam_count > 0:
            raise conflict(f"Không thể xóa khu vực vì đang có {cam_count} camera trực thuộc. Vui lòng chuyển hoặc xóa camera trước.")
        cursor.execute(
            "SELECT (SELECT COUNT(*) FROM dbo.ANH_VIPHAM a INNER JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID WHERE c.KHU_VUC_ID = ?) + "
            "(SELECT COUNT(*) FROM dbo.CT_VIPHAM WHERE KHU_VUC_ID = ?)", area_id, area_id
        )
        v_count = cursor.fetchone()[0]
        if v_count > 0:
            raise conflict(f"Khu vực này đã có {v_count} bản ghi vi phạm trong hệ thống. Để bảo toàn lịch sử vi phạm, vui lòng chuyển sang trạng thái 'Ngừng sử dụng' thay vì xóa vĩnh viễn.")
        cursor.execute("DELETE FROM dbo.KHU_VUC WHERE ID = ?", area_id)
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy khu vực.")


def delete_camera(db: Database, camera_id: int) -> None:
    with db.transaction() as cursor:
        cursor.execute("SELECT COUNT(*) FROM dbo.ANH_VIPHAM WHERE CAMERA_ID = ?", camera_id)
        v_count = cursor.fetchone()[0]
        if v_count > 0:
            raise conflict(f"Không thể xóa camera vì đã có {v_count} bản ghi vi phạm liên kết. Để bảo toàn lịch sử vi phạm, vui lòng chuyển sang trạng thái 'Ngừng sử dụng' thay vì xóa vĩnh viễn.")
        cursor.execute("DELETE FROM dbo.CAMERA WHERE ID = ?", camera_id)
        if cursor.rowcount == 0:
            raise not_found("Không tìm thấy camera.")
