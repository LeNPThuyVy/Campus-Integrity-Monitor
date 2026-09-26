"""
Declare data models for the reporting module
"""

from datetime import datetime
from dataclasses import dataclass

__all__ =[
    "TrackingResult",
    "ActiveEvent",
    "Event"
]

@dataclass(frozen=True)
class TrackingResult:
    """
    Object result use for process of event_logger
    """
    track_id:int
    uniform_label: str = "Waiting"
    card_label: str = "Waiting"
    label: str = "Waiting"

@dataclass
class ActiveEvent:
    """
    Object model in RAM, could be updated every frame
    """
    track_id: int
    uniform_label: str
    card_label: str
    first_seen: datetime
    last_seen: datetime
    label: str = ""

@dataclass(frozen=True)
class Event:
    """
    This is object for Event.JSON. It couldn't be update
    """
    track_id:int
    uniform_label: str
    card_label: str
    first_seen: datetime
    last_seen: datetime
    label: str = ""

