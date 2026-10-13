"""
Live call to the Gemini API. Skipped unless GEMINI_API_KEY is set and the google-genai package is
installed, so the regular test run never sends data to an external service.
"""
import os
from datetime import datetime

import pytest

import ai.config as my_config
from ai.reporting.models import Event
from ai.reporting.prompt_builder import PromptBuilder

pytestmark = pytest.mark.skipif(not os.getenv(my_config.GEMINI_KEY_ENV),
                                reason="Cần GEMINI_API_KEY để gọi Gemini thật.")


def test_gemini_generates_report():
    pytest.importorskip("google.genai")
    from ai.reporting.gemini_service import GeminiService

    now = datetime.now()
    events = [Event(track_id=i, uniform_label=u, card_label=c, label=f"{u} | {c}", first_seen=now, last_seen=now)
              for i, (u, c) in enumerate([("Uniform", "Card"), ("Non_Uniform", "Card"), ("Uniform", "No_Card")], 1)]
    prompt = PromptBuilder(prompt_yaml_path=my_config.PROMPT_YAML_PATH).build(events=events)
    report = GeminiService(api_key_env=my_config.GEMINI_KEY_ENV, model=my_config.GEMINI_MODEL).generate(prompt=prompt)
    assert isinstance(report, str) and report.strip()
