"""
Shape of the data going in and out of the event endpoints (/v1/...)
"""
from datetime import datetime
from uuid import UUID
from pydantic import BaseModel, Field


class EventIn(BaseModel):
    event_uuid: UUID
    track_id: int
    session_id: str = ""
    uniform_label: str
    card_label: str
    first_seen: datetime
    last_seen: datetime
    has_image: bool = False


class EventBatchRequest(BaseModel):
    events: list[EventIn] = Field(max_length=500)


class EventBatchResponse(BaseModel):
    accepted: int


class ImageUploadUrlResponse(BaseModel):
    upload_url: str
    image_key: str
    expires_in: int


class ImageConfirmRequest(BaseModel):
    image_key: str


class ImageUrlResponse(BaseModel):
    url: str
    expires_in: int


class EventOut(BaseModel):
    event_uuid: str
    device_id: str
    location: str | None = None
    track_id: int
    uniform_label: str
    card_label: str
    is_violation: bool
    violation_type: str
    first_seen: datetime
    last_seen: datetime
    image_status: str


class DeviceRegisterRequest(BaseModel):
    device_id: str = Field(min_length=1, max_length=64)
    location: str = Field(min_length=1, max_length=255)


class DeviceRegisterResponse(BaseModel):
    device_id: str
    location: str
    api_key: str
