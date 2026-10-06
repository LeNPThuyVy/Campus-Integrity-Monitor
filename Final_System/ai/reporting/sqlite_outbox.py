"""
Local queue (outbox) of Events on each device.
Every Event is saved here first (fast, works offline), then sync_worker pushes them to the central server.
"""
import json
import sqlite3
from contextlib import closing
from pathlib import Path
from ai.reporting.models import Event
from ai.reporting.event_repository import EventRepository
from ai.reporting.mapper import JsonEventMapper


class SqliteOutboxRepository(EventRepository):
    def __init__(self,file_path: Path):
        file_path.parent.mkdir(parents=True,exist_ok=True)
        self.file_path=file_path
        with closing(self._connect()) as conn:
            with conn:
                conn.execute("""
                    CREATE TABLE IF NOT EXISTS outbox (
                        event_uuid TEXT PRIMARY KEY,
                        payload TEXT NOT NULL,
                        image_path TEXT NOT NULL DEFAULT '',
                        event_synced INTEGER NOT NULL DEFAULT 0,
                        image_status TEXT NOT NULL DEFAULT 'none'
                    )
                """)

    def _connect(self) -> sqlite3.Connection:
        """
        Each call uses its own connection, so it is safe to use from many threads
        """
        return sqlite3.connect(self.file_path,timeout=10)

    def append(self, new_event: Event) -> None:
        """
        Insert Event into outbox. image_status is 'pending' if the event has an image, else 'none'
        Insert the same event_uuid twice is ignored
        """
        payload=json.dumps(JsonEventMapper.to_dict(new_event),ensure_ascii=False)
        image_status="pending" if new_event.image_path else "none"
        with closing(self._connect()) as conn:
            with conn:
                conn.execute(
                    "INSERT OR IGNORE INTO outbox (event_uuid, payload, image_path, image_status) VALUES (?, ?, ?, ?)",
                    (new_event.event_uuid, payload, new_event.image_path, image_status)
                )

    def get_all(self) -> list[Event]:
        with closing(self._connect()) as conn:
            rows=conn.execute("SELECT payload FROM outbox ORDER BY rowid").fetchall()
        return [JsonEventMapper.from_dict(json.loads(row[0])) for row in rows]

    def get_unsynced_events(self, limit: int) -> list[dict]:
        """
        Get payloads (dict) of events that haven't been sent to server yet
        """
        with closing(self._connect()) as conn:
            rows=conn.execute(
                "SELECT payload FROM outbox WHERE event_synced = 0 ORDER BY rowid LIMIT ?",
                (limit,)
            ).fetchall()
        return [json.loads(row[0]) for row in rows]

    def mark_events_synced(self, event_uuids: list[str]) -> None:
        with closing(self._connect()) as conn:
            with conn:
                conn.executemany(
                    "UPDATE outbox SET event_synced = 1 WHERE event_uuid = ?",
                    [(event_uuid,) for event_uuid in event_uuids]
                )

    def get_pending_images(self, limit: int) -> list[tuple[str, str]]:
        """
        Get (event_uuid, image_path) whose metadata is on server but the image isn't uploaded yet
        """
        with closing(self._connect()) as conn:
            rows=conn.execute(
                "SELECT event_uuid, image_path FROM outbox WHERE event_synced = 1 AND image_status = 'pending' ORDER BY rowid LIMIT ?",
                (limit,)
            ).fetchall()
        return [(row[0], row[1]) for row in rows]

    def mark_image_status(self, event_uuid: str, status: str) -> None:
        """
        status: 'uploaded' or 'missing' (local file is gone, never retry)
        """
        with closing(self._connect()) as conn:
            with conn:
                conn.execute(
                    "UPDATE outbox SET image_status = ? WHERE event_uuid = ?",
                    (status, event_uuid)
                )
