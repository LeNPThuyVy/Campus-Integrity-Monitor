"""Dashboard, statistics and report export (Excel/PDF)."""
from __future__ import annotations

from datetime import date

from fastapi import APIRouter, Depends, Query
from fastapi.responses import Response

from api.database import Database
from api.dependencies import CurrentUser, get_db, get_settings_dep, require_roles
from api.exporters import build_pdf, build_xlsx
from api.repositories import reports
from api.settings import Settings

router = APIRouter(prefix="/api/v1", tags=["Thống kê & báo cáo"])

PERIOD_PATTERN = "^(day|week|month|semester|custom)$"


def _range(db: Database, settings: Settings, period: str, anchor: date | None, month: str | None,
           semester_id: int | None, date_from: date | None, date_to: date | None) -> reports.ReportRange:
    return reports.resolve_range(db, settings.now_local().date(), period, anchor, month, semester_id, date_from,
                                 date_to)


@router.get("/dashboard", summary="Số liệu tổng quan: hôm nay/tuần/tháng/học kỳ + thống kê theo bộ lọc")
def dashboard(
    period: str = Query(default="month", pattern=PERIOD_PATTERN),
    anchor: date | None = None,
    month: str | None = Query(default=None, pattern=r"^\d{4}-\d{2}$"),
    semester_id: int | None = Query(default=None, ge=1),
    date_from: date | None = None,
    date_to: date | None = None,
    area_id: int | None = Query(default=None, ge=1),
    _: CurrentUser = Depends(require_roles()),
    db: Database = Depends(get_db),
    settings: Settings = Depends(get_settings_dep),
):
    report_range = _range(db, settings, period, anchor, month, semester_id, date_from, date_to)
    return {"data": reports.dashboard(db, settings.now_local().date(), report_range, area_id)}


@router.get("/reports/summary", summary="Báo cáo thống kê theo tuần/tháng/học kỳ/khoảng tùy chọn")
def report_summary(
    period: str = Query(default="week", pattern=PERIOD_PATTERN),
    anchor: date | None = None,
    month: str | None = Query(default=None, pattern=r"^\d{4}-\d{2}$"),
    semester_id: int | None = Query(default=None, ge=1),
    date_from: date | None = None,
    date_to: date | None = None,
    area_id: int | None = Query(default=None, ge=1),
    camera_id: int | None = Query(default=None, ge=1),
    type_id: int | None = Query(default=None, ge=1),
    _: CurrentUser = Depends(require_roles()),
    db: Database = Depends(get_db),
    settings: Settings = Depends(get_settings_dep),
):
    report_range = _range(db, settings, period, anchor, month, semester_id, date_from, date_to)
    filters = reports.report_filters(report_range, area_id, camera_id, type_id)
    return {"data": {
        "range": {"period": report_range.period, "from": report_range.date_from, "to": report_range.date_to,
                  "label": report_range.label},
        "filters": [{"label": label, "value": value} for label, value in reports.describe_filters(db, filters)],
        "summary": reports.summarize(db, filters),
    }}


@router.get("/reports/export", summary="Xuất báo cáo Excel (.xlsx) hoặc PDF")
def report_export(
    format: str = Query(pattern="^(xlsx|pdf)$"),
    period: str = Query(default="week", pattern=PERIOD_PATTERN),
    anchor: date | None = None,
    month: str | None = Query(default=None, pattern=r"^\d{4}-\d{2}$"),
    semester_id: int | None = Query(default=None, ge=1),
    date_from: date | None = None,
    date_to: date | None = None,
    area_id: int | None = Query(default=None, ge=1),
    camera_id: int | None = Query(default=None, ge=1),
    type_id: int | None = Query(default=None, ge=1),
    user: CurrentUser = Depends(require_roles()),
    db: Database = Depends(get_db),
    settings: Settings = Depends(get_settings_dep),
):
    report_range = _range(db, settings, period, anchor, month, semester_id, date_from, date_to)
    filters = reports.report_filters(report_range, area_id, camera_id, type_id)
    summary = reports.summarize(db, filters)
    description = reports.describe_filters(db, filters)
    description.insert(0, ("Kỳ báo cáo", report_range.label))
    description.append(("Người lập", user.full_name))
    details = reports.detail_rows(db, filters)
    title = "BÁO CÁO THỐNG KÊ VI PHẠM TÁC PHONG SINH VIÊN"
    generated_at = settings.now_local()
    stamp = f"{report_range.date_from:%Y%m%d}-{report_range.date_to:%Y%m%d}"
    if format == "xlsx":
        content = build_xlsx(title, generated_at, description, summary, details)
        media_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    else:
        content = build_pdf(title, generated_at, description, summary, details, settings.pdf_font_path)
        media_type = "application/pdf"
    return Response(content=content, media_type=media_type, headers={
        "Content-Disposition": f'attachment; filename="bao-cao-vi-pham-{stamp}.{format}"',
        "Cache-Control": "no-store",
    })
