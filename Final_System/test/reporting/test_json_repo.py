

from ai.reporting.json_repository import JsonRepository
from pathlib import Path
from ai.reporting.models import Event

repo = JsonRepository(file_path= Path("storage/events.json"))

event=Event(
    track_id=1,
    label="Uniform",
    first_seen=...,
    last_seen=...
)

repo.append(new_event=event)

events=repo.get_all()

print(events)