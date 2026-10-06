"""
Build the prompt for LLM from statistics that are already calculated (see report_stats.py)
"""
import yaml
from pathlib import Path


class StatsPromptBuilder:
    def __init__(self,prompt_yaml_path:Path):
        with open(prompt_yaml_path,'r',encoding='utf-8') as f:
            self._prompt=yaml.safe_load(f) # self._prompt: dict[str,str]

    def build(self,stats: dict)->str:
        """
        Join role, rules, data and required structure to a complete prompt
        """
        return "\n".join([
            self._prompt['role'],
            self._prompt['context'],
            "QUY TẮC DỮ LIỆU:\n"+self._prompt['data_rules'],
            "QUY TẮC PHÂN TÍCH:\n"+self._prompt['analysis_rules'],
            self._build_stats_section(stats=stats),
            "CẤU TRÚC BÁO CÁO (BẮT BUỘC):\n"+self._prompt['report_structure']
        ])

    def _build_stats_section(self,stats: dict)->str:
        """
        Preprocessing statistics to text
        """
        period=stats["period"]
        lines=[
            "SỐ LIỆU ĐÃ TÍNH SẴN:",
            f"- Kỳ báo cáo: {period['type']} ({period['start']} đến {period['end']})",
            f"- Tổng số lượt ghi nhận: {stats['total']}",
            f"- Số lượt vi phạm: {stats['violations']} ({stats['violation_rate']}%)",
            f"- Vi phạm đồng phục: {stats['by_type']['uniform']}, vi phạm thẻ: {stats['by_type']['card']}, vi phạm cả hai: {stats['by_type']['both']}",
        ]
        previous=stats.get("previous")
        if previous is not None:
            lines.append(
                f"- Kỳ trước: {previous['total']} lượt ghi nhận, {previous['violations']} lượt vi phạm ({previous['violation_rate']}%)"
            )
        lines.append("Theo địa điểm (địa điểm | tổng lượt | vi phạm | tỷ lệ):")
        for item in stats["by_location"]:
            lines.append(f"  - {item['location']} | {item['total']} | {item['violations']} | {item['violation_rate']}%")
        lines.append("Theo giờ trong ngày (giờ | tổng lượt | vi phạm | tỷ lệ):")
        for item in stats["by_hour"]:
            lines.append(f"  - {item['hour']}h | {item['total']} | {item['violations']} | {item['violation_rate']}%")
        lines.append("Theo thứ trong tuần (thứ | tổng lượt | vi phạm | tỷ lệ):")
        for item in stats["by_weekday"]:
            lines.append(f"  - {item['weekday']} | {item['total']} | {item['violations']} | {item['violation_rate']}%")
        lines.append("Theo ngày (ngày | tổng lượt | vi phạm | tỷ lệ):")
        for item in stats["by_day"]:
            lines.append(f"  - {item['date']} | {item['total']} | {item['violations']} | {item['violation_rate']}%")
        return "\n".join(lines)+"\n"
