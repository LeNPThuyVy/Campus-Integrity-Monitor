"""Report export to Excel (openpyxl) and PDF (fpdf2 with a Unicode TrueType font)."""
from __future__ import annotations

import io
from datetime import datetime
from pathlib import Path
from typing import Any

from api.errors import ApiError

PROCESSING_LABELS = {"MOI": "Mới", "DA_XEM": "Đã xem", "DA_XU_LY": "Đã xử lý", "BO_QUA": "Bỏ qua"}
EVIDENCE_LABELS = {"AVAILABLE": "Còn ảnh", "DELETED": "Đã xóa (hết hạn)", "MISSING": "Mất tệp", "NONE": "Không có"}

_FONT_CANDIDATES = [
    (Path("C:/Windows/Fonts/arial.ttf"), Path("C:/Windows/Fonts/arialbd.ttf")),
    (Path("C:/Windows/Fonts/segoeui.ttf"), Path("C:/Windows/Fonts/segoeuib.ttf")),
    (Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"), Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf")),
]


def _excel_safe(value: Any) -> Any:
    """Prevent CSV/Excel formula injection for text cells."""
    if isinstance(value, str) and value[:1] in ("=", "+", "-", "@"):
        return "'" + value
    return value


def build_xlsx(title: str, generated_at: datetime, filters: list[tuple[str, str]], summary: dict[str, Any],
               details: list[dict[str, Any]]) -> bytes:
    from openpyxl import Workbook
    from openpyxl.styles import Alignment, Font, PatternFill
    from openpyxl.utils import get_column_letter

    workbook = Workbook()
    header_font = Font(bold=True, color="FFFFFF")
    header_fill = PatternFill("solid", fgColor="1E3A8A")

    def write_table(sheet, start_row: int, headers: list[str], rows: list[list[Any]]) -> int:
        for column, header in enumerate(headers, start=1):
            cell = sheet.cell(row=start_row, column=column, value=header)
            cell.font = header_font
            cell.fill = header_fill
            cell.alignment = Alignment(horizontal="center")
        for offset, row in enumerate(rows, start=1):
            for column, value in enumerate(row, start=1):
                sheet.cell(row=start_row + offset, column=column, value=_excel_safe(value))
        for column in range(1, len(headers) + 1):
            letter = get_column_letter(column)
            width = max([len(str(headers[column - 1]))] +
                        [len(str(row[column - 1])) for row in rows if column - 1 < len(row)]) + 2
            sheet.column_dimensions[letter].width = min(max(sheet.column_dimensions[letter].width or 0, width), 60)
        return start_row + len(rows) + 2

    overview = workbook.active
    overview.title = "Tổng hợp"
    overview["A1"] = title
    overview["A1"].font = Font(bold=True, size=14)
    overview["A2"] = f"Thời điểm lập báo cáo: {generated_at:%d/%m/%Y %H:%M}"
    row = 4
    for label, value in filters:
        overview.cell(row=row, column=1, value=label).font = Font(bold=True)
        overview.cell(row=row, column=2, value=_excel_safe(value))
        row += 1
    overview.cell(row=row, column=1, value="Tổng số vi phạm").font = Font(bold=True)
    overview.cell(row=row, column=2, value=summary["total"])
    row += 2
    row = write_table(overview, row, ["Loại vi phạm", "Số lượng"],
                      [[item["name"], item["count"]] for item in summary["by_type"]])
    write_table(overview, row, ["Khu vực - khung giờ nhiều vi phạm", "Số lượng"],
                [[item["label"], item["count"]] for item in summary["hotspots"]])

    sheets = [
        ("Theo ngày", ["Ngày", "Số lượng"], [[item["date"].strftime("%d/%m/%Y"), item["count"]]
                                              for item in summary["by_day"]]),
        ("Theo khung giờ", ["Khung giờ", "Số lượng"], [[item["label"], item["count"]] for item in summary["by_hour"]]),
        ("Theo thứ", ["Thứ", "Số lượng"], [[item["name"], item["count"]] for item in summary["by_weekday"]]),
        ("Theo khu vực", ["Mã khu vực", "Khu vực", "Số lượng"],
         [[item["code"], item["name"], item["count"]] for item in summary["by_area"]]),
        ("Theo camera", ["Mã camera", "Camera", "Khu vực", "Số lượng"],
         [[item["code"], item["name"], item["area_name"], item["count"]] for item in summary["by_camera"]]),
        ("Chi tiết", ["Mã bản ghi", "Thời điểm", "Loại vi phạm", "Khu vực", "Camera", "Trạng thái xử lý", "Ảnh"],
         [[d["ID"], d["THOI_DIEM"].strftime("%d/%m/%Y %H:%M:%S"), d["TEN_LOI"], d["TEN_KHU_VUC"],
           f"{d['MA_CAMERA']} - {d['TEN_CAMERA']}", PROCESSING_LABELS.get(d["TRANG_THAI_XU_LY"], d["TRANG_THAI_XU_LY"]),
           EVIDENCE_LABELS.get(d["ANH_TRANG_THAI"], d["ANH_TRANG_THAI"])] for d in details]),
    ]
    for name, headers, rows in sheets:
        sheet = workbook.create_sheet(name)
        write_table(sheet, 1, headers, rows)
        sheet.freeze_panes = "A2"

    buffer = io.BytesIO()
    workbook.save(buffer)
    return buffer.getvalue()


