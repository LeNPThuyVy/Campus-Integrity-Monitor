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
    Column("violation_type", Text, nullable=False),
    Column("first_seen", DateTime(timezone=True), nullable=False),
    Column("last_seen", DateTime(timezone=True), nullable=False),
    Column("image_key", Text),
    Column("image_status", Text, nullable=False, server_default="none"),
    Column("review_status", Text, nullable=False, server_default="pending"), # pending | approved | rejected
    Column("reviewed_by", Text),
    Column("reviewed_at", DateTime(timezone=True)),
    Index("idx_events_time", "first_seen"),
    Index("idx_events_dev_time", "device_id", "first_seen"),
)

admin_users = Table(
    "admin_users", metadata,
    Column("username", Text, primary_key=True),
    Column("password_hash", Text, nullable=False),
    Column("created_at", DateTime(timezone=True), nullable=False),
)

access_logs = Table(
    "access_logs", metadata,
    Column("log_id", Integer, primary_key=True, autoincrement=True),
    Column("timestamp", DateTime(timezone=True), nullable=False),
    Column("ip_address", Text),
    Column("action", Text, nullable=False),
    Column("username", Text),
)


def create_db_engine(database_url: str) -> Engine:
    """
    database_url example: postgresql+psycopg://user:password@host:5432/dbname
    pool_pre_ping drops dead connections automatically
    """
    connect_args = {}
    if database_url.startswith("postgresql"):
        # For Supabase pooler (pgbouncer), we need to disable prepared statements
        connect_args["prepare_threshold"] = None
        return create_engine(database_url, pool_pre_ping=True, pool_size=5, max_overflow=5, connect_args=connect_args)
    return create_engine(database_url, pool_pre_ping=True)
