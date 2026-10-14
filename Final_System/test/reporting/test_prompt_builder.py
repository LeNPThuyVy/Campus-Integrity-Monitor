from datetime import datetime

import pytest

import ai.config as my_config
from ai.reporting.models import Event
from ai.reporting.prompt_builder import PromptBuilder


def make_event(track_id, uniform_label, card_label):
    now = datetime(2026, 10, 13, 8, 0, 0)
    return Event(
        track_id=track_id,
        uniform_label=uniform_label,
        card_label=card_label,
        label=f"{uniform_label} | {card_label}",
        first_seen=now,
        last_seen=now,
    )



@pytest.fixture
def builder():
    return PromptBuilder(prompt_yaml_path=my_config.PROMPT_YAML_PATH)


def test_empty_events_prompt_says_no_data(builder):
    prompt = builder.build([])
    assert "HIỆN KHÔNG CÓ DỮ LIỆU SỰ KIỆN" in prompt
    assert "TÓM TẮT" in prompt and "KIẾN NGHỊ" in prompt


def test_events_are_listed_with_uniform_and_card_status(builder):
    events = [make_event(1, "Uniform", "Card"), make_event(2, "Non_Uniform", "No_Card")]
    prompt = builder.build(events)
    assert prompt.count("Sự kiện") >= 2
    assert "Trạng thái Đồng phục: Non_Uniform" in prompt
    assert "Trạng thái Thẻ sinh viên: No_Card" in prompt
    assert "Mã theo dõi: 2" in prompt


def test_multiple_mixed_events_generation(builder):
    events = [
        make_event(1, "Uniform", "Card"),
        make_event(2, "Non_Uniform", "Card"),
        make_event(3, "Non_Uniform", "No_Card"),
        make_event(4, "Uniform", "Card"),
    ]
    prompt = builder.build(events)
    assert prompt.count("Sự kiện") == 4

