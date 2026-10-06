"""
Declare data models for the reporting module
"""

from datetime import datetime
from dataclasses import dataclass, field

__all__ =[
    "TrackingResult",
    "ActiveEvent",
    "Event",
    "get_violation_type"
]

def get_violation_type(uniform_label: str, card_label: str) -> str:
    """
    A violation is Non_Uniform or No_Card
    Returns one of: "none", "uniform", "card", "both"
    """
    is_uniform_violation = uniform_label == "Non_Uniform"
    is_card_violation = card_label == "No_Card"
    if is_uniform_violation and is_card_violation:
        return "both"
    if is_uniform_violation:
        return "uniform"
    if is_card_violation:
        return "card"
    return "none"


@dataclass(frozen=True)
class TrackingResult:
    """
    Object result use for process of event_logger
    """
    track_id:int
    uniform_label: str = "Waiting"
    card_label: str = "Waiting"
    label: str = "Waiting"
    bbox: list[float] | None = field(default=None, compare=False)
    image: object = field(default=None, compare=False)

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
    event_uuid: str = ""
    image_path: str = ""

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
    event_uuid: str = ""
    device_id: str = ""
    session_id: str = ""
    image_path: str = ""

    @property
    def violation_type(self) -> str:
        return get_violation_type(self.uniform_label, self.card_label)

    @property
    def is_violation(self) -> bool:
        return self.violation_type != "none"


