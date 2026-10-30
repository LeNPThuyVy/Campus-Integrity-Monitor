"""Statistics for the dashboard and reports. All numbers come from PHAT_HIEN_VIPHAM in SQL Server."""
from __future__ import annotations

from dataclasses import dataclass
from datetime import date, datetime, timedelta
from typing import Any

from api.database import Database
from api.errors import validation_error
from api.repositories import facilities
from api.repositories.violations import ViolationFilters, list_violation_types

PERIODS = ("day", "week", "month", "semester", "custom")
MAX_RANGE_DAYS = 400
WEEKDAY_NAMES = ["Thứ Hai", "Thứ Ba", "Thứ Tư", "Thứ Năm", "Thứ Sáu", "Thứ Bảy", "Chủ Nhật"]


@dataclass(frozen=True)
class ReportRange:
    period: str
    date_from: date
    date_to: date
    label: str
    semester: dict[str, Any] | None = None


def resolve_range(db: Database, today: date, period: str, anchor: date | None = None, month: str | None = None,
                  semester_id: int | None = None, date_from: date | None = None,
                  date_to: date | None = None) -> ReportRange:
    if period not in PERIODS:
        raise validation_error("Kỳ báo cáo phải là day, week, month, semester hoặc custom.")
    anchor = anchor or today
    if period == "day":
        return ReportRange("day", anchor, anchor, f"Ngày {anchor:%d/%m/%Y}")
    if period == "week":
        start = anchor - timedelta(days=anchor.weekday())
        end = start + timedelta(days=6)
        return ReportRange("week", start, end, f"Tuần {start:%d/%m} - {end:%d/%m/%Y}")
    if period == "month":
        if month:
            try:
                year, month_number = (int(part) for part in month.split("-"))
                start = date(year, month_number, 1)
            except (ValueError, TypeError) as exc:
                raise validation_error("Tháng phải có dạng YYYY-MM.") from exc
        else:
            start = anchor.replace(day=1)
        end = (start.replace(day=28) + timedelta(days=4)).replace(day=1) - timedelta(days=1)
        return ReportRange("month", start, end, f"Tháng {start:%m/%Y}")
    if period == "semester":
        semester = (facilities.get_semester(db, semester_id) if semester_id is not None
                    else facilities.current_semester(db, anchor))
        if semester is None:
            raise validation_error("Chưa có học kỳ chứa ngày đã chọn. Hãy khai báo học kỳ trong mục Camera & khu vực.")
        return ReportRange("semester", semester["start_date"], semester["end_date"],
                           f"{semester['name']} ({semester['start_date']:%d/%m/%Y} - {semester['end_date']:%d/%m/%Y})",
                           semester)
    if date_from is None or date_to is None:
        raise validation_error("Báo cáo tùy chọn cần cả ngày bắt đầu và ngày kết thúc.")
    if date_to < date_from:
        raise validation_error("Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.")
    if (date_to - date_from).days > MAX_RANGE_DAYS:
        raise validation_error(f"Khoảng thời gian báo cáo tối đa {MAX_RANGE_DAYS} ngày.")
    return ReportRange("custom", date_from, date_to, f"Từ {date_from:%d/%m/%Y} đến {date_to:%d/%m/%Y}")


def _grouped(cursor, select: str, group: str, where: str, params: list[Any], order: str) -> list[tuple]:
    cursor.execute(
        f"SELECT {select}, COUNT_BIG(*) AS SO_LUONG FROM dbo.PHAT_HIEN_VIPHAM b "
        "INNER JOIN dbo.LOI_VIPHAM l ON l.ID = b.LOI_ID "
        "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
        "LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID "
        "LEFT JOIN dbo.KHU_VUC k ON k.ID = c.KHU_VUC_ID "
        f"{where} GROUP BY {group} ORDER BY {order}",
        *params,
    )
    return cursor.fetchall()


