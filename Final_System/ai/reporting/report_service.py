"""
Generate a report: statistics -> prompt -> LLM analysis -> DOCX
"""
from ai.reporting.llm_service import LLMService
from ai.reporting.stats_prompt_builder import StatsPromptBuilder
from ai.reporting.report_docx import build_docx


class ReportService:
    def __init__(self, llm: LLMService, prompt_builder: StatsPromptBuilder):
        self._llm = llm
        self._prompt_builder = prompt_builder

    def generate_docx(self, stats: dict, images: list[tuple[str, bytes]] | None = None) -> bytes:
        """
        If there is no event, the LLM isn't called (nothing to analyse, save money)
        """
        analysis_failed = False
        if stats["total"] == 0:
            analysis_text = "Dữ liệu không đủ để kết luận."
        else:
            try:
                from ai.reporting.gemini_service import LLMError
                analysis_text = self._llm.generate(self._prompt_builder.build(stats))
            except Exception as e:
                import logging
                logging.error(f"Failed to generate analysis: {e}")
                analysis_text = "Phân tích tự động tạm thời không khả dụng do lỗi kết nối hoặc API. Vui lòng xem số liệu tổng hợp trong các bảng."
                analysis_failed = True
        return build_docx(stats=stats, analysis_text=analysis_text, images=images, analysis_failed=analysis_failed)
