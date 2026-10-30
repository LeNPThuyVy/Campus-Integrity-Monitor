-- =====================================================================
-- Migration 007: Tinh gọn CSDL đồng bộ với sơ đồ lớp (Class Diagram)
-- 1. Xóa bảng NHAT_KY_XOA_ANH (nếu tồn tại).
-- 2. Bảng ANH_VIPHAM:
--    - Thêm CAMERA_ID, THOI_GIAN_LUU, THOI_GIAN_HET_HAN.
--    - Backfill dữ liệu từ BAN_GHI_VIPHAM và các cột cũ.
--    - Xóa check constraints, indexes và default constraints liên quan đến cột cũ.
--    - Xóa các cột thừa: ANH_SHA256, TRANG_THAI, DA_XOA_LUC, HET_HAN_LUC, NGAY_TAO.
--    - Tạo ràng buộc FK_ANH_VIPHAM_CAMERA và Index mới cho việc dọn dẹp ảnh.
-- 3. Bảng BAN_GHI_VIPHAM:
--    - Xóa indexes, check constraints và foreign keys liên quan đến cột cũ.
--    - Xóa các cột thừa: TRACK_ID, KHU_VUC_ID, CAMERA_ID, NGUOI_XU_LY_ID, GHI_CHU_XU_LY.
--    - Đổi tên cột TRANG_THAI_XU_LY -> TINH_TRANG_XU_LY và tạo check constraint mới.
--    - Đổi tên cột ANH_VIPHAM.BAN_GHI_ID -> PHAT_HIEN_ID.
--    - Đổi tên bảng BAN_GHI_VIPHAM -> PHAT_HIEN_VIPHAM.
-- 4. Bảng TAIKHOAN:
--    - Xóa DOI_MAT_KHAU_LUC, NGAY_CAP_NHAT, EMAIL, KHOA_ID (nếu có).
--    - Giữ lại các cột bảo mật (migration 008 đổi tên thành FAILED_ATTEMPTS, LOCKED_UNTIL, TOKEN_VERSION).
-- =====================================================================

SET NOCOUNT ON;
SET XACT_ABORT ON;
SET QUOTED_IDENTIFIER ON;

BEGIN TRANSACTION;

-- Helper drop default constraint
DECLARE @drop_def NVARCHAR(MAX);

-- ---------------------------------------------------------------------
-- 1. Xóa bảng NHAT_KY_XOA_ANH nếu tồn tại
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.NHAT_KY_XOA_ANH', 'U') IS NOT NULL
    DROP TABLE dbo.NHAT_KY_XOA_ANH;

