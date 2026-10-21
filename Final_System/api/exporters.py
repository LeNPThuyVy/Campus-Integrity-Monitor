"""Report export to Excel (openpyxl) and PDF (matplotlib with fallback to fpdf2)."""
from __future__ import annotations

import io
import textwrap
from datetime import datetime
from pathlib import Path
from typing import Any

from api.errors import ApiError

PROCESSING_LABELS = {
    "MOI": "Mới báo động",
    "DA_XEM": "Đã điều Cờ Đỏ",
    "DA_XU_LY": "Đã kiểm tra",
    "BO_QUA": "Đã kiểm tra",
}
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
    overview.cell(row=row, column=2, value=summary.get("total", 0))
    row += 2
    row = write_table(overview, row, ["Loại vi phạm", "Số lượng"],
                      [[item.get("name", ""), item.get("count", 0)] for item in summary.get("by_type", [])])
    write_table(overview, row, ["Khu vực / Phòng học - khung giờ nhiều vi phạm", "Số lượng"],
                [[item.get("label", item.get("area_name", "")), item.get("count", 0)] for item in summary.get("hotspots", [])])

    details_rows = []
    for d in details:
        t_time = d.get("THOI_DIEM") or d.get("occurred_at") or d.get("created_at") or d.get("time")
        time_str = t_time.strftime("%d/%m/%Y %H:%M:%S") if hasattr(t_time, "strftime") else str(t_time or "")
        t_loi = d.get("TEN_LOI") or d.get("type_name") or (d.get("type", {}).get("name") if isinstance(d.get("type"), dict) else "")
        t_kv = d.get("PHONG_HOC") or d.get("location") or d.get("TEN_KHU_VUC") or (d.get("area", {}).get("name") if isinstance(d.get("area"), dict) else "")
        t_cam = f"{d.get('MA_CAMERA', '')} - {d.get('TEN_CAMERA', '')}".strip(" -")
        t_status = d.get("TRANG_THAI_XU_LY") or (d.get("processing", {}).get("status") if isinstance(d.get("processing"), dict) else "DA_XU_LY")
        t_sv = d.get("HOTEN") or d.get("student_name") or d.get("MA_SV") or d.get("student_code") or (d.get("student", {}).get("name") if isinstance(d.get("student"), dict) else "")
        details_rows.append([
            d.get("ID", d.get("id", "")),
            time_str,
            t_sv or "Chưa định danh",
            t_loi,
            t_kv,
            t_cam or "Cờ Đỏ trực tiếp",
            PROCESSING_LABELS.get(t_status, t_status),
        ])

    sheets = [
        ("Theo ngày", ["Ngày", "Số lượng"], [[item["date"].strftime("%d/%m/%Y") if hasattr(item["date"], "strftime") else str(item["date"]), item["count"]]
                                              for item in summary.get("by_day", [])]),
        ("Theo khung giờ", ["Khung giờ", "Số lượng"], [[item.get("label", f"{item.get('hour')}:00"), item.get("count", 0)] for item in summary.get("by_hour", [])]),
        ("Theo thứ", ["Thứ", "Số lượng"], [[item.get("name", ""), item.get("count", 0)] for item in summary.get("by_weekday", [])]),
        ("Theo khu vực", ["Khu vực / Phòng học", "Số lượng"],
         [[item.get("name", item.get("TEN_KHU_VUC", "")), item.get("count", 0)] for item in summary.get("by_area", [])]),
        ("Chi tiết", ["Mã bản ghi", "Thời điểm", "Sinh viên / Đối tượng", "Loại vi phạm", "Khu vực / Phòng học", "Nguồn ghi nhận", "Trạng thái xử lý"],
         details_rows),
    ]
    for name, headers, rows in sheets:
        sheet = workbook.create_sheet(name)
        write_table(sheet, 1, headers, rows)
        sheet.freeze_panes = "A2"

    buffer = io.BytesIO()
    workbook.save(buffer)
    return buffer.getvalue()


