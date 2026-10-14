from datetime import datetime

from ai.reporting.json_repository import JsonRepository
from ai.reporting.models import Event


def test_append_and_read_back(tmp_path):
    repo = JsonRepository(file_path=tmp_path / "storage" / "events.json")
    assert repo.get_all() == []
    event = Event(track_id=1, uniform_label="Uniform", card_label="No_Card", label="Uniform | No_Card",
                  first_seen=datetime(2026, 10, 13, 8, 0, 0), last_seen=datetime(2026, 10, 13, 8, 0, 5))
    repo.append(new_event=event)
    events = repo.get_all()
    assert events == [event]