def summarize(db: Database, filters: ViolationFilters) -> dict[str, Any]:
    if filters.date_from is None or filters.date_to is None:
        raise validation_error("Cần khoảng thời gian để thống kê.")
    where, params = filters.where_clause()
    with db.transaction() as cursor:
        cursor.execute(
            "SELECT COUNT_BIG(*) FROM dbo.PHAT_HIEN_VIPHAM b "
            "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
            f"LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID {where}",
            *params,
        )
        total = int(cursor.fetchone()[0])
        by_type_rows = _grouped(cursor, "l.ID, l.MA_LOI, l.TEN_LOI", "l.ID, l.MA_LOI, l.TEN_LOI", where, params, "l.ID")
        by_day_rows = _grouped(cursor, "CAST(b.THOI_DIEM AS DATE) AS NGAY", "CAST(b.THOI_DIEM AS DATE)", where, params,
                               "NGAY")
        by_hour_rows = _grouped(cursor, "DATEPART(HOUR, b.THOI_DIEM) AS GIO", "DATEPART(HOUR, b.THOI_DIEM)", where,
                                params, "GIO")
        by_weekday_rows = _grouped(cursor, "(DATEDIFF(DAY, '19000101', b.THOI_DIEM) % 7) AS THU",
                                   "(DATEDIFF(DAY, '19000101', b.THOI_DIEM) % 7)", where, params, "THU")
        by_area_rows = _grouped(cursor, "k.ID, k.MA_KHU_VUC, k.TEN_KHU_VUC", "k.ID, k.MA_KHU_VUC, k.TEN_KHU_VUC",
                                where, params, "SO_LUONG DESC, k.MA_KHU_VUC")
        by_camera_rows = _grouped(cursor, "c.ID, c.MA_CAMERA, c.TEN_CAMERA, k.TEN_KHU_VUC",
                                  "c.ID, c.MA_CAMERA, c.TEN_CAMERA, k.TEN_KHU_VUC", where, params,
                                  "SO_LUONG DESC, c.MA_CAMERA")
        hotspot_rows = _grouped(cursor, "TOP (10) k.ID, k.TEN_KHU_VUC, DATEPART(HOUR, b.THOI_DIEM) AS GIO",
                                "k.ID, k.TEN_KHU_VUC, DATEPART(HOUR, b.THOI_DIEM)", where, params,
                                "SO_LUONG DESC, k.TEN_KHU_VUC, GIO")
        by_status_rows = _grouped(cursor, "b.TINH_TRANG_XU_LY", "b.TINH_TRANG_XU_LY", where, params,
                                  "b.TINH_TRANG_XU_LY")

    type_counts = {row[0]: int(row[3]) for row in by_type_rows}
    by_type = [{"id": t["id"], "code": t["code"], "name": t["name"], "count": type_counts.get(t["id"], 0)}
               for t in list_violation_types(db)
               if filters.type_id is None or t["id"] == filters.type_id]

    day_counts = {row[0]: int(row[1]) for row in by_day_rows}
    by_day = []
    current = filters.date_from
    while current <= filters.date_to:
        by_day.append({"date": current, "count": day_counts.get(current, 0)})
        current += timedelta(days=1)

    hour_counts = {int(row[0]): int(row[1]) for row in by_hour_rows}
    by_hour = [{"hour": hour, "label": f"{hour:02d}:00-{hour:02d}:59", "count": hour_counts.get(hour, 0)}
               for hour in range(24)]
    weekday_counts = {int(row[0]): int(row[1]) for row in by_weekday_rows}  # 0 = Monday (1900-01-01 was Monday)
    by_weekday = [{"weekday": index, "name": WEEKDAY_NAMES[index], "count": weekday_counts.get(index, 0)}
                  for index in range(7)]

    return {
        "total": total,
        "by_type": by_type,
        "by_day": by_day,
        "by_hour": by_hour,
        "by_weekday": by_weekday,
        "by_area": [{"id": r[0], "code": r[1], "name": r[2], "count": int(r[3])} for r in by_area_rows if r[0] is not None],
        "by_camera": [{"id": r[0], "code": r[1], "name": r[2], "area_name": r[3], "count": int(r[4])}
                      for r in by_camera_rows if r[0] is not None],
        "hotspots": [{"area_id": r[0], "area_name": r[1], "hour": int(r[2]),
                      "label": f"{r[1]} • {int(r[2]):02d}:00-{int(r[2]):02d}:59", "count": int(r[3])}
                     for r in hotspot_rows if r[0] is not None],
        "by_status": {row[0]: int(row[1]) for row in by_status_rows if row[0] is not None},
        "peak_hour": max(by_hour, key=lambda item: item["count"]) if total else None,
        "peak_area": (lambda rows: rows[0] if rows else None)(
            [{"id": r[0], "name": r[2], "count": int(r[3])} for r in by_area_rows if r[0] is not None]),
    }


