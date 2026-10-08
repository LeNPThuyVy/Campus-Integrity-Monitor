"""
Build the DOCX report: statistics tables (calculated by code) + analysis text (written by LLM) + evidence images
"""
import io
import re
from datetime import datetime
from docx import Document
from docx.shared import Cm, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH


def _add_table(document: Document, headers: list[str], rows: list[list]) -> None:
    table=document.add_table(rows=1,cols=len(headers))
    table.style="Table Grid"
    for index,header in enumerate(headers):
        table.rows[0].cells[index].text=header
    for row in rows:
        cells=table.add_row().cells
        for index,value in enumerate(row):
            cells[index].text=str(value)


def _add_analysis(document: Document, analysis_text: str) -> None:
    """
    LLM writes markdown-like text. Convert simple markdown (#, -, **) to Word paragraphs
    """
    for raw_line in analysis_text.splitlines():
        line=raw_line.strip()
        if not line:
            continue
        line=line.replace("**","")
        if line.startswith("#"):
            document.add_heading(line.lstrip("#").strip(),level=2)
        elif re.match(r"^\d+\.\s+[A-ZÀ-Ỹ\s]+$",line):
            #Section title like "1. TÓM TẮT"
            document.add_heading(line,level=2)
        elif line.startswith(("- ","* ")):
            document.add_paragraph(line[2:],style="List Bullet")
        else:
            document.add_paragraph(line)


def build_docx(stats: dict, analysis_text: str, images: list[tuple[str, bytes]] | None = None, analysis_failed: bool = False) -> bytes:
    """
    images: list of (caption, jpeg bytes) shown as evidence, can be empty
    Returns the content of the .docx file
    """
    document=Document()
    document.add_heading("BÁO CÁO TÌNH HÌNH THỰC HIỆN ĐỒNG PHỤC VÀ THẺ SINH VIÊN",level=1)
    period=stats["period"]
    
    # "Kỳ báo cáo: Ngày 07/10/2026 | Tuần 40 (06–12/10/2026) | Tháng 10/2026"
    period_str = ""
    if period['type'] == 'day':
        period_str = f"Ngày {period['start'][:10]}"
    elif period['type'] == 'week':
        period_str = f"Tuần {datetime.strptime(period['start'][:10], '%Y-%m-%d').isocalendar()[1]} ({period['start'][:10]} - {period['end'][:10]})"
    elif period['type'] == 'month':
        dt = datetime.strptime(period['start'][:10], '%Y-%m-%d')
        period_str = f"Tháng {dt.month}/{dt.year}"
    
    document.add_paragraph(f"Kỳ báo cáo: {period_str}")
    document.add_paragraph(f"Ngày lập báo cáo: {datetime.now().strftime('%d/%m/%Y %H:%M')}")

    if analysis_failed:
        p = document.add_paragraph()
        run = p.add_run("CẢNH BÁO: Quá trình phân tích tự động bằng AI đã gặp lỗi. Chỉ có số liệu được hiển thị.")
        run.font.color.rgb = RGBColor(255, 0, 0)
        run.bold = True

    document.add_heading("Số liệu tổng hợp",level=2)
    _add_table(document,["Chỉ số","Giá trị"],[
        ["Tổng số lượt ghi nhận",stats["total"]],
        ["Số lượt vi phạm",f"{stats['violations']} ({stats['violation_rate']}%)"],
        ["Vi phạm đồng phục",stats["by_type"]["uniform"]],
        ["Vi phạm thẻ sinh viên",stats["by_type"]["card"]],
        ["Vi phạm cả hai",stats["by_type"]["both"]],
    ])
    document.add_paragraph("Lưu ý: hệ thống không nhận diện danh tính, một sinh viên có thể tạo nhiều lượt ghi nhận.")

    if stats.get("previous"):
        prev = stats["previous"]
        diff = stats["violations"] - prev["violations"]
        trend = "tăng" if diff > 0 else "giảm" if diff < 0 else "không đổi"
        document.add_paragraph(f"So với kỳ trước: {trend} {abs(diff)} lượt vi phạm (kỳ trước: {prev['violations']} lượt).")

    document.add_heading("Theo địa điểm",level=2)
    _add_table(document,["Địa điểm","Tổng lượt","Vi phạm","Tỷ lệ"],[
        [item["location"],item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats["by_location"]
    ])
    document.add_heading("Theo giờ trong ngày",level=2)
    _add_table(document,["Giờ","Tổng lượt","Vi phạm","Tỷ lệ"],[
        [f"{item['hour']}h",item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats["by_hour"]
    ])
    
    if stats["period"]["type"] in ("week", "month"):
        document.add_heading("Theo ngày trong tuần",level=2)
        _add_table(document,["Thứ","Tổng lượt","Vi phạm","Tỷ lệ"],[
            [item["weekday"],item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats.get("by_weekday", [])
        ])
        
    if stats["period"]["type"] == "month":
        document.add_heading("Theo ngày trong tháng",level=2)
        _add_table(document,["Ngày","Tổng lượt","Vi phạm","Tỷ lệ"],[
            [item["date"],item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats.get("by_day", [])
        ])

    document.add_heading("Phân tích",level=2)
    _add_analysis(document,analysis_text)

    if images:
        document.add_heading("Ảnh minh họa",level=2)
        for caption,image_bytes in images:
            document.add_picture(io.BytesIO(image_bytes),width=Cm(5))
            document.add_paragraph(caption)

    output=io.BytesIO()
    document.save(output)
    return output.getvalue()
