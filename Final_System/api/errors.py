"""
Uniform error responses: {"error": {"code": ..., "message": ..., "details": ...}}.
Internal details (paths, SQL, connection strings) are logged, never returned.
"""
from __future__ import annotations

import logging
from typing import Any

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException

from api.database import DatabaseUnavailable

logger = logging.getLogger(__name__)


class ApiError(Exception):
    def __init__(self, status_code: int, code: str, message: str, details: Any = None):
        super().__init__(message)
        self.status_code = status_code
        self.code = code
        self.message = message
        self.details = details


def error_body(code: str, message: str, details: Any = None) -> dict[str, Any]:
    body: dict[str, Any] = {"error": {"code": code, "message": message}}
    if details is not None:
        body["error"]["details"] = details
    return body


def bad_request(message: str, details: Any = None) -> ApiError:
    return ApiError(400, "bad_request", message, details)


def validation_error(message: str, details: Any = None) -> ApiError:
    return ApiError(422, "validation_error", message, details)


def not_found(message: str = "Không tìm thấy dữ liệu.") -> ApiError:
    return ApiError(404, "not_found", message)


def conflict(message: str, details: Any = None) -> ApiError:
    return ApiError(409, "conflict", message, details)


def forbidden(message: str = "Bạn không có quyền thực hiện thao tác này.") -> ApiError:
    return ApiError(403, "forbidden", message)


def unauthorized(message: str = "Bạn cần đăng nhập.") -> ApiError:
    return ApiError(401, "unauthorized", message)


_STATUS_CODES = {
    400: "bad_request", 401: "unauthorized", 403: "forbidden", 404: "not_found",
    405: "method_not_allowed", 409: "conflict", 410: "gone", 413: "payload_too_large",
    415: "unsupported_media_type", 422: "validation_error", 429: "too_many_requests",
}


def install_error_handlers(app: FastAPI) -> None:
    @app.exception_handler(ApiError)
    async def _api_error(_: Request, exc: ApiError):
        headers = {"WWW-Authenticate": "Bearer"} if exc.status_code == 401 else None
        return JSONResponse(error_body(exc.code, exc.message, exc.details), status_code=exc.status_code,
                            headers=headers)

    @app.exception_handler(RequestValidationError)
    async def _validation(_: Request, exc: RequestValidationError):
        details = []
        for item in exc.errors():
            location = ".".join(str(part) for part in item.get("loc", ()) if part not in ("body", "query", "form"))
            details.append({"field": location, "message": item.get("msg", "")})
        return JSONResponse(error_body("validation_error", "Dữ liệu gửi lên không hợp lệ.", details),
                            status_code=422)

    @app.exception_handler(StarletteHTTPException)
    async def _http_error(_: Request, exc: StarletteHTTPException):
        message = exc.detail if isinstance(exc.detail, str) else "Yêu cầu không hợp lệ."
        return JSONResponse(error_body(_STATUS_CODES.get(exc.status_code, "error"), message),
                            status_code=exc.status_code, headers=getattr(exc, "headers", None))

    @app.exception_handler(DatabaseUnavailable)
    async def _database_unavailable(_: Request, exc: DatabaseUnavailable):
        return JSONResponse(error_body("database_unavailable",
                                       "Cơ sở dữ liệu tạm thời không truy cập được. Vui lòng thử lại sau."),
                            status_code=503)

    @app.exception_handler(Exception)
    async def _unhandled(_: Request, exc: Exception):
        logger.exception("Lỗi không xử lý được: %s", type(exc).__name__)
        return JSONResponse(error_body("internal_error", "Lỗi hệ thống. Vui lòng thử lại sau."), status_code=500)
