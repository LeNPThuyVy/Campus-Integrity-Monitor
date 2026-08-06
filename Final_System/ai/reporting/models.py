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
    label: str

@dataclass
class ActiveEvent:
    """
    Object model in RAM, could be updated every each frame
    """
    track_id: int
    label: str
    first_seen: datetime
    last_seen: datetime

@dataclass(frozen=True)
class Event:
    """
    This is object for Event.JSON. It couldn't be update
    """
    track_id:int
    label: str
    first_seen: datetime
    last_seen: datetime

