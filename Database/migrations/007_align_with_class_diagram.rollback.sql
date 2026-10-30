-- =====================================================================
-- Migration 007 Rollback: Khôi phục cấu trúc trước khi tinh gọn
-- =====================================================================

BEGIN TRANSACTION;

-- 1. Đổi tên bảng và cột về lại BAN_GHI_VIPHAM
IF OBJECT_ID('dbo.PHAT_HIEN_VIPHAM', 'U') IS NOT NULL
BEGIN
    EXEC sp_rename 'dbo.PHAT_HIEN_VIPHAM', 'BAN_GHI_VIPHAM';
END

IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'TINH_TRANG_XU_LY') IS NOT NULL
BEGIN
    EXEC sp_rename 'dbo.BAN_GHI_VIPHAM.TINH_TRANG_XU_LY', 'TRANG_THAI_XU_LY', 'COLUMN';
END

IF OBJECT_ID('dbo.ANH_VIPHAM', 'U') IS NOT NULL AND COL_LENGTH('dbo.ANH_VIPHAM', 'PHAT_HIEN_ID') IS NOT NULL
BEGIN
    EXEC sp_rename 'dbo.ANH_VIPHAM.PHAT_HIEN_ID', 'BAN_GHI_ID', 'COLUMN';
END

-- 2. Thêm lại các cột đã xóa vào BAN_GHI_VIPHAM (nullable)
IF OBJECT_ID('dbo.BAN_GHI_VIPHAM', 'U') IS NOT NULL
BEGIN
    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'TRACK_ID') IS NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM ADD TRACK_ID INT NULL;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'KHU_VUC_ID') IS NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM ADD KHU_VUC_ID INT NULL;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'CAMERA_ID') IS NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM ADD CAMERA_ID INT NULL;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'NGUOI_XU_LY_ID') IS NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM ADD NGUOI_XU_LY_ID INT NULL;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'GHI_CHU_XU_LY') IS NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM ADD GHI_CHU_XU_LY NVARCHAR(500) NULL;

    -- Backfill lại CAMERA_ID từ ANH_VIPHAM nếu có
    IF OBJECT_ID('dbo.ANH_VIPHAM', 'U') IS NOT NULL AND COL_LENGTH('dbo.ANH_VIPHAM', 'CAMERA_ID') IS NOT NULL
    BEGIN
        EXEC sp_executesql N'
            UPDATE b
            SET b.CAMERA_ID = a.CAMERA_ID
            FROM dbo.BAN_GHI_VIPHAM b
            JOIN dbo.ANH_VIPHAM a ON b.ID = a.BAN_GHI_ID
            WHERE b.CAMERA_ID IS NULL;
        ';
    END
END

-- 3. Xóa bản ghi migration 007
DELETE FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '007';

COMMIT TRANSACTION;
