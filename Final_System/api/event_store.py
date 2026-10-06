"""
All database operations of events and devices on the central server
"""
import hashlib
from datetime import datetime
from sqlalchemy import select, update, insert
from sqlalchemy.dialects import postgresql, sqlite
from sqlalchemy.engine import Engine
from ai.reporting.models import Event, get_violation_type
from api.db import metadata, devices, events


def hash_api_key(api_key: str) -> str:
    return hashlib.sha256(api_key.encode("utf-8")).hexdigest()


class EventStore:
    def __init__(self, engine: Engine):
        self._engine = engine

    def init_schema(self) -> None:
        """
        Create tables if they don't exist
        """
        metadata.create_all(self._engine)

    # ------------------------------------------------------------------
    # Devices
    # ------------------------------------------------------------------

    def register_device(self, device_id: str, location: str, api_key: str) -> None:
        """
        Create the device, or update its location and key if it already exists
        """
        with self._engine.begin() as conn:
            exists = conn.execute(select(devices.c.device_id).where(devices.c.device_id == device_id)).first()
            if exists:
                conn.execute(update(devices).where(devices.c.device_id == device_id).values(
                    location=location, api_key_hash=hash_api_key(api_key)))
            else:
                conn.execute(insert(devices).values(
                    device_id=device_id, location=location, api_key_hash=hash_api_key(api_key)))

    def get_device_by_api_key(self, api_key: str) -> dict | None:
        with self._engine.connect() as conn:
            row = conn.execute(
                select(devices.c.device_id, devices.c.location).where(devices.c.api_key_hash == hash_api_key(api_key))
            ).first()
        return {"device_id": row.device_id, "location": row.location} if row else None

    def get_location_map(self) -> dict[str, str]:
        with self._engine.connect() as conn:
            rows = conn.execute(select(devices.c.device_id, devices.c.location)).all()
        return {row.device_id: row.location for row in rows}

    # ------------------------------------------------------------------
    # Events
    # ------------------------------------------------------------------

    def upsert_events(self, device_id: str, new_events: list[dict]) -> None:
        """
        Insert events, an event_uuid that already exists is updated (so retry never creates duplicates).
        device_id comes from the API key, never from the payload.
        The image columns are not touched here, they are changed only by the image endpoints
        """
        if not new_events:
            return
        rows = []
        for item in new_events:
            violation_type = get_violation_type(item["uniform_label"], item["card_label"])
            rows.append({
                "event_uuid": item["event_uuid"],
                "device_id": device_id,
                "session_id": item.get("session_id", ""),
                "track_id": item["track_id"],
                "uniform_label": item["uniform_label"],
                "card_label": item["card_label"],
                "is_violation": violation_type != "none",
                "violation_type": violation_type,
                "first_seen": item["first_seen"],
                "last_seen": item["last_seen"],
                "image_status": "pending" if item.get("has_image") else "none",
            })
        insert_function = postgresql.insert if self._engine.dialect.name == "postgresql" else sqlite.insert
        statement = insert_function(events).values(rows)
        update_columns = ["session_id", "track_id", "uniform_label", "card_label",
                          "is_violation", "violation_type", "first_seen", "last_seen"]
        statement = statement.on_conflict_do_update(
            index_elements=["event_uuid"],
            set_={column: statement.excluded[column] for column in update_columns},
            #Another device can't overwrite this device's event
            where=events.c.device_id == statement.excluded.device_id
        )
        with self._engine.begin() as conn:
            conn.execute(statement)

    def get_event(self, event_uuid: str) -> dict | None:
        with self._engine.connect() as conn:
            row = conn.execute(select(events).where(events.c.event_uuid == event_uuid)).first()
        return dict(row._mapping) if row else None

    def set_image(self, event_uuid: str, image_key: str, status: str) -> None:
        with self._engine.begin() as conn:
            conn.execute(update(events).where(events.c.event_uuid == event_uuid).values(
                image_key=image_key, image_status=status))

    def list_events(self, start: datetime, end: datetime, device_id: str | None = None,
                    violation_only: bool = False, limit: int = 100, offset: int = 0) -> list[dict]:
        """
        Newest first, paginated, for the web page
        """
        statement = select(events).where(events.c.first_seen >= start, events.c.first_seen < end)
        if device_id:
            statement = statement.where(events.c.device_id == device_id)
        if violation_only:
            statement = statement.where(events.c.is_violation.is_(True))
        statement = statement.order_by(events.c.first_seen.desc()).limit(limit).offset(offset)
        with self._engine.connect() as conn:
            rows = conn.execute(statement).all()
        return [dict(row._mapping) for row in rows]

    def get_events_in_range(self, start: datetime, end: datetime) -> list[Event]:
        """
        Only the columns needed for statistics are loaded
        """
        statement = select(
            events.c.event_uuid, events.c.device_id, events.c.session_id, events.c.track_id,
            events.c.uniform_label, events.c.card_label, events.c.first_seen, events.c.last_seen
        ).where(events.c.first_seen >= start, events.c.first_seen < end)
        with self._engine.connect() as conn:
            rows = conn.execute(statement).all()
        return [
            Event(
                track_id=row.track_id,
                uniform_label=row.uniform_label,
                card_label=row.card_label,
                first_seen=row.first_seen,
                last_seen=row.last_seen,
                event_uuid=row.event_uuid,
                device_id=row.device_id,
                session_id=row.session_id
            )
            for row in rows
        ]

    def get_violation_images(self, start: datetime, end: datetime, limit: int) -> list[dict]:
        """
        Violation events that have an uploaded image (used for the report), newest first
        """
        statement = select(events.c.event_uuid, events.c.device_id, events.c.first_seen, events.c.image_key).where(
            events.c.first_seen >= start, events.c.first_seen < end,
            events.c.is_violation.is_(True), events.c.image_status == "uploaded"
        ).order_by(events.c.first_seen.desc()).limit(limit)
        with self._engine.connect() as conn:
            rows = conn.execute(statement).all()
        return [dict(row._mapping) for row in rows]
