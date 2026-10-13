"""
Reliable delivery of violation events from the Desktop app to the API.

- Every event is first written to a local outbox (JPEG + JSON) so nothing is lost if the API or
  the network is down, or the app is closed.
- A background thread sends pending events with exponential backoff.
- The event_id generated on the Desktop is the idempotency key: resending after a timeout never
  creates a second record (the API answers 200 + duplicate=true).
- Responses that will never succeed (invalid data, inactive violation type, ...) move the event
  to outbox/failed with the reason, instead of retrying forever.
- Authentication problems (401/403) pause sending and are reported to the UI; events stay pending.
"""
from __future__ import annotations

import json
import logging
import os
import threading
import time
from dataclasses import dataclass
from pathlib import Path

import cv2

logger = logging.getLogger("cim.desktop.sender")

RETRYABLE_STATUS = {408, 425, 429, 500, 502, 503, 504}
AUTH_STATUS = {401, 403}
MAX_BACKOFF_SECONDS = 60
AUTH_PAUSE_SECONDS = 60


@dataclass
class ApiConfig:
    base_url: str
    camera_code: str
    device_key: str
    timeout: float = 10.0

    @property
    def configured(self) -> bool:
        return bool(self.base_url and self.camera_code and self.device_key)

    def headers(self) -> dict[str, str]:
        return {"X-Camera-Code": self.camera_code, "X-Device-Key": self.device_key}


@dataclass
class SenderStatus:
    pending: int = 0
    failed: int = 0
    sent: int = 0
    last_error: str | None = None
    last_success_at: float | None = None
    paused_reason: str | None = None


def _api_message(response) -> str:
    try:
        error = response.json().get("error", {})
        return error.get("message") or f"HTTP {response.status_code}"
    except Exception:
        return f"HTTP {response.status_code}"


def check_connection(config: ApiConfig, session=None) -> tuple[bool, str, dict | None]:
    """Call GET /api/v1/device/me to validate URL, camera code and device key."""
    import requests

    if not config.configured:
        return False, "Chưa cấu hình đủ địa chỉ API, mã camera và khóa thiết bị.", None
    http = session or requests
    try:
        response = http.get(config.base_url.rstrip("/") + "/api/v1/device/me", headers=config.headers(),
                            timeout=config.timeout)
    except requests.RequestException as exc:
        return False, f"Không kết nối được API: {type(exc).__name__}", None
    if response.status_code == 200:
        data = response.json()["data"]
        return True, f"Đã kết nối: {data['camera']['name']} - {data['area']['name']}", data
    return False, _api_message(response), None