-- ---------------------------------------------------------------------
-- 2. Cập nhật bảng ANH_VIPHAM
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.ANH_VIPHAM', 'U') IS NOT NULL
BEGIN
    -- Thêm các cột mới nếu chưa có
    IF COL_LENGTH('dbo.ANH_VIPHAM', 'CAMERA_ID') IS NULL
        ALTER TABLE dbo.ANH_VIPHAM ADD CAMERA_ID INT NULL;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'THOI_GIAN_LUU') IS NULL
        ALTER TABLE dbo.ANH_VIPHAM ADD THOI_GIAN_LUU DATETIME2(0) NULL;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'THOI_GIAN_HET_HAN') IS NULL
        ALTER TABLE dbo.ANH_VIPHAM ADD THOI_GIAN_HET_HAN DATETIME2(0) NULL;

    -- Backfill CAMERA_ID từ BAN_GHI_VIPHAM nếu cột CAMERA_ID ở BAN_GHI_VIPHAM còn tồn tại
    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'CAMERA_ID') IS NOT NULL
    BEGIN
        EXEC sp_executesql N'
            UPDATE a
            SET a.CAMERA_ID = b.CAMERA_ID
            FROM dbo.ANH_VIPHAM a
            JOIN dbo.BAN_GHI_VIPHAM b ON a.BAN_GHI_ID = b.ID
            WHERE a.CAMERA_ID IS NULL;
        ';
    END

    -- Backfill THOI_GIAN_HET_HAN từ HET_HAN_LUC
    IF COL_LENGTH('dbo.ANH_VIPHAM', 'HET_HAN_LUC') IS NOT NULL
    BEGIN
        EXEC sp_executesql N'
            UPDATE dbo.ANH_VIPHAM
            SET THOI_GIAN_HET_HAN = HET_HAN_LUC
            WHERE THOI_GIAN_HET_HAN IS NULL AND HET_HAN_LUC IS NOT NULL;
        ';
    END

    -- Backfill THOI_GIAN_LUU từ NGAY_TAO hoặc THOI_DIEM của vi phạm
    IF COL_LENGTH('dbo.ANH_VIPHAM', 'NGAY_TAO') IS NOT NULL
    BEGIN
        EXEC sp_executesql N'
            UPDATE dbo.ANH_VIPHAM
            SET THOI_GIAN_LUU = NGAY_TAO
            WHERE THOI_GIAN_LUU IS NULL;
        ';
    END

    IF OBJECT_ID('dbo.BAN_GHI_VIPHAM', 'U') IS NOT NULL AND COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'THOI_DIEM') IS NOT NULL
    BEGIN
        EXEC sp_executesql N'
            UPDATE a
            SET a.THOI_GIAN_LUU = b.THOI_DIEM
            FROM dbo.ANH_VIPHAM a
            JOIN dbo.BAN_GHI_VIPHAM b ON a.BAN_GHI_ID = b.ID
            WHERE a.THOI_GIAN_LUU IS NULL;
        ';
    END

    -- Drop check constraint trên TRANG_THAI nếu có
    IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_ANH_VIPHAM_TRANG_THAI')
        ALTER TABLE dbo.ANH_VIPHAM DROP CONSTRAINT CK_ANH_VIPHAM_TRANG_THAI;

    -- Drop index cũ nếu có
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_ANH_VIPHAM_HET_HAN' AND object_id = OBJECT_ID('dbo.ANH_VIPHAM'))
        DROP INDEX IX_ANH_VIPHAM_HET_HAN ON dbo.ANH_VIPHAM;

    -- Drop default constraints trên các cột sắp xóa
    SET @drop_def = N'';
    SELECT @drop_def += N'ALTER TABLE dbo.ANH_VIPHAM DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
    FROM sys.default_constraints dc
    JOIN sys.tables t ON dc.parent_object_id = t.object_id
    JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    WHERE t.name = 'ANH_VIPHAM' AND c.name IN ('ANH_SHA256', 'TRANG_THAI', 'DA_XOA_LUC', 'HET_HAN_LUC', 'NGAY_TAO');
    IF @drop_def <> N'' EXEC sp_executesql @drop_def;

    -- Drop các cột thừa
    IF COL_LENGTH('dbo.ANH_VIPHAM', 'ANH_SHA256') IS NOT NULL
        ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN ANH_SHA256;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'TRANG_THAI') IS NOT NULL
        ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN TRANG_THAI;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'DA_XOA_LUC') IS NOT NULL
        ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN DA_XOA_LUC;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'HET_HAN_LUC') IS NOT NULL
        ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN HET_HAN_LUC;

    IF COL_LENGTH('dbo.ANH_VIPHAM', 'NGAY_TAO') IS NOT NULL
        ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN NGAY_TAO;

    -- Thiết lập FK sang CAMERA nếu chưa có
    IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_ANH_VIPHAM_CAMERA')
    BEGIN
        ALTER TABLE dbo.ANH_VIPHAM
        ADD CONSTRAINT FK_ANH_VIPHAM_CAMERA FOREIGN KEY (CAMERA_ID) REFERENCES dbo.CAMERA(ID);
    END

    -- Tạo index phục vụ việc dọn dẹp ảnh hết hạn (Cloudflare R2 Retention Purge)
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_ANH_VIPHAM_THOI_GIAN_HET_HAN' AND object_id = OBJECT_ID('dbo.ANH_VIPHAM'))
    BEGIN
        CREATE NONCLUSTERED INDEX IX_ANH_VIPHAM_THOI_GIAN_HET_HAN
        ON dbo.ANH_VIPHAM (THOI_GIAN_HET_HAN)
        WHERE ANH_KHOA IS NOT NULL;
    END
END