def _build_pdf_fpdf2(
    title: str,
    generated_at: datetime,
    filters: list[tuple[str, str]],
    summary: dict[str, Any],
    details: list[dict[str, Any]],
    font_path: str | None,
    max_detail_rows: int,
    analysis_text: str | None,
) -> bytes:
    from fpdf import FPDF

    regular = Path("C:/Windows/Fonts/arial.ttf")
    bold = Path("C:/Windows/Fonts/arialbd.ttf")
    pdf = FPDF(orientation="P", unit="mm", format="A4")
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_font("ReportFont", "", str(regular))
    pdf.add_font("ReportFont", "B", str(bold if bold.is_file() else regular))
    pdf.add_page()
    width = pdf.w - pdf.l_margin - pdf.r_margin

    pdf.set_font("ReportFont", "B", 14)
    pdf.multi_cell(width, 7, title, align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "", 9)
    pdf.cell(width, 6, f"Thời điểm lập báo cáo: {generated_at:%d/%m/%Y %H:%M}", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(3)

    pdf.set_font("ReportFont", "B", 10.5)
    pdf.cell(width, 6, "1. BỘ LỌC VÀ THÔNG SỐ", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "", 9)
    for label, value in filters:
        pdf.cell(width, 5.5, f"  • {label}: {value}", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "B", 10)
    pdf.cell(width, 7, f"  ==> Tổng số vi phạm ghi nhận: {summary.get('total', 0)} trường hợp", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(2)

    if analysis_text:
        pdf.set_fill_color(240, 244, 255)
        pdf.set_font("ReportFont", "B", 11)
        pdf.cell(width, 7, "2. NHẬN XÉT, PHÂN TÍCH & KIẾN NGHỊ ĐIỀU PHỐI (AI GEMINI TỔNG HỢP)", border=1, fill=True, new_x="LMARGIN", new_y="NEXT")
        pdf.ln(2)
        pdf.set_font("ReportFont", "", 9)
        for line in analysis_text.splitlines():
            line_str = line.strip().replace("**", "").replace("###", "").strip()
            if not line_str:
                pdf.ln(1.5)
                continue
            if line_str[0].isdigit() and line_str[1:3] in (". ", "."):
                pdf.set_font("ReportFont", "B", 9.5)
                pdf.multi_cell(width, 6, line_str, new_x="LMARGIN", new_y="NEXT")
                pdf.set_font("ReportFont", "", 9)
            elif line_str.startswith(("-", "*", "•")):
                pdf.cell(5, 5, "•")
                pdf.multi_cell(width - 5, 5, line_str.lstrip("-*• "), new_x="LMARGIN", new_y="NEXT")
            else:
                pdf.multi_cell(width, 5, line_str, new_x="LMARGIN", new_y="NEXT")
        pdf.ln(4)

    return bytes(pdf.output())


def _format_table_cell(val: Any, max_width: int) -> str:
    """Format and wrap text inside table cell to prevent overflow."""
    if val is None:
        return ""
    s = str(val).strip()
    if not s:
        return ""
    return "\n".join(textwrap.wrap(s, width=max_width))


def _fmt_detail_time(d: dict[str, Any]) -> str:
    """Format timestamp into dd/mm/yyyy hh:mm for report tables."""
    t = d.get("THOI_DIEM") or d.get("occurred_at") or d.get("created_at") or d.get("time")
    if hasattr(t, "strftime"):
        return t.strftime("%d/%m/%Y\n%H:%M")
    s = str(t or "").strip()
    if len(s) >= 16:
        clean = s.replace("T", " ")
        parts = clean.split(" ")
        if len(parts) >= 2 and "-" in parts[0]:
            ymd = parts[0].split("-")
            if len(ymd) == 3:
                return f"{ymd[2]}/{ymd[1]}/{ymd[0]}\n{parts[1][:5]}"
        return clean[:16]
    return s


def _render_styled_table(
    ax,
    cell_data: list[list[str]],
    col_widths: list[float],
    bbox: list[float],
    col_alignments: list[str],
    header_bg: str = "#F1F5F9",
    header_color: str = "#0F172A",
    header_font_size: float = 8.5,
    row_font_size: float = 7.5,
    border_color: str = "#94A3B8",
):
    """Render a clean, formatted table in Matplotlib matching official administrative document style."""
    table = ax.table(
        cellText=cell_data,
        colWidths=col_widths,
        bbox=bbox,
        loc="upper left",
    )
    table.auto_set_font_size(False)
    for (r, c), cell in table.get_celld().items():
        cell.set_edgecolor(border_color)
        cell.set_linewidth(0.6)
        align = col_alignments[c] if c < len(col_alignments) else "center"
        if r == 0:
            cell.set_facecolor(header_bg)
            cell.set_text_props(color=header_color, weight="bold", ha="center", va="center", fontsize=header_font_size)
        else:
            bg = "#F8FAFC" if r % 2 == 1 else "#FFFFFF"
            cell.set_facecolor(bg)
            cell.set_text_props(ha=align, va="center", fontsize=row_font_size, color="#0F172A")
    return table


def _build_pdf_fpdf2(
    title: str,
    generated_at: datetime,
    filters: list[tuple[str, str]],
    summary: dict[str, Any],
    details: list[dict[str, Any]],
    font_path: str | None,
    max_detail_rows: int,
    analysis_text: str | None,
) -> bytes:
    from fpdf import FPDF
    from ai.reporting.stats_prompt_builder import StatsPromptBuilder

    class OfficialReportPdf(FPDF):
        def footer(self):
            self.set_y(-12)
            self.set_font("ReportFont", "", 8)
            self.set_text_color(148, 163, 184)
            self.cell(
                0, 8,
                f"Trang {self.page_no()} / {{nb}}  —  Hệ thống Giám sát Tác phong Học đường (Campus Integrity Monitor)",
                align="C"
            )

    regular = Path("C:/Windows/Fonts/arial.ttf")
    bold = Path("C:/Windows/Fonts/arialbd.ttf")
    italic = Path("C:/Windows/Fonts/ariali.ttf")
    pdf = OfficialReportPdf(orientation="P", unit="mm", format="A4")
    pdf.set_left_margin(28)
    pdf.set_right_margin(18)
    pdf.set_auto_page_break(auto=True, margin=16)

    pdf.add_font("ReportFont", "", str(regular))
    pdf.add_font("ReportFont", "B", str(bold if bold.is_file() else regular))
    pdf.add_font("ReportFont", "I", str(italic if italic.is_file() else regular))

    width = pdf.w - pdf.l_margin - pdf.r_margin  # 164mm (210 - 28 - 18)
    total = summary.get("total", 0)
    range_label = next((v for k, v in filters if "kỳ" in k.lower() or "khoảng" in k.lower()), "Kỳ hiện tại")
    author = next((v for k, v in filters if "người" in k.lower()), "Phòng Công tác Sinh viên")

    # === TRANG 1: Quốc hiệu, Tiêu đề, Kính gửi, Đoạn mở đầu & Phân tích I + II ===
    pdf.add_page()

    # Quốc hiệu - Tiêu ngữ (đặt ở góc phải / giữa trang theo chuẩn văn bản hành chính)
    pdf.set_text_color(0, 0, 0)
    pdf.set_x(95)
    pdf.set_font("ReportFont", "B", 10.5)
    pdf.cell(95, 5.0, "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_x(95)
    pdf.set_font("ReportFont", "B", 10.0)
    pdf.cell(95, 4.8, "Độc lập - Tự do - Hạnh phúc", align="C", new_x="LMARGIN", new_y="NEXT")

    # Dòng kẻ ngắn dưới Độc lập - Tự do - Hạnh phúc
    x_center = 142.5
    pdf.line(x_center - 22, pdf.get_y() + 0.8, x_center + 22, pdf.get_y() + 0.8)
    pdf.ln(2.2)

    pdf.set_x(95)
    pdf.set_font("ReportFont", "I", 9.0)
    pdf.cell(95, 4.8, f"TP. Hồ Chí Minh, ngày {generated_at.day:02d} tháng {generated_at.month:02d} năm {generated_at.year}", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(5)

    # Tiêu đề báo cáo
    pdf.set_text_color(30, 58, 138)
    pdf.set_font("ReportFont", "B", 14)
    pdf.multi_cell(width, 7, title.upper(), align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_text_color(0, 0, 0)
    pdf.set_font("ReportFont", "I", 10)
    pdf.multi_cell(width, 5.5, "Kết quả kiểm tra, giám sát công tác thực hiện nội quy, tác phong sinh viên", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.multi_cell(width, 5.5, f"({range_label})", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(4)

    # Kính gửi
    pdf.set_text_color(0, 0, 0)
    pdf.set_font("ReportFont", "B", 10)
    pdf.cell(width, 5.5, "Kính gửi:", new_x="LMARGIN", new_y="NEXT")
    pdf.set_font("ReportFont", "", 9.5)
    pdf.cell(width, 5.0, "  ● Ban Giám hiệu Nhà trường;", new_x="LMARGIN", new_y="NEXT")
    pdf.cell(width, 5.0, "  ● Ban Chủ nhiệm các Khoa/Viện.", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(3)

    # Đoạn mở đầu hành chính
    year = generated_at.year
    intro = (
        f"Thực hiện kế hoạch kiểm tra, rèn luyện nề nếp học đường năm học {year} – {year + 1}, "
        "Phòng Công tác Sinh viên (CTSV) phối hợp cùng Ban Chỉ đạo Đội Cờ đỏ báo cáo tình hình chấp hành quy định "
        "tác phong của sinh viên qua Hệ thống Giám sát Tác phong Học đường (CIM) như sau:"
    )
    pdf.set_text_color(0, 0, 0)
    pdf.multi_cell(width, 5.2, intro, new_x="LMARGIN", new_y="NEXT")
    pdf.ln(3)

    # Làm sạch nội dung AI phân tích, bỏ qua mọi phần mở đầu bị lặp
    cleaned_analysis = StatsPromptBuilder.clean_analysis(analysis_text or "")
    if cleaned_analysis:
        for raw_line in cleaned_analysis.splitlines():
            line = raw_line.strip().replace("**", "").replace("###", "").strip()
            if not line:
                pdf.ln(2)
                continue
            is_roman = line.startswith(("I. ", "II. ", "III. ", "IV. "))
            is_num = (len(line) >= 2 and line[0].isdigit() and line[1] in (".", " ")) or (len(line) >= 3 and line[:2].isdigit() and line[2] in (".", " "))
            is_bullet = line.startswith(("-", "•", "*"))

            if is_roman:
                pdf.ln(2.5)
                pdf.set_text_color(30, 58, 138)
                pdf.set_font("ReportFont", "B", 11)
                pdf.multi_cell(width, 6, line, new_x="LMARGIN", new_y="NEXT")
                pdf.set_text_color(0, 0, 0)
                pdf.set_font("ReportFont", "", 9.5)
            elif is_num:
                pdf.ln(1.5)
                pdf.set_text_color(0, 0, 0)
                pdf.set_font("ReportFont", "B", 10)
                pdf.multi_cell(width, 5.5, line, new_x="LMARGIN", new_y="NEXT")
                pdf.set_font("ReportFont", "", 9.5)
            elif is_bullet:
                pdf.set_text_color(0, 0, 0)
                pdf.cell(5, 5, "-")
                pdf.multi_cell(width - 5, 5, line.lstrip("-•* ").strip(), new_x="LMARGIN", new_y="NEXT")
            else:
                pdf.set_text_color(0, 0, 0)
                pdf.multi_cell(width, 5.2, line, new_x="LMARGIN", new_y="NEXT")

    # === TRANG MỚI: III. BẢNG THỐNG KÊ SỐ LIỆU VI PHẠM & IV. DANH SÁCH CHI TIẾT ===
    pdf.add_page()
    pdf.set_text_color(30, 58, 138)
    pdf.set_font("ReportFont", "B", 11)
    pdf.cell(width, 6, "III. BẢNG THỐNG KÊ SỐ LIỆU VI PHẠM", new_x="LMARGIN", new_y="NEXT")
    pdf.set_text_color(0, 0, 0)
    pdf.ln(1)

    # Bảng 3.1: Cơ cấu loại vi phạm
    pdf.set_font("ReportFont", "B", 9.5)
    pdf.cell(width, 5.5, "Bảng 3.1. Thống kê theo nhóm hành vi vi phạm", new_x="LMARGIN", new_y="NEXT")
    by_type_list = summary.get("by_type", [])
    max_c = max([t.get("count", 0) for t in by_type_list], default=0)

    pdf.set_fill_color(241, 245, 249)
    pdf.set_text_color(0, 0, 0)
    pdf.set_font("ReportFont", "B", 8.5)
    w31 = [12, 80, 22, 23, 27]
    h_cell = 6.2
    for h_txt, col_w in zip(["STT", "Nội dung hành vi vi phạm", "Số lượt", "Tỷ lệ (%)", "Ghi chú"], w31):
        pdf.cell(col_w, h_cell, h_txt, border=1, fill=True, align="C")
    pdf.ln(h_cell)

    pdf.set_font("ReportFont", "", 8.5)
    if by_type_list:
        for idx, t in enumerate(by_type_list, 1):
            c = t.get("count", 0)
            pct = f"{(c / total * 100):.1f}%" if total > 0 else "0.0%"
            note = "Chiếm đa số" if (c == max_c and c > 0) else ""
            pdf.cell(w31[0], h_cell, str(idx), border=1, align="C")
            pdf.cell(w31[1], h_cell, t.get("name", "")[:60], border=1, align="L")
            pdf.cell(w31[2], h_cell, f"{c:02d}" if c < 10 else str(c), border=1, align="C")
            pdf.cell(w31[3], h_cell, pct, border=1, align="C")
            pdf.cell(w31[4], h_cell, note, border=1, align="C")
            pdf.ln(h_cell)
    else:
        pdf.cell(w31[0], h_cell, "—", border=1, align="C")
        pdf.cell(w31[1], h_cell, "Không ghi nhận vi phạm", border=1, align="L")
        pdf.cell(w31[2], h_cell, "00", border=1, align="C")
        pdf.cell(w31[3], h_cell, "0.0%", border=1, align="C")
        pdf.cell(w31[4], h_cell, "", border=1, align="C")
        pdf.ln(h_cell)

    # Dòng Tổng
    pdf.set_font("ReportFont", "B", 8.5)
    pdf.cell(w31[0] + w31[1], h_cell, "Tổng", border=1, align="C")
    pdf.cell(w31[2], h_cell, f"{total:02d}" if total < 10 else str(total), border=1, align="C")
    pdf.cell(w31[3], h_cell, "100%" if total > 0 else "0.0%", border=1, align="C")
    pdf.cell(w31[4], h_cell, "", border=1, align="C")
    pdf.ln(h_cell + 3)

    # Bảng 3.2: Khu vực và khung giờ
    pdf.set_font("ReportFont", "B", 9.5)
    pdf.cell(width, 5.5, "Bảng 3.2. Thống kê theo khu vực và khung giờ ghi nhận", new_x="LMARGIN", new_y="NEXT")
    w32 = [12, 70, 42, 20, 20]
    pdf.set_font("ReportFont", "B", 8.5)
    for h_txt, col_w in zip(["STT", "Khu vực", "Khung giờ ghi nhận", "Số lượt", "Tỷ lệ (%)"], w32):
        pdf.cell(col_w, h_cell, h_txt, border=1, fill=True, align="C")
    pdf.ln(h_cell)

    pdf.set_font("ReportFont", "", 8.5)
    hotspot_list = [h for h in summary.get("hotspots", []) if h.get("count", 0) > 0][:5]
    if total > 0 and not hotspot_list:
        p_area = summary.get("peak_area", {})
        p_area_str = p_area.get("name", "Khu vực chung (Sảnh chính)") if isinstance(p_area, dict) else "Khu vực chung (Sảnh chính)"
        p_hour = summary.get("peak_hour", {})
        p_hour_str = p_hour.get("label", "07:00 – 07:59") if isinstance(p_hour, dict) else "07:00 – 07:59"
        hotspot_list = [{"area_name": p_area_str, "hour_str": p_hour_str, "count": total}]

    if hotspot_list:
        for idx, h in enumerate(hotspot_list, 1):
            lbl = h.get("label", "")
            if "•" in lbl:
                parts = lbl.split("•", 1)
                area_str = parts[0].strip()
                hour_str = parts[1].strip()
            else:
                area_str = h.get("area_name", lbl or "Khu vực chung")
                hour_str = h.get("hour_str", f"{h.get('hour', 7):02d}:00 – {h.get('hour', 7):02d}:59")
            c = h.get("count", 0)
            pct = f"{(c / total * 100):.1f}%" if total > 0 else "0.0%"
            pdf.cell(w32[0], h_cell, str(idx), border=1, align="C")
            pdf.cell(w32[1], h_cell, area_str[:40], border=1, align="L")
            pdf.cell(w32[2], h_cell, hour_str, border=1, align="C")
            pdf.cell(w32[3], h_cell, str(c), border=1, align="C")
            pdf.cell(w32[4], h_cell, pct, border=1, align="C")
            pdf.ln(h_cell)
    else:
        pdf.cell(w32[0], h_cell, "—", border=1, align="C")
        pdf.cell(w32[1], h_cell, "Không ghi nhận điểm nóng", border=1, align="L")
        pdf.cell(w32[2], h_cell, "—", border=1, align="C")
        pdf.cell(w32[3], h_cell, "0", border=1, align="C")
        pdf.cell(w32[4], h_cell, "0.0%", border=1, align="C")
        pdf.ln(h_cell)
    pdf.ln(4)

    # Phần IV: Danh sách sinh viên vi phạm
    pdf.set_text_color(30, 58, 138)
    pdf.set_font("ReportFont", "B", 11)
    pdf.cell(width, 6, "IV. DANH SÁCH SINH VIÊN VI PHẠM GHI NHẬN TRONG KỲ", new_x="LMARGIN", new_y="NEXT")
    pdf.set_text_color(0, 0, 0)
    pdf.ln(1)

    w4 = [12, 25, 22, 35, 50, 20]
    pdf.set_font("ReportFont", "B", 8.0)
    for h_txt, col_w in zip(["Mã BG", "Thời điểm", "MSSV", "Sinh viên", "Hành vi vi phạm", "Vị trí"], w4):
        pdf.cell(col_w, h_cell, h_txt, border=1, fill=True, align="C")
    pdf.ln(h_cell)

    pdf.set_font("ReportFont", "", 8.0)
    detail_items = details[:max_detail_rows]
    if detail_items:
        for d in detail_items:
            rec_id = str(d.get("ID", d.get("id", "")))
            t_str = _fmt_detail_time(d).replace("\n", " ")
            mssv = str(d.get("MSSV") or d.get("MA_SV") or d.get("student_code") or d.get("student_id") or "—").strip()
            sv = str(d.get("HOTEN") or d.get("student_name") or (d.get("student", {}).get("name") if isinstance(d.get("student"), dict) else "") or "Chưa định danh").strip()
            loi = str(d.get("TEN_LOI") or d.get("type_name") or (d.get("type", {}).get("name") if isinstance(d.get("type"), dict) else "") or "").strip()
            kv = str(d.get("PHONG_HOC") or d.get("TEN_KHU_VUC") or d.get("location") or (d.get("area", {}).get("name") if isinstance(d.get("area"), dict) else "") or "Khu vực chung").strip()

            pdf.cell(w4[0], h_cell, rec_id, border=1, align="C")
            pdf.cell(w4[1], h_cell, t_str, border=1, align="C")
            pdf.cell(w4[2], h_cell, mssv, border=1, align="C")
            pdf.cell(w4[3], h_cell, sv[:25], border=1, align="L")
            pdf.cell(w4[4], h_cell, loi[:42], border=1, align="L")
            pdf.cell(w4[5], h_cell, kv[:16], border=1, align="L")
            pdf.ln(h_cell)
    else:
        pdf.cell(sum(w4), h_cell, "Không ghi nhận trường hợp sinh viên vi phạm nào trong kỳ báo cáo này.", border=1, align="C")
        pdf.ln(h_cell)

    pdf.ln(6)

    # Chữ ký người lập báo cáo (nếu sát đáy trang thì tự động thêm trang mới)
    if pdf.get_y() > 240:
        pdf.add_page()

    sig_x = pdf.l_margin + width - 70
    pdf.set_x(sig_x)
    pdf.set_text_color(0, 0, 0)
    pdf.set_font("ReportFont", "B", 10)
    pdf.cell(70, 5.0, "NGƯỜI LẬP BÁO CÁO", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.set_x(sig_x)
    pdf.set_font("ReportFont", "I", 8.5)
    pdf.cell(70, 4.5, "(Ký và ghi rõ họ tên)", align="C", new_x="LMARGIN", new_y="NEXT")

    return bytes(pdf.output())


def _build_pdf_matplotlib(
    title: str,
    generated_at: datetime,
    filters: list[tuple[str, str]],
    summary: dict[str, Any],
    details: list[dict[str, Any]],
    analysis_text: str | None,
) -> bytes:
    import matplotlib
    matplotlib.use("Agg")
    import matplotlib.pyplot as plt
    from matplotlib.backends.backend_pdf import PdfPages
    from ai.reporting.stats_prompt_builder import StatsPromptBuilder

    plt.rcParams["font.sans-serif"] = ["Segoe UI", "Arial", "DejaVu Sans", "Tahoma"]
    plt.rcParams["font.family"] = "sans-serif"

    total = summary.get("total", 0)
    range_label = next((v for k, v in filters if "kỳ" in k.lower() or "khoảng" in k.lower()), "Kỳ hiện tại")
    author = next((v for k, v in filters if "người" in k.lower()), "Phòng Công tác Sinh viên")

    pages: list[tuple[plt.Figure, plt.Axes]] = []

    # === TRANG 1+: Header hành chính, Kính gửi, Đoạn mở đầu & Phân tích I + II ===
    fig1, ax1 = plt.subplots(figsize=(8.27, 11.69))
    ax1.axis("off")
    pages.append((fig1, ax1))

    # Quốc hiệu - Tiêu ngữ
    ax1.text(0.72, 0.965, "CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM", ha="center", va="top", fontsize=11.0, weight="bold", color="#000000")
    ax1.text(0.72, 0.946, "Độc lập - Tự do - Hạnh phúc", ha="center", va="top", fontsize=10.5, weight="bold", color="#000000")
    ax1.plot([0.60, 0.84], [0.936, 0.936], color="#000000", lw=0.9)
    ax1.text(0.72, 0.920, f"TP. Hồ Chí Minh, ngày {generated_at.day:02d} tháng {generated_at.month:02d} năm {generated_at.year}", ha="center", va="top", fontsize=9.5, style="italic", color="#000000")

    # Tiêu đề báo cáo
    y = 0.875
    ax1.text(0.52, y, title.upper(), ha="center", va="top", fontsize=14.5, weight="bold", color="#1E3A8A")
    y -= 0.026
    ax1.text(0.52, y, "Kết quả kiểm tra, giám sát công tác thực hiện nội quy, tác phong sinh viên", ha="center", va="top", fontsize=10.5, style="italic", color="#000000")
    y -= 0.022
    ax1.text(0.52, y, f"({range_label})", ha="center", va="top", fontsize=10.5, style="italic", color="#000000")
    y -= 0.035

    # Kính gửi
    ax1.text(0.12, y, "Kính gửi:", ha="left", va="top", fontsize=10.5, weight="bold", color="#000000")
    y -= 0.022
    ax1.text(0.16, y, "●  Ban Giám hiệu Nhà trường;", ha="left", va="top", fontsize=10.0, color="#000000")
    y -= 0.020
    ax1.text(0.16, y, "●  Ban Chủ nhiệm các Khoa/Viện.", ha="left", va="top", fontsize=10.0, color="#000000")
    y -= 0.030

    # Đoạn mở đầu hành chính
    year = generated_at.year
    intro_lines = textwrap.wrap(
        f"Thực hiện kế hoạch kiểm tra, rèn luyện nề nếp học đường năm học {year} – {year + 1}, "
        "Phòng Công tác Sinh viên (CTSV) phối hợp cùng Ban Chỉ đạo Đội Cờ đỏ báo cáo tình hình chấp hành quy định "
        "tác phong của sinh viên qua Hệ thống Giám sát Tác phong Học đường (CIM) như sau:",
        width=82,
    )
    for line in intro_lines:
        ax1.text(0.12, y, line, ha="left", va="top", fontsize=10.0, color="#000000")
        y -= 0.020
    y -= 0.015

    # Phân tích AI (Phần I & II) sau khi đã làm sạch preamble
    cleaned_analysis = StatsPromptBuilder.clean_analysis(analysis_text or "")
    curr_fig, curr_ax = fig1, ax1
    if cleaned_analysis:
        for raw_line in cleaned_analysis.splitlines():
            line = raw_line.strip().replace("**", "").replace("###", "").strip()
            if not line:
                y -= 0.012
                continue

            is_roman = line.startswith(("I. ", "II. ", "III. ", "IV. "))
            is_num = (len(line) >= 2 and line[0].isdigit() and line[1] in (".", " ")) or (len(line) >= 3 and line[:2].isdigit() and line[2] in (".", " "))
            is_bullet = line.startswith(("-", "•", "*"))

            wrapped = textwrap.wrap(line, width=76 if is_bullet else 82)
            line_height = len(wrapped) * 0.020 + (0.012 if (is_roman or is_num) else 0.004)

            if y - line_height < 0.06:
                curr_fig, curr_ax = plt.subplots(figsize=(8.27, 11.69))
                curr_ax.axis("off")
                pages.append((curr_fig, curr_ax))
                y = 0.94

            if is_roman:
                curr_ax.text(0.12, y, line, ha="left", va="top", fontsize=11.5, weight="bold", color="#1E3A8A")
                y -= 0.026
            elif is_num:
                curr_ax.text(0.12, y, line, ha="left", va="top", fontsize=10.5, weight="bold", color="#000000")
                y -= 0.022
            elif is_bullet:
                bullet_body = line.lstrip("-•* ").strip()
                b_wrapped = textwrap.wrap(bullet_body, width=74)
                curr_ax.text(0.12, y, "-", ha="left", va="top", fontsize=9.5, weight="bold", color="#000000")
                for bw in b_wrapped:
                    curr_ax.text(0.14, y, bw, ha="left", va="top", fontsize=9.5, color="#000000")
                    y -= 0.019
            else:
                for pw in wrapped:
                    curr_ax.text(0.12, y, pw, ha="left", va="top", fontsize=9.5, color="#000000")
                    y -= 0.019

    # === TRANG MỚI: III. BẢNG THỐNG KÊ SỐ LIỆU VI PHẠM & IV. DANH SÁCH CHI TIẾT ===
    fig_tbl, ax_tbl = plt.subplots(figsize=(8.27, 11.69))
    ax_tbl.axis("off")
    pages.append((fig_tbl, ax_tbl))
    yt = 0.95

    ax_tbl.text(0.12, yt, "III. BẢNG THỐNG KÊ SỐ LIỆU VI PHẠM", ha="left", va="top", fontsize=11.5, weight="bold", color="#1E3A8A")
    yt -= 0.030

    # Bảng 3.1: Cơ cấu loại vi phạm
    ax_tbl.text(0.12, yt, "Bảng 3.1. Thống kê theo nhóm hành vi vi phạm", ha="left", va="top", fontsize=10.0, weight="bold", color="#000000")
    yt -= 0.016

    by_type_list = summary.get("by_type", [])
    max_c = max([t.get("count", 0) for t in by_type_list], default=0)
    type_rows = [["STT", "Nội dung hành vi vi phạm", "Số lượt", "Tỷ lệ (%)", "Ghi chú"]]
    if by_type_list:
        for idx, t in enumerate(by_type_list, 1):
            c = t.get("count", 0)
            pct = f"{(c / total * 100):.1f}%" if total > 0 else "0.0%"
            note = "Chiếm đa số" if (c == max_c and c > 0) else ""
            type_rows.append([str(idx), _format_table_cell(t.get("name", ""), 30), f"{c:02d}" if c < 10 else str(c), pct, note])
    else:
        type_rows.append(["—", "Không có vi phạm ghi nhận", "00", "0.0%", ""])

    type_rows.append(["Tổng", "", f"{total:02d}" if total < 10 else str(total), "100%" if total > 0 else "0.0%", ""])

    tbl31_h = len(type_rows) * 0.025
    _render_styled_table(
        ax_tbl,
        cell_data=type_rows,
        col_widths=[0.08, 0.44, 0.14, 0.16, 0.18],
        bbox=[0.12, yt - tbl31_h, 0.80, tbl31_h],
        col_alignments=["center", "left", "center", "center", "center"],
        header_bg="#F1F5F9",
        header_color="#000000",
        header_font_size=8.5,
        row_font_size=8.0,
    )
    yt -= tbl31_h + 0.030

    # Bảng 3.2: Khu vực và khung giờ ghi nhận
    ax_tbl.text(0.12, yt, "Bảng 3.2. Thống kê theo khu vực và khung giờ ghi nhận", ha="left", va="top", fontsize=10.0, weight="bold", color="#000000")
    yt -= 0.016

    hotspot_list = [h for h in summary.get("hotspots", []) if h.get("count", 0) > 0][:5]
    hotspot_rows = [["STT", "Khu vực", "Khung giờ ghi nhận", "Số lượt", "Tỷ lệ (%)"]]
    if hotspot_list:
        for idx, h in enumerate(hotspot_list, 1):
            lbl = h.get("label", "")
            if "•" in lbl:
                parts = lbl.split("•", 1)
                area_str = parts[0].strip()
                hour_str = parts[1].strip()
            else:
                area_str = h.get("area_name", lbl)
                hour_str = f"{h.get('hour', 7):02d}:00 – {h.get('hour', 7):02d}:59"
            c = h.get("count", 0)
            pct = f"{(c / total * 100):.1f}%" if total > 0 else "0.0%"
            hotspot_rows.append([str(idx), _format_table_cell(area_str, 24), hour_str, str(c), pct])
    else:
        hotspot_rows.append(["—", "Không ghi nhận điểm nóng", "—", "0", "0.0%"])

    tbl32_h = len(hotspot_rows) * 0.025
    _render_styled_table(
        ax_tbl,
        cell_data=hotspot_rows,
        col_widths=[0.08, 0.40, 0.22, 0.14, 0.16],
        bbox=[0.12, yt - tbl32_h, 0.80, tbl32_h],
        col_alignments=["center", "left", "center", "center", "center"],
        header_bg="#F1F5F9",
        header_color="#000000",
        header_font_size=8.5,
        row_font_size=8.0,
    )
    yt -= tbl32_h + 0.035

    # Section IV: DANH SÁCH SINH VIÊN VI PHẠM GHI NHẬN TRONG KỲ
    ax_tbl.text(0.12, yt, "IV. DANH SÁCH SINH VIÊN VI PHẠM GHI NHẬN TRONG KỲ", ha="left", va="top", fontsize=11.5, weight="bold", color="#1E3A8A")
    yt -= 0.024

    detail_headers = ["Mã\nbản\nghi", "Thời điểm\nghi nhận", "MSSV", "Sinh viên", "Hành vi vi phạm", "Vị trí /\nGiảng đường"]
    detail_data_all: list[list[str]] = []
    for d in details[:30]:
        rec_id = str(d.get("ID", d.get("id", "")))
        time_str = _fmt_detail_time(d)
        mssv = str(d.get("MSSV") or d.get("MA_SV") or d.get("student_code") or d.get("student_id") or "—").strip()
        sv_name = str(d.get("HOTEN") or d.get("student_name") or (d.get("student", {}).get("name") if isinstance(d.get("student"), dict) else "") or "Chưa định danh").strip()
        loi = str(d.get("TEN_LOI") or d.get("type_name") or (d.get("type", {}).get("name") if isinstance(d.get("type"), dict) else "") or "").strip()
        kv = str(d.get("PHONG_HOC") or d.get("TEN_KHU_VUC") or d.get("location") or (d.get("area", {}).get("name") if isinstance(d.get("area"), dict) else "") or "Khu vực chung").strip()

        repeat_note = d.get("GHI_CHU") or d.get("note") or ""
        if repeat_note and ("lần" in repeat_note.lower() or "lan" in repeat_note.lower()):
            loi += f"\n({repeat_note})"

        detail_data_all.append([
            rec_id,
            time_str,
            mssv,
            _format_table_cell(sv_name, 16),
            _format_table_cell(loi, 20),
            _format_table_cell(kv, 14),
        ])

    if not detail_data_all:
        detail_data_all.append(["—", "—", "—", "Không ghi nhận vi phạm", "—", "—"])

    # Phân trang Table IV nếu số hàng nhiều
    avail_h = yt - 0.05
    row_h = 0.026
    header_h = 0.030
    rows_page1_count = max(2, int((avail_h - header_h) / row_h))

    data_p1 = [detail_headers] + detail_data_all[:rows_page1_count]
    data_p2 = detail_data_all[rows_page1_count:]

    tbl4_h = len(data_p1) * row_h
    _render_styled_table(
        ax_tbl,
        cell_data=data_p1,
        col_widths=[0.08, 0.16, 0.15, 0.21, 0.24, 0.16],
        bbox=[0.12, yt - tbl4_h, 0.80, tbl4_h],
        col_alignments=["center", "center", "center", "left", "left", "left"],
        header_bg="#F1F5F9",
        header_color="#000000",
        header_font_size=8.5,
        row_font_size=7.5,
    )

    last_ax = ax_tbl
    last_yt = yt - tbl4_h

    if data_p2:
        fig4, ax4 = plt.subplots(figsize=(8.27, 11.69))
        ax4.axis("off")
        pages.append((fig4, ax4))
        y4 = 0.95

        data_p2_table = [detail_headers] + data_p2
        tbl4_p2_h = len(data_p2_table) * row_h
        _render_styled_table(
            ax4,
            cell_data=data_p2_table,
            col_widths=[0.08, 0.16, 0.15, 0.21, 0.24, 0.16],
            bbox=[0.12, y4 - tbl4_p2_h, 0.80, tbl4_p2_h],
            col_alignments=["center", "center", "center", "left", "left", "left"],
            header_bg="#F1F5F9",
            header_color="#000000",
            header_font_size=8.5,
            row_font_size=7.5,
        )
        last_ax = ax4
        last_yt = y4 - tbl4_p2_h

    # Khối chữ ký: NGƯỜI LẬP BÁO CÁO (Ký và ghi rõ họ tên)
    y_sig = last_yt - 0.05
    if y_sig < 0.12:
        fig_sig, ax_sig = plt.subplots(figsize=(8.27, 11.69))
        ax_sig.axis("off")
        pages.append((fig_sig, ax_sig))
        y_sig = 0.88
        last_ax = ax_sig

    last_ax.text(0.75, y_sig, "NGƯỜI LẬP BÁO CÁO", ha="center", va="top", fontsize=10.5, weight="bold", color="#000000")
    last_ax.text(0.75, y_sig - 0.024, "(Ký và ghi rõ họ tên)", ha="center", va="top", fontsize=9.0, style="italic", color="#000000")

    # Đánh số trang và xuất PDF
    buf = io.BytesIO()
    total_pages = len(pages)
    with PdfPages(buf) as pdf:
        for idx, (fig, ax) in enumerate(pages, 1):
            ax.text(
                0.50, 0.022,
                f"Trang {idx} / {total_pages}  —  Hệ thống Giám sát Tác phong Học đường (Campus Integrity Monitor)",
                ha="center", va="bottom", fontsize=8.0, color="#94A3B8"
            )
            pdf.savefig(fig)
            plt.close(fig)

    return buf.getvalue()



def build_pdf(
    title: str,
    generated_at: datetime,
    filters: list[tuple[str, str]],
    summary: dict[str, Any],
    details: list[dict[str, Any]],
    font_path: str | None = None,
    max_detail_rows: int = 300,
    analysis_text: str | None = None,
) -> bytes:
    """
    Build a multi-page PDF report with statistics and Gemini AI analysis.
    Uses matplotlib (PdfPages) natively installed, or fpdf2 if available.
    """
    try:
        import fpdf  # noqa: F401
        return _build_pdf_fpdf2(title, generated_at, filters, summary, details, font_path, max_detail_rows, analysis_text)
    except ImportError:
        return _build_pdf_matplotlib(title, generated_at, filters, summary, details, analysis_text)