class ViolationSender:
    def __init__(self, outbox_dir: Path, config: ApiConfig, on_status=None):
        self.outbox_dir = Path(outbox_dir)
        self.pending_dir = self.outbox_dir / "pending"
        self.failed_dir = self.outbox_dir / "failed"
        self.pending_dir.mkdir(parents=True, exist_ok=True)
        self.failed_dir.mkdir(parents=True, exist_ok=True)
        self._config = config
        self._on_status = on_status
        self._lock = threading.Lock()
        self._wake = threading.Event()
        self._stop = threading.Event()
        self._thread: threading.Thread | None = None
        self.status = SenderStatus()
        self._refresh_counts()

    # ------------------------------------------------------------------ configuration
    def update_config(self, config: ApiConfig) -> None:
        with self._lock:
            self._config = config
            self.status.paused_reason = None
        self._wake.set()

    @property
    def config(self) -> ApiConfig:
        with self._lock:
            return self._config

    # ------------------------------------------------------------------ outbox
    def enqueue(self, event, camera_code: str) -> bool:
        """Persist an event to the outbox. Returns False if the crop cannot be encoded."""
        ok, encoded = cv2.imencode(".jpg", event.crop, [int(cv2.IMWRITE_JPEG_QUALITY), 90])
        if not ok:
            logger.error("Không mã hóa được ảnh bằng chứng cho sự kiện %s.", event.event_id)
            return False
        metadata = {
            "event_id": event.event_id,
            "camera_code": camera_code,
            "violation_code": event.violation_code,
            "occurred_at": event.occurred_at.isoformat(timespec="seconds"),
            "track_id": event.track_id,
            "uniform_confidence": event.uniform_confidence,
            "card_confidence": event.card_confidence,
            "attempts": 0,
        }
        image_path = self.pending_dir / f"{event.event_id}.jpg"
        meta_path = self.pending_dir / f"{event.event_id}.json"
        self._atomic_write(image_path, encoded.tobytes())
        self._atomic_write(meta_path, json.dumps(metadata, ensure_ascii=False).encode("utf-8"))
        self._refresh_counts()
        self._wake.set()
        return True

    @staticmethod
    def _atomic_write(path: Path, data: bytes) -> None:
        temporary = path.with_suffix(path.suffix + ".tmp")
        with open(temporary, "wb") as handle:
            handle.write(data)
            handle.flush()
            os.fsync(handle.fileno())
        os.replace(temporary, path)

    def _pending_items(self) -> list[Path]:
        return sorted(self.pending_dir.glob("*.json"), key=lambda p: p.stat().st_mtime)

    def _refresh_counts(self) -> None:
        self.status.pending = len(list(self.pending_dir.glob("*.json")))
        self.status.failed = len(list(self.failed_dir.glob("*.json")))
        if self._on_status:
            try:
                self._on_status(self.status)
            except Exception:
                logger.exception("Lỗi callback trạng thái gửi.")

    def _move_to_failed(self, meta_path: Path, metadata: dict, reason: str) -> None:
        metadata["failure_reason"] = reason
        self._atomic_write(self.failed_dir / meta_path.name, json.dumps(metadata, ensure_ascii=False).encode("utf-8"))
        image_path = meta_path.with_suffix(".jpg")
        if image_path.exists():
            os.replace(image_path, self.failed_dir / image_path.name)
        meta_path.unlink(missing_ok=True)

    def _remove_pending(self, meta_path: Path) -> None:
        meta_path.with_suffix(".jpg").unlink(missing_ok=True)
        meta_path.unlink(missing_ok=True)

    # ------------------------------------------------------------------ sending
    def send_one(self, meta_path: Path, session) -> str:
        """Send a single pending event. Returns 'sent', 'retry', 'auth' or 'failed'."""
        import requests

        metadata = json.loads(meta_path.read_text(encoding="utf-8"))
        config = self.config
        if metadata.get("camera_code") != config.camera_code:
            self._move_to_failed(meta_path, metadata, "Sự kiện thuộc mã camera khác cấu hình hiện tại.")
            return "failed"
        image_path = meta_path.with_suffix(".jpg")
        if not image_path.exists():
            self._move_to_failed(meta_path, metadata, "Thiếu tệp ảnh bằng chứng trong outbox.")
            return "failed"
        fields = {key: ("" if metadata[key] is None else str(metadata[key]))
                  for key in ("event_id", "violation_code", "occurred_at", "track_id", "uniform_confidence",
                              "card_confidence")}
        try:
            with open(image_path, "rb") as image:
                response = session.post(config.base_url.rstrip("/") + "/api/v1/violations",
                                        headers=config.headers(), data=fields,
                                        files={"image": (image_path.name, image, "image/jpeg")},
                                        timeout=config.timeout)
        except requests.RequestException as exc:
            self.status.last_error = f"Mất kết nối API ({type(exc).__name__}); sẽ gửi lại."
            return "retry"

        if response.status_code in (200, 201):
            self._remove_pending(meta_path)
            self.status.sent += 1
            self.status.last_success_at = time.time()
            self.status.last_error = None
            self.status.paused_reason = None
            return "sent"
        message = _api_message(response)
        if response.status_code in AUTH_STATUS:
            self.status.paused_reason = f"API từ chối thiết bị: {message}"
            return "auth"
        if response.status_code in RETRYABLE_STATUS:
            self.status.last_error = f"API tạm lỗi ({response.status_code}): {message}; sẽ gửi lại."
            return "retry"
        self._move_to_failed(meta_path, metadata, f"HTTP {response.status_code}: {message}")
        self.status.last_error = f"Bản ghi bị từ chối: {message}"
        return "failed"

    def _run(self) -> None:
        import requests

        session = requests.Session()
        backoff = 1.0
        while not self._stop.is_set():
            if not self.config.configured:
                self._wake.wait(5)
                self._wake.clear()
                continue
            items = self._pending_items()
            if not items:
                self._wake.wait(5)
                self._wake.clear()
                continue
            outcome = "sent"
            for meta_path in items:
                if self._stop.is_set():
                    break
                try:
                    outcome = self.send_one(meta_path, session)
                except (OSError, ValueError, KeyError) as exc:
                    logger.error("Outbox hỏng (%s): %s", meta_path.name, exc)
                    try:
                        self._move_to_failed(meta_path, {"event_id": meta_path.stem}, f"Outbox hỏng: {exc}")
                    except OSError:
                        pass
                    outcome = "failed"
                self._refresh_counts()
                if outcome in ("retry", "auth"):
                    break
            if outcome == "retry":
                self._wake.wait(backoff)
                backoff = min(backoff * 2, MAX_BACKOFF_SECONDS)
            elif outcome == "auth":
                self._wake.wait(AUTH_PAUSE_SECONDS)
            else:
                backoff = 1.0
            self._wake.clear()
        session.close()

    def start(self) -> None:
        if self._thread is None:
            self._stop.clear()
            self._thread = threading.Thread(target=self._run, name="violation-sender", daemon=True)
            self._thread.start()

    def stop(self, timeout: float = 3.0) -> None:
        self._stop.set()
        self._wake.set()
        if self._thread is not None:
            self._thread.join(timeout=timeout)
            self._thread = None