-- ---------------------------------------------------------------------
-- 3. Cập nhật bảng BAN_GHI_VIPHAM và đổi tên sang PHAT_HIEN_VIPHAM
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.BAN_GHI_VIPHAM', 'U') IS NOT NULL
BEGIN
    -- Drop các FK constraints trên BAN_GHI_VIPHAM nếu có
    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_BAN_GHI_VIPHAM_KHU_VUC')
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT FK_BAN_GHI_VIPHAM_KHU_VUC;

    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_BAN_GHI_VIPHAM_CAMERA')
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT FK_BAN_GHI_VIPHAM_CAMERA;

    IF EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_BAN_GHI_VIPHAM_NGUOI_XU_LY')
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT FK_BAN_GHI_VIPHAM_NGUOI_XU_LY;

    -- Drop check constraint trên TRANG_THAI_XU_LY nếu có
    IF EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_BAN_GHI_VIPHAM_TRANG_THAI_XU_LY')
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT CK_BAN_GHI_VIPHAM_TRANG_THAI_XU_LY;

    -- Drop các index liên quan đến cột sắp xóa
    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_BAN_GHI_VIPHAM_THOI_DIEM' AND object_id = OBJECT_ID('dbo.BAN_GHI_VIPHAM'))
        DROP INDEX IX_BAN_GHI_VIPHAM_THOI_DIEM ON dbo.BAN_GHI_VIPHAM;

    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_BAN_GHI_VIPHAM_KHU_VUC_THOI_DIEM' AND object_id = OBJECT_ID('dbo.BAN_GHI_VIPHAM'))
        DROP INDEX IX_BAN_GHI_VIPHAM_KHU_VUC_THOI_DIEM ON dbo.BAN_GHI_VIPHAM;

    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_BAN_GHI_VIPHAM_KHU_VUC' AND object_id = OBJECT_ID('dbo.BAN_GHI_VIPHAM'))
        DROP INDEX IX_BAN_GHI_VIPHAM_KHU_VUC ON dbo.BAN_GHI_VIPHAM;

    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_BAN_GHI_VIPHAM_CAMERA' AND object_id = OBJECT_ID('dbo.BAN_GHI_VIPHAM'))
        DROP INDEX IX_BAN_GHI_VIPHAM_CAMERA ON dbo.BAN_GHI_VIPHAM;

    -- Drop default constraints
    SET @drop_def = N'';
    SELECT @drop_def += N'ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
    FROM sys.default_constraints dc
    JOIN sys.tables t ON dc.parent_object_id = t.object_id
    JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    WHERE t.name = 'BAN_GHI_VIPHAM' AND c.name IN ('TRACK_ID', 'KHU_VUC_ID', 'CAMERA_ID', 'NGUOI_XU_LY_ID', 'GHI_CHU_XU_LY', 'TRANG_THAI_XU_LY');
    IF @drop_def <> N'' EXEC sp_executesql @drop_def;

    -- Drop các cột thừa
    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'TRACK_ID') IS NOT NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN TRACK_ID;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'KHU_VUC_ID') IS NOT NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN KHU_VUC_ID;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'CAMERA_ID') IS NOT NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN CAMERA_ID;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'NGUOI_XU_LY_ID') IS NOT NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN NGUOI_XU_LY_ID;

    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'GHI_CHU_XU_LY') IS NOT NULL
        ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN GHI_CHU_XU_LY;

    -- Đổi tên cột TRANG_THAI_XU_LY -> TINH_TRANG_XU_LY
    IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'TRANG_THAI_XU_LY') IS NOT NULL
    BEGIN
        EXEC sp_rename 'dbo.BAN_GHI_VIPHAM.TRANG_THAI_XU_LY', 'TINH_TRANG_XU_LY', 'COLUMN';
    END

    -- Gán default constraint mới cho TINH_TRANG_XU_LY nếu chưa có
    IF NOT EXISTS (
        SELECT 1 FROM sys.default_constraints dc
        JOIN sys.tables t ON dc.parent_object_id = t.object_id
        JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
        WHERE t.name = 'BAN_GHI_VIPHAM' AND c.name = 'TINH_TRANG_XU_LY'
    )
    BEGIN
        EXEC sp_executesql N'ALTER TABLE dbo.BAN_GHI_VIPHAM ADD CONSTRAINT DF_PHAT_HIEN_VIPHAM_TINH_TRANG DEFAULT (''MOI'') FOR TINH_TRANG_XU_LY;';
    END

    -- Gán check constraint mới cho TINH_TRANG_XU_LY
    IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'CK_PHAT_HIEN_VIPHAM_TINH_TRANG')
    BEGIN
        EXEC sp_executesql N'ALTER TABLE dbo.BAN_GHI_VIPHAM ADD CONSTRAINT CK_PHAT_HIEN_VIPHAM_TINH_TRANG CHECK (TINH_TRANG_XU_LY IN (''MOI'', ''DA_XEM'', ''DA_XU_LY'', ''BO_QUA''));';
    END

    -- Tạo index trên THOI_DIEM, LOI_ID
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_PHAT_HIEN_VIPHAM_THOI_DIEM' AND object_id = OBJECT_ID('dbo.BAN_GHI_VIPHAM'))
    BEGIN
        CREATE NONCLUSTERED INDEX IX_PHAT_HIEN_VIPHAM_THOI_DIEM
        ON dbo.BAN_GHI_VIPHAM (THOI_DIEM, LOI_ID);
    END

    -- Đổi tên cột ANH_VIPHAM.BAN_GHI_ID -> PHAT_HIEN_ID
    IF COL_LENGTH('dbo.ANH_VIPHAM', 'BAN_GHI_ID') IS NOT NULL
    BEGIN
        EXEC sp_rename 'dbo.ANH_VIPHAM.BAN_GHI_ID', 'PHAT_HIEN_ID', 'COLUMN';
    END

    -- Đổi tên bảng BAN_GHI_VIPHAM -> PHAT_HIEN_VIPHAM
    EXEC sp_rename 'dbo.BAN_GHI_VIPHAM', 'PHAT_HIEN_VIPHAM';
