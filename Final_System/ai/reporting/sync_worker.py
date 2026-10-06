"""
Background thread that pushes Events from the local outbox to the central server.
Step of one sync: send events (batch) -> for each event image: ask presigned url -> PUT image to cloud storage -> confirm
If the server is down or there is no network, nothing is lost, events stay in outbox and are retried with backoff.
"""
import logging
import random
import threading
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
import requests
from ai.reporting.sqlite_outbox import SqliteOutboxRepository


class SyncWorker:
    def __init__(self, repository: SqliteOutboxRepository, server_url: str, api_key: str,
                 interval_seconds: float = 5, batch_size: int = 50, image_workers: int = 2,
                 timeout_seconds: float = 15, http=requests):
        self._repository = repository
        self._server_url = server_url.rstrip("/")
        self._headers = {"X-API-Key": api_key}
        self._interval = interval_seconds
        self._batch_size = batch_size
        self._image_workers = image_workers
        self._timeout = timeout_seconds
        self._http = http
        self._stop_event = threading.Event()
        self._thread = None

    def start(self) -> None:
        if self._thread is not None and self._thread.is_alive():
            return
        self._stop_event.clear()
        self._thread = threading.Thread(target=self._run, daemon=True)
        self._thread.start()

    def stop(self) -> None:
        self._stop_event.set()
        if self._thread is not None:
            self._thread.join(timeout=2.0)
            self._thread = None

    def _run(self) -> None:
        """
        Loop until stop. Wait time grows (x2, max 60s, random jitter) while syncing fails,
        jitter prevents many devices from retrying at the same moment
        """
        delay = self._interval
        while not self._stop_event.is_set():
            try:
                self.sync_once()
                delay = self._interval
            except Exception as e:
                logging.warning(f"Sync failed, will retry: {e}")
                delay = min(delay * 2, 60)
            self._stop_event.wait(delay * random.uniform(1.0, 1.3))

    def sync_once(self) -> None:
        """
        One round of sync. Raise exception if the server can't be reached, so the loop can back off
        """
        self._send_events()
        self._send_images()

    def _send_events(self) -> None:
        events = self._repository.get_unsynced_events(limit=self._batch_size)
        if not events:
            return
        #image_path is a path on this device, the server doesn't need it
        payload = [
            {**event, "image_path": None, "has_image": bool(event.get("image_path"))}
            for event in events
        ]
        response = self._http.post(
            f"{self._server_url}/v1/events/batch",
            json={"events": payload},
            headers=self._headers,
            timeout=self._timeout
        )
        response.raise_for_status()
        self._repository.mark_events_synced([event["event_uuid"] for event in events])

    def _send_images(self) -> None:
        pending = self._repository.get_pending_images(limit=self._batch_size)
        if not pending:
            return
        with ThreadPoolExecutor(max_workers=self._image_workers) as pool:
            futures = [pool.submit(self._upload_one_image, event_uuid, image_path) for event_uuid, image_path in pending]
            for (event_uuid, _), future in zip(pending, futures):
                try:
                    status = future.result()
                except Exception as e:
                    logging.warning(f"Upload image {event_uuid} failed, will retry: {e}")
                    continue
                self._repository.mark_image_status(event_uuid, status)

    def _upload_one_image(self, event_uuid: str, image_path: str) -> str:
        """
        Returns 'uploaded', or 'missing' if the local file is gone
        """
        file_path = Path(image_path)
        if not file_path.exists():
            return "missing"
        url_response = self._http.post(
            f"{self._server_url}/v1/events/{event_uuid}/image-upload-url",
            headers=self._headers,
            timeout=self._timeout
        )
        url_response.raise_for_status()
        upload_info = url_response.json()
        #Image goes directly to cloud storage, not through our server
        put_response = self._http.put(
            upload_info["upload_url"],
            data=file_path.read_bytes(),
            headers={"Content-Type": "image/jpeg"},
            timeout=self._timeout
        )
        put_response.raise_for_status()
        confirm_response = self._http.post(
            f"{self._server_url}/v1/events/{event_uuid}/image-confirm",
            json={"image_key": upload_info["image_key"]},
            headers=self._headers,
            timeout=self._timeout
        )
        confirm_response.raise_for_status()
        return "uploaded"
