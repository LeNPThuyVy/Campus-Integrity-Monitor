"""
Database tables of the central server (SQLAlchemy Core).
Works on PostgreSQL (production). SQLite is only used in unit tests.
"""
from sqlalchemy import MetaData, Table, Column, Text, Integer, Boolean, DateTime, ForeignKey, Index, create_engine
from sqlalchemy.engine import Engine

metadata = MetaData()

devices = Table(
    "devices", metadata,
    Column("device_id", Text, primary_key=True),
    Column("location", Text, nullable=False),
    Column("api_key_hash", Text, nullable=False),
)

events = Table(
    "events", metadata,
    Column("event_uuid", Text, primary_key=True),
    Column("device_id", Text, ForeignKey("devices.device_id"), nullable=False),
    Column("session_id", Text, nullable=False),
    Column("track_id", Integer, nullable=False),
    Column("uniform_label", Text, nullable=False),
    Column("card_label", Text, nullable=False),
    Column("is_violation", Boolean, nullable=False),
    Column("violation_type", Text, nullable=False),  # none | uniform | card | both
    Column("first_seen", DateTime(timezone=True), nullable=False),
    Column("last_seen", DateTime(timezone=True), nullable=False),
    Column("image_key", Text),
    Column("image_status", Text, nullable=False, server_default="none"),  # none | pending | uploaded
    Index("idx_events_time", "first_seen"),
    Index("idx_events_dev_time", "device_id", "first_seen"),
)


def create_db_engine(database_url: str) -> Engine:
    """
    database_url example: postgresql+psycopg://user:password@host:5432/dbname
    pool_pre_ping drops dead connections automatically
    """
    return create_engine(database_url, pool_pre_ping=True)