def _find_fonts(configured: str | None) -> tuple[Path, Path | None]:
    if configured:
        path = Path(configured)
        if path.is_file():
            bold = path.with_name(path.stem + "bd" + path.suffix)
            return path, bold if bold.is_file() else None
    for regular, bold in _FONT_CANDIDATES:
        if regular.is_file():
            return regular, bold if bold.is_file() else None
    raise ApiError(503, "pdf_font_missing",
                   "Máy chủ chưa có font Unicode để xuất PDF tiếng Việt. Cấu hình CIM_PDF_FONT_PATH tới tệp .ttf.")


def build_pdf(title: str, generated_at: datetime, filters: list[tuple[str, str]], summary: dict[str, Any],
              details: list[dict[str, Any]], font_path: str | None, max_detail_rows: int = 300) -> bytes:
    from fpdf import FPDF

    regular, bold = _find_fonts(font_path)
    pdf = FPDF(orientation="P", unit="mm", format="A4")
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_font("ReportFont", "", str(regular))
    pdf.add_font("ReportFont", "B", str(bold or regular))
    pdf.add_page()
    width = pdf.w - pdf.l_margin - pdf.r_margin

    pdf.set_font("ReportFont", "B", 15)
    pdf.multi_cell(width, 8, title, align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "", 9)
    pdf.cell(width, 6, f"Thời điểm lập báo cáo: {generated_at:%d/%m/%Y %H:%M}", align="C",
             new_x="LMARGIN", new_y="NEXT")
    pdf.ln(3)

    pdf.set_font("ReportFont", "B", 11)
    pdf.cell(width, 7, "Bộ lọc", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "", 10)
    for label, value in filters:
        pdf.cell(width, 6, f"• {label}: {value}", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "B", 11)
    pdf.cell(width, 8, f"Tổng số vi phạm ghi nhận: {summary['total']}", new_x="LMARGIN", new_y="NEXT")

    def table(heading: str, headers: list[str], rows: list[list[Any]], widths: list[float]):
        pdf.ln(2)
        pdf.set_font("ReportFont", "B", 11)
        pdf.cell(width, 7, heading, new_x="LMARGIN", new_y="NEXT")
        pdf.set_font("ReportFont", "B", 9)
        pdf.set_fill_color(30, 58, 138)
        pdf.set_text_color(255, 255, 255)
        for header, column_width in zip(headers, widths):
            pdf.cell(column_width, 7, header, border=1, align="C", fill=True)
        pdf.ln()
        pdf.set_text_color(0, 0, 0)
        pdf.set_font("ReportFont", "", 9)
        if not rows:
            pdf.cell(sum(widths), 7, "Không có dữ liệu", border=1, align="C", new_x="LMARGIN", new_y="NEXT")
            return
        for row in rows:
            for value, column_width in zip(row, widths):
                text = str(value)
                while pdf.get_string_width(text) > column_width - 2 and len(text) > 3:
                    text = text[:-4] + "…"
                pdf.cell(column_width, 6, text, border=1)
            pdf.ln()

    table("Theo loại vi phạm", ["Loại vi phạm", "Số lượng"],
          [[item["name"], item["count"]] for item in summary["by_type"]], [width - 30, 30])
    table("Theo khu vực", ["Khu vực", "Số lượng"],
          [[item["name"], item["count"]] for item in summary["by_area"]], [width - 30, 30])
    table("Khu vực và khung giờ có nhiều vi phạm", ["Khu vực • khung giờ", "Số lượng"],
          [[item["label"], item["count"]] for item in summary["hotspots"]], [width - 30, 30])
    busy_hours = [item for item in summary["by_hour"] if item["count"] > 0]
    table("Theo khung giờ", ["Khung giờ", "Số lượng"], [[item["label"], item["count"]] for item in busy_hours],
          [width - 30, 30])
    busy_days = [item for item in summary["by_day"] if item["count"] > 0]
    table("Theo ngày (chỉ ngày có vi phạm)", ["Ngày", "Số lượng"],
          [[item["date"].strftime("%d/%m/%Y"), item["count"]] for item in busy_days], [width - 30, 30])

    shown = details[:max_detail_rows]
    table(f"Chi tiết bản ghi ({len(shown)}/{len(details)} bản ghi mới nhất)",
          ["Mã", "Thời điểm", "Loại vi phạm", "Khu vực", "Camera"],
          [[d["ID"], d["THOI_DIEM"].strftime("%d/%m/%Y %H:%M"), d["TEN_LOI"], d["TEN_KHU_VUC"], d["MA_CAMERA"]]
           for d in shown],
          [16, 32, width - 16 - 32 - 40 - 30, 40, 30])
    pdf.ln(4)
    pdf.set_font("ReportFont", "", 8)
    pdf.multi_cell(width, 4.5,
                   "Ghi chú: Bản ghi do hệ thống AI tạo không chứa danh tính sinh viên. Việc đối chiếu danh tính và "
                   "xử lý điểm rèn luyện do Phòng CTSV thực hiện bên ngoài hệ thống.",
                   new_x="LMARGIN", new_y="NEXT")
    return bytes(pdf.output())