def count_in_range(db: Database, date_from: date, date_to: date, area_id: int | None) -> int:
    where, params = ViolationFilters(date_from=date_from, date_to=date_to, area_id=area_id).where_clause()
    row = db.fetch_one(
        "SELECT COUNT_BIG(*) AS N FROM dbo.PHAT_HIEN_VIPHAM b "
        "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
        f"LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID {where}",
        params,
    )
    return int(row["N"]) if row else 0


def dashboard(db: Database, today: date, report_range: ReportRange, area_id: int | None) -> dict[str, Any]:
    week_start = today - timedelta(days=today.weekday())
    month_start = today.replace(day=1)
    semester = facilities.current_semester(db, today)
    counters = {
        "today": count_in_range(db, today, today, area_id),
        "week": count_in_range(db, week_start, today, area_id),
        "month": count_in_range(db, month_start, today, area_id),
        "semester": count_in_range(db, semester["start_date"], min(today, semester["end_date"]), area_id)
        if semester else None,
    }
    filters = ViolationFilters(date_from=report_range.date_from, date_to=report_range.date_to, area_id=area_id)
    return {
        "today": today,
        "counters": counters,
        "current_semester": semester,
        "range": {"period": report_range.period, "from": report_range.date_from, "to": report_range.date_to,
                  "label": report_range.label},
        "summary": summarize(db, filters),
    }


def report_filters(report_range: ReportRange, area_id: int | None, camera_id: int | None,
                   type_id: int | None) -> ViolationFilters:
    return ViolationFilters(date_from=report_range.date_from, date_to=report_range.date_to, area_id=area_id,
                            camera_id=camera_id, type_id=type_id)


def describe_filters(db: Database, filters: ViolationFilters) -> list[tuple[str, str]]:
    """Human-readable filter description for exported reports."""
    description = [("Khoảng thời gian", f"{filters.date_from:%d/%m/%Y} - {filters.date_to:%d/%m/%Y}")]
    if filters.area_id is not None:
        description.append(("Khu vực", facilities.get_area(db, filters.area_id)["name"]))
    else:
        description.append(("Khu vực", "Tất cả"))
    if filters.camera_id is not None:
        camera = facilities.get_camera(db, filters.camera_id, include_sensitive=False)
        description.append(("Camera", f"{camera['code']} - {camera['name']}"))
    else:
        description.append(("Camera", "Tất cả"))
    if filters.type_id is not None:
        names = {t["id"]: t["name"] for t in list_violation_types(db)}
        description.append(("Loại vi phạm", names.get(filters.type_id, str(filters.type_id))))
    else:
        description.append(("Loại vi phạm", "Tất cả"))
    return description


def detail_rows(db: Database, filters: ViolationFilters, limit: int = 5000) -> list[dict[str, Any]]:
    where, params = filters.where_clause()
    return db.fetch_all(
        "SELECT TOP (?) b.ID, b.THOI_DIEM, l.TEN_LOI, k.TEN_KHU_VUC, c.MA_CAMERA, c.TEN_CAMERA, "
        "b.TINH_TRANG_XU_LY AS TRANG_THAI_XU_LY, "
        "CASE WHEN a.ANH_KHOA IS NOT NULL THEN 'AVAILABLE' ELSE 'NONE' END AS ANH_TRANG_THAI "
        "FROM dbo.PHAT_HIEN_VIPHAM b "
        "INNER JOIN dbo.LOI_VIPHAM l ON l.ID = b.LOI_ID "
        "LEFT JOIN dbo.ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID "
        "LEFT JOIN dbo.CAMERA c ON c.ID = a.CAMERA_ID "
        "LEFT JOIN dbo.KHU_VUC k ON k.ID = c.KHU_VUC_ID "
        f"{where} ORDER BY b.THOI_DIEM DESC",
        [limit, *params],
    )
