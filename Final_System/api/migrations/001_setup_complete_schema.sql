-- ==============================================================================
-- Campus Integrity Monitor - Complete Database Schema (Supabase / PostgreSQL)
-- Chạy script này trên Supabase SQL Editor (chỉ cần chạy 1 lần)
-- ==============================================================================

-- 1. Bảng quản lý thiết bị camera / edge device
CREATE TABLE IF NOT EXISTS devices (
    device_id TEXT PRIMARY KEY,
    location TEXT NOT NULL,
    api_key_hash TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- 2. Bảng lưu trữ sự kiện vi phạm / giám sát (Events)
CREATE TABLE IF NOT EXISTS events (
    event_uuid TEXT PRIMARY KEY,
    device_id TEXT NOT NULL REFERENCES devices(device_id) ON DELETE CASCADE,
    session_id TEXT NOT NULL,
    track_id INTEGER NOT NULL,
    uniform_label TEXT NOT NULL,
    card_label TEXT NOT NULL,
    is_violation BOOLEAN NOT NULL,
    violation_type TEXT NOT NULL,
    first_seen TIMESTAMP WITH TIME ZONE NOT NULL,
    last_seen TIMESTAMP WITH TIME ZONE NOT NULL,
    image_key TEXT,
    image_status TEXT NOT NULL DEFAULT 'none',
    review_status TEXT NOT NULL DEFAULT 'pending', -- pending | approved | rejected
    reviewed_by TEXT,
    reviewed_at TIMESTAMP WITH TIME ZONE
);

CREATE INDEX IF NOT EXISTS idx_events_time ON events(first_seen);
CREATE INDEX IF NOT EXISTS idx_events_dev_time ON events(device_id, first_seen);
CREATE INDEX IF NOT EXISTS idx_events_review ON events(review_status);

-- 3. Bảng quản trị viên hệ thống (Admin Users)
CREATE TABLE IF NOT EXISTS admin_users (
    username TEXT PRIMARY KEY,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW()
);

-- 4. Bảng ghi nhật ký truy cập (Access Logs)
CREATE TABLE IF NOT EXISTS access_logs (
    log_id SERIAL PRIMARY KEY,
    timestamp TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    ip_address TEXT,
    user_agent TEXT,
    action TEXT NOT NULL,
    username TEXT
);

-- 5. Bật Row Level Security (RLS) bảo vệ dữ liệu trước các request client trực tiếp
ALTER TABLE devices ENABLE ROW LEVEL SECURITY;
ALTER TABLE events ENABLE ROW LEVEL SECURITY;
ALTER TABLE admin_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE access_logs ENABLE ROW LEVEL SECURITY;
