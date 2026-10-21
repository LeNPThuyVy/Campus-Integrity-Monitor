"""Dashboard, statistics and report export (Excel/PDF with AI Analysis)."""
from __future__ import annotations

import logging
import os
from datetime import date
from pathlib import Path

from fastapi import APIRouter, Depends, Query
from fastapi.responses import Response

from api.database import Database
from api.dependencies import CurrentUser, get_db, get_settings_dep, require_roles
from api.exporters import build_pdf, build_xlsx
from api.repositories import reports
from api.settings import Settings
from api.v1_schemas import AiPdfReportRequest
from ai.reporting.stats_prompt_builder import StatsPromptBuilder

logger = logging.getLogger(__name__)
router = APIRouter(prefix="/api/v1", tags=["Thống kê & báo cáo"])

PERIOD_PATTERN = "^(day|week|month|semester|custom)$"


def _get_prompt_builder() -> StatsPromptBuilder:
    prompt_path = Path(__file__).resolve().parent.parent.parent / "ai" / "reporting" / "prompt" / "report_stats_prompt.yaml"
    return StatsPromptBuilder(prompt_path)


def _generate_ai_analysis(range_label: str, filters_desc: list[dict[str, str]], summary: dict, details: list) -> str:
    prompt_builder = _get_prompt_builder()
    api_key = os.getenv("GEMINI_API_KEY", "")
    if not api_key:
        from dotenv import load_dotenv
        env_reporting = Path(__file__).resolve().parent.parent.parent / "ai" / "reporting" / ".env"
        if env_reporting.exists():
            load_dotenv(env_reporting)
            api_key = os.getenv("GEMINI_API_KEY", "")

    if api_key:
        try:
            from ai.reporting.gemini_service import GeminiService
            preferred_model = os.getenv("GEMINI_MODEL_NAME") or os.getenv("GEMINI_MODEL") or "gemini-3-flash-preview"
            candidate_models = [
                preferred_model,
                "gemini-3-flash-preview",
                "gemini-3.5-flash-lite",
                "gemini-3.6-flash",
                "gemini-3.5-flash",
                "gemini-3.8-flash",
            ]

            seen = set()
            models_to_try = [m for m in candidate_models if not (m in seen or seen.add(m))]
            prompt = prompt_builder.build_from_sql_summary(range_label, filters_desc, summary, details)

            for model_name in models_to_try:
                try:
                    logger.info("Đang gọi mô hình Gemini: %s để phân tích báo cáo...", model_name)
                    llm = GeminiService(api_key_env="GEMINI_API_KEY", model=model_name)
                    text = llm.generate(prompt)
                    if text and len(text.strip()) > 30:
                        logger.info("Mô hình Gemini %s đã tạo phân tích thành công (%d ký tự)", model_name, len(text))
                        return prompt_builder.clean_analysis(text)
                except Exception as model_err:
                    logger.warning("Thử mô hình %s thất bại: %s. Đang chuyển sang model tiếp theo...", model_name, model_err)
                    continue
        except Exception as exc:
            logger.warning("Không thể gọi Gemini API phân tích báo cáo: %s. Chuyển sang fallback.", exc)
    return prompt_builder.clean_analysis(prompt_builder.build_fallback_analysis(range_label, summary))



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


@router.get("/reports/export", summary="Xuất báo cáo Excel (.xlsx) hoặc PDF tích hợp phân tích AI")
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
    title = "BÁO CÁO GIÁM SÁT TÁC PHONG"
    generated_at = settings.now_local()
    stamp_from = f"{report_range.date_from:%d_%m}"
    stamp_to = f"{report_range.date_to:%d_%m_%Y}"
    fn_prefix = f"Bao_cao_Giam_sat_Tac_phong_Sinh_vien_({stamp_from}-{stamp_to})"
    if format == "xlsx":
        content = build_xlsx(title, generated_at, description, summary, details)
        media_type = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
    else:
        filters_dict_list = [{"label": k, "value": v} for k, v in description]
        analysis_text = _generate_ai_analysis(report_range.label, filters_dict_list, summary, details)
        content = build_pdf(title, generated_at, description, summary, details, settings.pdf_font_path,
                            analysis_text=analysis_text)
        media_type = "application/pdf"
    return Response(content=content, media_type=media_type, headers={
        "Content-Disposition": f'attachment; filename="{fn_prefix}.{format}"',
        "Cache-Control": "no-store",
    })


@router.post("/reports/ai-pdf", summary="Web gửi dữ liệu thống kê & danh sách sự kiện qua API để AI viết báo cáo và xuất PDF chuẩn")
def report_ai_pdf(
    body: AiPdfReportRequest,
    settings: Settings = Depends(get_settings_dep),
):
    generated_at = settings.now_local()
    range_text = body.range_label or body.date_range or body.period or "Kỳ hiện tại"
    if isinstance(body.filters, dict):
        filters_list = [{"label": str(k), "value": str(v)} for k, v in body.filters.items()]
    else:
        filters_list = [{"label": str(item.get("label", "")), "value": str(item.get("value", ""))} for item in body.filters]

    filters_tuples = [(item["label"], item["value"]) for item in filters_list]
    if not any(k.lower() == "kỳ báo cáo" for k, _ in filters_tuples):
        filters_tuples.insert(0, ("Kỳ báo cáo", range_text))
    if not any(k.lower() == "người lập" for k, _ in filters_tuples):
        filters_tuples.append(("Người lập", body.author))

    analysis_text = _generate_ai_analysis(range_text, filters_list, body.summary, body.details)
    content = build_pdf(
        title=body.title,
        generated_at=generated_at,
        filters=filters_tuples,
        summary=body.summary,
        details=body.details,
        font_path=settings.pdf_font_path,
        analysis_text=analysis_text,
    )
    stamp = generated_at.strftime("%Y%m%d_%H%M%S")
    return Response(content=content, media_type="application/pdf", headers={
        "Content-Disposition": f'attachment; filename="Bao_cao_Giam_sat_Tac_phong_Sinh_vien_{stamp}.pdf"',
        "Cache-Control": "no-store",
    })
