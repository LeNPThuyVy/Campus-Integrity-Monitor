"""Request bodies for /api/v1 (Pydantic validation). Responses are plain JSON dictionaries
built by the repositories, wrapped consistently as {"data": ...} or list envelopes."""
from __future__ import annotations

from datetime import date
from typing import Any

from pydantic import BaseModel, ConfigDict, Field


class StrictModel(BaseModel):
    model_config = ConfigDict(extra="forbid", str_strip_whitespace=True)


class LoginRequest(StrictModel):
    username: str = Field(min_length=1, max_length=50)
    password: str = Field(min_length=1, max_length=256)


class ChangePasswordRequest(StrictModel):
    current_password: str = Field(min_length=1, max_length=256)
    new_password: str = Field(min_length=1, max_length=256)


class AccountCreateRequest(StrictModel):
    username: str = Field(min_length=3, max_length=50)
    full_name: str = Field(min_length=1, max_length=100)
    role: str = Field(pattern="^(ADMIN|CTSV)$")
    password: str = Field(min_length=1, max_length=256)


class AccountUpdateRequest(StrictModel):
    full_name: str = Field(min_length=1, max_length=100)
    role: str = Field(pattern="^(ADMIN|CTSV)$")
    active: bool


class PasswordResetRequest(StrictModel):
    new_password: str = Field(min_length=1, max_length=256)


class AreaRequest(StrictModel):
    code: str = Field(min_length=1, max_length=20)
    name: str = Field(min_length=1, max_length=100)
    description: str | None = Field(default=None, max_length=255)
    active: bool = True


class CameraRequest(StrictModel):
    code: str = Field(min_length=1, max_length=30)
    name: str = Field(min_length=1, max_length=100)
    area_id: int = Field(ge=1)
    location: str | None = Field(default=None, max_length=255)
    active: bool = True


class SemesterRequest(StrictModel):
    code: str = Field(min_length=1, max_length=20)
    name: str = Field(min_length=1, max_length=100)
    start_date: date
    end_date: date


class ProcessingUpdateRequest(StrictModel):
    status: str = Field(pattern="^(MOI|DA_XEM|DA_XU_LY|BO_QUA)$")
    note: str | None = Field(default=None, max_length=500)


class AiPdfReportRequest(BaseModel):
    title: str = "BÁO CÁO GIÁM SÁT TÁC PHONG"
    range_label: str = "Tuần hiện tại"
    period: str | None = None
    date_range: str | None = None
    author: str = "Phòng Công tác Sinh viên"
    filters: list[dict[str, Any]] | dict[str, Any] = []
    summary: dict[str, Any] = {}
    details: list[dict[str, Any]] = []

