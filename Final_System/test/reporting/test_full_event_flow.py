import unittest
from datetime import datetime, timedelta, timezone
from sqlalchemy import create_engine
from api.event_store import EventStore, hash_api_key
from ai.reporting.models import Event
from ai.reporting.report_stats import build_statistics


class TestFullEventFlow(unittest.TestCase):
    def setUp(self):
        # Dùng SQLite in-memory để test nhanh và độc lập
        self.engine = create_engine("sqlite:///:memory:")
        self.store = EventStore(self.engine)
        self.store.init_schema()

    def test_device_registration(self):
        self.store.register_device("cam-01", "Cổng chính", "secret-key-123")
        device = self.store.get_device_by_api_key("secret-key-123")
        self.assertIsNotNone(device)
        self.assertEqual(device["device_id"], "cam-01")
        self.assertEqual(device["location"], "Cổng chính")

        # Key sai
        self.assertIsNone(self.store.get_device_by_api_key("wrong-key"))

        # Liệt kê thiết bị
        devices = self.store.list_devices()
        self.assertEqual(len(devices), 1)
        self.assertEqual(devices[0]["device_id"], "cam-01")

    def test_event_upsert_and_review(self):
        self.store.register_device("cam-01", "Cổng chính", "key-1")
        now = datetime.now(timezone.utc)

        events_batch = [
            {
                "event_uuid": "evt-001",
                "track_id": 1,
                "session_id": "sess-1",
                "uniform_label": "Non_Uniform",
                "card_label": "Card",
                "first_seen": now - timedelta(minutes=5),
                "last_seen": now - timedelta(minutes=4),
                "has_image": True
            },
            {
                "event_uuid": "evt-002",
                "track_id": 2,
                "session_id": "sess-1",
                "uniform_label": "Uniform",
                "card_label": "No_Card",
                "first_seen": now - timedelta(minutes=3),
                "last_seen": now - timedelta(minutes=2),
                "has_image": True
            }
        ]

        self.store.upsert_events("cam-01", events_batch)

        # Lấy chi tiết event
        e1 = self.store.get_event("evt-001")
        self.assertIsNotNone(e1)
        self.assertEqual(e1["violation_type"], "uniform")
        self.assertTrue(e1["is_violation"])
        self.assertEqual(e1["image_status"], "pending")

        # Cập nhật ảnh
        self.store.set_image("evt-001", "cam-01/2026/10/13/evt-001.jpg", "uploaded")
        e1_updated = self.store.get_event("evt-001")
        self.assertEqual(e1_updated["image_status"], "uploaded")
        self.assertEqual(e1_updated["image_key"], "cam-01/2026/10/13/evt-001.jpg")

        # Kiểm tra get_violation_images trước khi review
        imgs = self.store.get_violation_images(now - timedelta(hours=1), now, limit=10)
        self.assertEqual(len(imgs), 1)
        self.assertEqual(imgs[0]["event_uuid"], "evt-001")

        # Admin reject evt-001
        self.store.set_review("evt-001", "rejected", "admin")
        e1_reviewed = self.store.get_event("evt-001")
        self.assertEqual(e1_reviewed["review_status"], "rejected")
        self.assertEqual(e1_reviewed["reviewed_by"], "admin")
        self.assertIsNotNone(e1_reviewed["reviewed_at"])

        # Sau khi reject, get_violation_images không được chứa evt-001
        imgs_after_reject = self.store.get_violation_images(now - timedelta(hours=1), now, limit=10)
        self.assertEqual(len(imgs_after_reject), 0)

        # get_events_in_range cho thống kê cũng không được chứa evt-001
        stat_events = self.store.get_events_in_range(now - timedelta(hours=1), now)
        self.assertEqual(len(stat_events), 1)
        self.assertEqual(stat_events[0].event_uuid, "evt-002")

    def test_log_access(self):
        self.store.log_access("admin", "127.0.0.1", "Mozilla/5.0", "login")
        with self.engine.connect() as conn:
            from sqlalchemy import text
            row = conn.execute(text("SELECT username, ip_address, user_agent, action FROM access_logs")).first()
            self.assertIsNotNone(row)
            self.assertEqual(row[0], "admin")
            self.assertEqual(row[1], "127.0.0.1")
            self.assertEqual(row[2], "Mozilla/5.0")
            self.assertEqual(row[3], "login")

    def test_retention_and_orphan_images(self):
        self.store.register_device("cam-01", "Cổng chính", "key-1")
        old_time = datetime.now(timezone.utc) - timedelta(days=40)

        events_batch = [
            {
                "event_uuid": "old-evt",
                "track_id": 1,
                "session_id": "sess-old",
                "uniform_label": "Non_Uniform",
                "card_label": "No_Card",
                "first_seen": old_time,
                "last_seen": old_time + timedelta(seconds=10),
                "has_image": True
            }
        ]
        self.store.upsert_events("cam-01", events_batch)
        self.store.set_image("old-evt", "cam-01/2026/08/01/old-evt.jpg", "uploaded")

        cutoff = datetime.now(timezone.utc) - timedelta(days=30)
        orphan_keys = self.store.get_orphan_image_keys_before(cutoff)
        self.assertEqual(orphan_keys, ["cam-01/2026/08/01/old-evt.jpg"])

        deleted = self.store.delete_events_before(cutoff)
        self.assertEqual(deleted, 1)

        # Bản ghi cũ đã bị xóa
        self.assertIsNone(self.store.get_event("old-evt"))


if __name__ == "__main__":
    unittest.main()
