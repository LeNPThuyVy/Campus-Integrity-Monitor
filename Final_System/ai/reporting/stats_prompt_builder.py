"""
Build the prompt for LLM from SQL Server statistics and violation log records.
Provides fallback text generation if Gemini API is offline or not configured.
"""
from __future__ import annotations

from pathlib import Path
from typing import Any
import yaml


class StatsPromptBuilder:
    def __init__(self, prompt_yaml_path: Path):
        with open(prompt_yaml_path, "r", encoding="utf-8") as f:
            self._prompt = yaml.safe_load(f)

    def build_from_sql_summary(
        self,
        range_label: str,
        filters: list[dict[str, str]],
        summary: dict[str, Any],
        details: list[dict[str, Any]] | None = None,
    ) -> str:
        """
        Assemble prompt for Gemini LLM using SQL summary statistics and sample details.
        """
        if isinstance(filters, dict):
            filters_str = ", ".join(f"{k}: {v}" for k, v in filters.items())
        elif isinstance(filters, list):
            filters_str = ", ".join(f"{f.get('label', '')}: {f.get('value', '')}" if isinstance(f, dict) else str(f) for f in filters)
        else:
            filters_str = str(filters or "")

        total = summary.get("total", 0)
        lines = [
            f"KỲ BÁO CÁO: {range_label}",
            f"BỘ LỌC ÁP DỤNG: {filters_str}",
            f"- Tổng số lượt vi phạm ghi nhận: {total}",
        ]

        by_type = summary.get("by_type", [])
        if by_type and total > 0:
            types_str = ", ".join(f"{item.get('name')}: {item.get('count', 0)} lượt ({(item.get('count', 0) / total * 100):.1f}%)" for item in by_type)
            lines.append(f"- Cơ cấu theo hành vi vi phạm: {types_str}")

        by_faculty = summary.get("by_faculty", [])
        if by_faculty and total > 0:
            fac_str = ", ".join(f"{item.get('name')}: {item.get('count', 0)} lượt ({(item.get('count', 0) / total * 100):.1f}%)" for item in by_faculty if item.get('count', 0) > 0)
            if fac_str:
                lines.append(f"- Phân bố theo Khoa/Ngành đào tạo: {fac_str}")

        peak_hour = summary.get("peak_hour")
        if peak_hour and isinstance(peak_hour, dict):
            lines.append(f"- Khung giờ cao điểm: {peak_hour.get('label')} ({peak_hour.get('count')} lượt)")

        peak_area = summary.get("peak_area")
        if peak_area and isinstance(peak_area, dict):
            lines.append(f"- Khu vực tập trung nhiều vi phạm nhất: {peak_area.get('name')} ({peak_area.get('count')} lượt)")

        hotspots = summary.get("hotspots", [])
        if hotspots:
            lines.append("Điểm nóng (Khu vực / Phòng học • Khung giờ):")
            for h in hotspots[:5]:
                lines.append(f"  + {h.get('label', h.get('area_name'))}: {h.get('count')} lượt")

        by_weekday = summary.get("by_weekday", [])
        if by_weekday:
            lines.append("Theo thứ trong tuần:")
            for w in by_weekday:
                if w.get("count", 0) > 0:
                    lines.append(f"  + {w.get('name')}: {w.get('count')} lượt")

        if details:
            lines.append(f"Danh sách mẫu các sự kiện vi phạm đã ghi nhận ({min(len(details), 20)} bản ghi gần nhất):")
            for d in details[:20]:
                time_str = d.get("THOI_DIEM") or d.get("occurred_at") or d.get("created_at") or d.get("time") or ""
                loi = d.get("TEN_LOI") or d.get("type_name") or (d.get("type", {}).get("name") if isinstance(d.get("type"), dict) else "")
                place = d.get("PHONG_HOC") or d.get("location") or d.get("TEN_KHU_VUC") or (d.get("area", {}).get("name") if isinstance(d.get("area"), dict) else "")
                sv = d.get("HOTEN") or d.get("student_name") or d.get("MA_SV") or d.get("student_code") or (d.get("student", {}).get("name") if isinstance(d.get("student"), dict) else "")
                lines.append(f"  * Thời điểm: {time_str} | Vị trí: {place} | Loại lỗi: {loi} | Đối tượng: {sv or 'Chưa định danh'}")

        data_section = "SỐ LIỆU TỔNG HỢP TỪ CƠ SỞ DỮ LIỆU:\n" + "\n".join(lines) + "\n"

        return "\n".join([
            self._prompt.get("role", ""),
            self._prompt.get("context", ""),
            "QUY TẮC DỮ LIỆU:\n" + self._prompt.get("data_rules", ""),
            "QUY TẮC PHÂN TÍCH:\n" + self._prompt.get("analysis_rules", ""),
            data_section,
            "CẤU TRÚC BÁO CÁO (BẮT BUỘC):\n" + self._prompt.get("report_structure", ""),
        ])

    @classmethod
    def clean_analysis(cls, text: str) -> str:
        """
        Strip any AI-hallucinated preambles (e.g. 'BỘ GIÁO DỤC...', 'TRƯỜNG ĐẠI HỌC...',
        'Kính gửi...', duplicated titles) so the report strictly starts at 'I. ĐÁNH GIÁ...'.
        """
        if not text:
            return ""
        lines = text.splitlines()
        start_idx = 0
        found = False
        for idx, line in enumerate(lines):
            stripped = line.strip().replace("**", "").replace("###", "").strip()
            if stripped.startswith("I. ") or stripped.startswith("I."):
                start_idx = idx
                found = True
                break
        if not found:
            # Fallback check for "1."
            for idx, line in enumerate(lines):
                stripped = line.strip().replace("**", "").replace("###", "").strip()
                if stripped.startswith("1. ") or stripped.startswith("1."):
                    start_idx = idx
                    break
        cleaned_lines = lines[start_idx:]
        cleaned_text = "\n".join(cleaned_lines)
        # Remove common hallucinated placeholders
        cleaned_text = cleaned_text.replace("[Tên Trường]", "Đại học Công Thương TP.HCM")
        cleaned_text = cleaned_text.replace("[Tên trường]", "Đại học Công Thương TP.HCM")
        return cleaned_text.strip()

    @staticmethod
    def build_fallback_analysis(range_label: str, summary: dict[str, Any]) -> str:
        """
        Generate administrative analysis text if LLM API is unavailable.
        Uses Roman numeral structure (I, II) matching formal university administrative style.
        """
        total = summary.get("total", 0)
        if total == 0:
            return (
                "I. ĐÁNH GIÁ TÌNH HÌNH THỰC HIỆN\n"
                "1. Kết quả ghi nhận chung\n"
                f"- Trong kỳ báo cáo ({range_label}), toàn trường không ghi nhận bất kỳ trường hợp sinh viên vi phạm quy định về tác phong khi ra vào khu vực học tập.\n"
                "- Sinh viên các khoa chấp hành nghiêm túc quy định đồng phục và đeo thẻ sinh viên khi đến trường.\n\n"
                "2. Phân tích thời điểm và khu vực vi phạm\n"
                "- Nền nếp kỷ cương được duy trì đồng đều tại toàn bộ các khu vực và các đơn vị đào tạo, không phát sinh điểm nóng vi phạm.\n\n"
                "II. KIẾN NGHỊ VÀ GIẢI PHÁP THỰC HIỆN\n"
                "Để tiếp tục duy trì nề nếp học đường, Phòng Công tác Sinh viên đề xuất triển khai các biện pháp sau:\n"
                "- Tăng cường kiểm soát tại cổng và sảnh giảng đường: Tiếp tục duy trì lực lượng Đội Cờ Đỏ tuần tra định kỳ tại các cổng chính và khu vực sảnh để bảo đảm kỷ cương ổn định.\n"
                "- Đẩy mạnh tuyên truyền trực quan: Phối hợp Đoàn Thanh niên – Hội Sinh viên lồng ghép nội dung phổ biến quy chế vào các buổi sinh hoạt lớp định kỳ.\n\n"
                "Trên đây là báo cáo tình hình thực hiện nội quy tác phong sinh viên kỳ qua, Phòng Công tác Sinh viên kính trình Ban Giám hiệu xem xét và cho ý kiến chỉ đạo."
            )

        types_list = summary.get("by_type", [])
        types_text = ", ".join(f"{t.get('name')}: {t.get('count')} lượt ({t.get('count', 0) / total * 100:.1f}%)" for t in types_list if t.get("count", 0) > 0)
        peak_hour = summary.get("peak_hour")
        peak_hour_str = f"{peak_hour.get('label')} ({peak_hour.get('count')} lượt)" if isinstance(peak_hour, dict) and peak_hour.get("label") else "phân bố rải rác"
        peak_area = summary.get("peak_area")
        peak_area_str = f"{peak_area.get('name')} ({peak_area.get('count')} lượt)" if isinstance(peak_area, dict) and peak_area.get("name") else "Khu vực chung"

        return (
            "I. ĐÁNH GIÁ TÌNH HÌNH THỰC HIỆN\n"
            "1. Kết quả ghi nhận chung\n"
            f"Trong kỳ báo cáo ({range_label}), hệ thống ghi nhận tổng cộng {total} trường hợp sinh viên vi phạm quy định về tác phong khi ra vào khu vực học tập. Trong đó:\n"
            f"- Hành vi vi phạm: {types_text or 'chủ yếu liên quan đến sai đồng phục và thiếu thẻ sinh viên'}.\n"
            "- Tình trạng tái phạm: Qua rà soát dữ liệu, đa số các trường hợp vi phạm rải rác ở các lớp. Cần tiếp tục đối chiếu mã số sinh viên để chấn chỉnh kịp thời các trường hợp vi phạm nhiều lần.\n\n"
            "2. Phân tích thời điểm và khu vực vi phạm\n"
            f"- Về thời gian: Khung giờ cao điểm ghi nhận nhiều vi phạm nhất rơi vào {peak_hour_str}. Nguyên nhân chủ yếu do sinh viên đi học sát giờ dẫn đến vội vã, quên mang thẻ hoặc mặc áo khoác che kín đồng phục.\n"
            f"- Về địa bàn trọng điểm: Khu vực tập trung nhiều vi phạm nhất là {peak_area_str}, nơi có lưu lượng sinh viên di chuyển vào giảng đường đông đúc.\n\n"
            "II. KIẾN NGHỊ VÀ GIẢI PHÁP THỰC HIỆN\n"
            "Để chấn chỉnh nề nếp học đường và hạn chế tình trạng tái phạm, Phòng Công tác Sinh viên đề xuất triển khai các biện pháp sau:\n"
            f"- Tăng cường kiểm soát tại cổng và sảnh giảng đường: Đội Cờ đỏ bố trí nhân sự trực chốt tại {peak_area_str} vào khung giờ {peak_hour_str}, kiểm tra thẻ và yêu cầu chỉnh đốn đồng phục trước khi vào sảnh.\n"
            "- Phối hợp quản lý cấp Khoa và Lớp: Phòng CTSV trích xuất danh sách vi phạm gửi về Văn phòng các Khoa, đề nghị Cố vấn học tập làm việc trực tiếp, nhắc nhở và yêu cầu viết bản cam kết.\n"
            "- Áp dụng chế tài điểm rèn luyện: Thực hiện trừ điểm rèn luyện theo đúng Quy chế công tác sinh viên hiện hành; với lỗi vi phạm kép đề xuất áp dụng mức trừ tương ứng để tăng tính răn đe.\n"
            "- Đẩy mạnh tuyên truyền trực quan: Phối hợp Đoàn Thanh niên – Hội Sinh viên đặt bảng nhắc nhở tại cổng trường và lồng ghép phổ biến quy chế vào sinh hoạt lớp.\n\n"
            "Trên đây là báo cáo tình hình thực hiện nội quy tác phong sinh viên kỳ qua, Phòng Công tác Sinh viên kính trình Ban Giám hiệu xem xét và cho ý kiến chỉ đạo."
        )

