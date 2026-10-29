-- =====================================================================
-- Migration 006: Tinh gọn CSDL
-- 1. Bảng CAMERA: Loại bỏ NGUON_VIDEO, KHOA_THIET_BI_HASH, LAN_CUOI_KET_NOI, NGAY_TAO, NGAY_CAP_NHAT.
-- 2. Bảng CT_VIPHAM: Loại bỏ NGAY_TAO.
-- 3. Bảng KHU_VUC: Loại bỏ NGAY_TAO, NGAY_CAP_NHAT.
-- 4. Bảng HOCKY: Loại bỏ NGAY_TAO.
-- 5. Bảng BAN_GHI_VIPHAM: Loại bỏ NGAY_TAO.
-- 6. Bảng ANH_VIPHAM: Loại bỏ NGAY_TAO.
-- 7. Bảng TAIKHOAN: Loại bỏ CREATED_AT.
-- =====================================================================

BEGIN TRANSACTION;

-- Helper procedure để xóa default constraint của 1 cột nếu có
DECLARE @drop_def NVARCHAR(MAX);

-- ---------------------------------------------------------------------
-- 1. CAMERA: NGUON_VIDEO, KHOA_THIET_BI_HASH, LAN_CUOI_KET_NOI, NGAY_TAO, NGAY_CAP_NHAT
-- ---------------------------------------------------------------------
-- Drop default constraints on CAMERA
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.CAMERA DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'CAMERA' AND c.name IN ('LAN_CUOI_KET_NOI', 'NGAY_TAO', 'NGAY_CAP_NHAT', 'NGUON_VIDEO', 'KHOA_THIET_BI_HASH');
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.CAMERA', 'NGUON_VIDEO') IS NOT NULL
    ALTER TABLE dbo.CAMERA DROP COLUMN NGUON_VIDEO;

IF COL_LENGTH('dbo.CAMERA', 'KHOA_THIET_BI_HASH') IS NOT NULL
    ALTER TABLE dbo.CAMERA DROP COLUMN KHOA_THIET_BI_HASH;

IF COL_LENGTH('dbo.CAMERA', 'LAN_CUOI_KET_NOI') IS NOT NULL
    ALTER TABLE dbo.CAMERA DROP COLUMN LAN_CUOI_KET_NOI;

IF COL_LENGTH('dbo.CAMERA', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.CAMERA DROP COLUMN NGAY_TAO;

IF COL_LENGTH('dbo.CAMERA', 'NGAY_CAP_NHAT') IS NOT NULL
    ALTER TABLE dbo.CAMERA DROP COLUMN NGAY_CAP_NHAT;

-- ---------------------------------------------------------------------
-- 2. CT_VIPHAM: NGAY_TAO
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.CT_VIPHAM DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'CT_VIPHAM' AND c.name = 'NGAY_TAO';
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.CT_VIPHAM', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.CT_VIPHAM DROP COLUMN NGAY_TAO;

-- ---------------------------------------------------------------------
-- 3. KHU_VUC: NGAY_TAO, NGAY_CAP_NHAT
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.KHU_VUC DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'KHU_VUC' AND c.name IN ('NGAY_TAO', 'NGAY_CAP_NHAT');
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.KHU_VUC', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.KHU_VUC DROP COLUMN NGAY_TAO;

IF COL_LENGTH('dbo.KHU_VUC', 'NGAY_CAP_NHAT') IS NOT NULL
    ALTER TABLE dbo.KHU_VUC DROP COLUMN NGAY_CAP_NHAT;

-- ---------------------------------------------------------------------
-- 4. HOCKY: NGAY_TAO
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.HOCKY DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'HOCKY' AND c.name = 'NGAY_TAO';
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.HOCKY', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.HOCKY DROP COLUMN NGAY_TAO;

-- ---------------------------------------------------------------------
-- 5. BAN_GHI_VIPHAM: NGAY_TAO
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.BAN_GHI_VIPHAM DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'BAN_GHI_VIPHAM' AND c.name = 'NGAY_TAO';
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.BAN_GHI_VIPHAM', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.BAN_GHI_VIPHAM DROP COLUMN NGAY_TAO;

-- ---------------------------------------------------------------------
-- 6. ANH_VIPHAM: NGAY_TAO
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.ANH_VIPHAM DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'ANH_VIPHAM' AND c.name = 'NGAY_TAO';
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.ANH_VIPHAM', 'NGAY_TAO') IS NOT NULL
    ALTER TABLE dbo.ANH_VIPHAM DROP COLUMN NGAY_TAO;

-- ---------------------------------------------------------------------
-- 7. TAIKHOAN: CREATED_AT
-- ---------------------------------------------------------------------
SET @drop_def = N'';
SELECT @drop_def += N'ALTER TABLE dbo.TAIKHOAN DROP CONSTRAINT ' + QUOTENAME(dc.name) + N';' + CHAR(13)
FROM sys.default_constraints dc
JOIN sys.tables t ON dc.parent_object_id = t.object_id
JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
WHERE t.name = 'TAIKHOAN' AND c.name = 'CREATED_AT';
IF @drop_def <> N'' EXEC sp_executesql @drop_def;

IF COL_LENGTH('dbo.TAIKHOAN', 'CREATED_AT') IS NOT NULL
    ALTER TABLE dbo.TAIKHOAN DROP COLUMN CREATED_AT;

-- ---------------------------------------------------------------------
-- 8. Ghi nhận phiên bản migration
-- ---------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '006')
BEGIN
    INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA, AP_DUNG_LUC)
    VALUES ('006', N'Loại bỏ các cột ngày tạo/cập nhật dư thừa và tối giản bảng CAMERA, CT_VIPHAM', SYSDATETIME());
END

COMMIT TRANSACTION;
