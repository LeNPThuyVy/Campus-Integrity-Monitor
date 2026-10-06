"""
Build the DOCX report: statistics tables (calculated by code) + analysis text (written by LLM) + evidence images
"""
import io
import re
from datetime import datetime
from docx import Document
from docx.shared import Cm


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


def build_docx(stats: dict, analysis_text: str, images: list[tuple[str, bytes]] | None = None) -> bytes:
    """
    images: list of (caption, jpeg bytes) shown as evidence, can be empty
    Returns the content of the .docx file
    """
    document=Document()
    document.add_heading("BÁO CÁO TÌNH HÌNH THỰC HIỆN ĐỒNG PHỤC VÀ THẺ SINH VIÊN",level=1)
    period=stats["period"]
    document.add_paragraph(f"Kỳ báo cáo: {period['type']} (từ {period['start']} đến {period['end']})")
    document.add_paragraph(f"Ngày lập báo cáo: {datetime.now().strftime('%d/%m/%Y %H:%M')}")

    document.add_heading("Số liệu tổng hợp",level=2)
    _add_table(document,["Chỉ số","Giá trị"],[
        ["Tổng số lượt ghi nhận",stats["total"]],
        ["Số lượt vi phạm",f"{stats['violations']} ({stats['violation_rate']}%)"],
        ["Vi phạm đồng phục",stats["by_type"]["uniform"]],
        ["Vi phạm thẻ sinh viên",stats["by_type"]["card"]],
        ["Vi phạm cả hai",stats["by_type"]["both"]],
    ])
    document.add_paragraph("Lưu ý: hệ thống không nhận diện danh tính, một sinh viên có thể tạo nhiều lượt ghi nhận.")

    document.add_heading("Theo địa điểm",level=2)
    _add_table(document,["Địa điểm","Tổng lượt","Vi phạm","Tỷ lệ"],[
        [item["location"],item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats["by_location"]
    ])
    document.add_heading("Theo giờ trong ngày",level=2)
    _add_table(document,["Giờ","Tổng lượt","Vi phạm","Tỷ lệ"],[
        [f"{item['hour']}h",item["total"],item["violations"],f"{item['violation_rate']}%"] for item in stats["by_hour"]
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