END

-- ---------------------------------------------------------------------
-- 4. Bảng TAIKHOAN: Xóa cột thừa, giữ các cột bảo mật
-- ---------------------------------------------------------------------
IF OBJECT_ID('dbo.TAIKHOAN', 'U') IS NOT NULL
BEGIN
    SET @drop_def = N'';
    SELECT @drop_def += N'ALTER TABLE dbo.TAIKHOAN DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
    FROM sys.default_constraints dc
    JOIN sys.tables t ON dc.parent_object_id = t.object_id
    JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
    WHERE t.name = 'TAIKHOAN' AND c.name IN ('DOI_MAT_KHAU_LUC', 'NGAY_CAP_NHAT', 'EMAIL', 'KHOA_ID');
    IF @drop_def <> N'' EXEC sp_executesql @drop_def;

    IF COL_LENGTH('dbo.TAIKHOAN', 'DOI_MAT_KHAU_LUC') IS NOT NULL
        ALTER TABLE dbo.TAIKHOAN DROP COLUMN DOI_MAT_KHAU_LUC;

    IF COL_LENGTH('dbo.TAIKHOAN', 'NGAY_CAP_NHAT') IS NOT NULL
        ALTER TABLE dbo.TAIKHOAN DROP COLUMN NGAY_CAP_NHAT;

    IF COL_LENGTH('dbo.TAIKHOAN', 'EMAIL') IS NOT NULL
        ALTER TABLE dbo.TAIKHOAN DROP COLUMN EMAIL;

    IF COL_LENGTH('dbo.TAIKHOAN', 'KHOA_ID') IS NOT NULL
        ALTER TABLE dbo.TAIKHOAN DROP COLUMN KHOA_ID;
END

-- ---------------------------------------------------------------------
-- 5. Ghi nhận phiên bản migration 007
-- ---------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '007')
BEGIN
    INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA, AP_DUNG_LUC)
    VALUES ('007', N'Tinh gọn CSDL chuẩn hóa theo sơ đồ lớp: đổi tên PHAT_HIEN_VIPHAM, chuyển CAMERA_ID sang ANH_VIPHAM, xóa cột thừa', SYSDATETIME());
END

COMMIT TRANSACTION;
