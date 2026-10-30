/*
  Migration 008 - Vá lỗi sau migration 007 và bổ sung cột bảo mật.
  1. ANH_VIPHAM.ANH_KHOA cho phép NULL (NULL = ảnh đã bị xóa/không còn) — 007 bỏ cột TRANG_THAI
     nhưng không đổi ràng buộc NOT NULL nên retention/đánh dấu ảnh mất bị lỗi 515.
  2. Đổi tên ràng buộc còn mang tên cũ BAN_GHI_* sang PHAT_HIEN_*.
  3. TAIKHOAN: đổi cột tiếng Việt của migration 001 (TEN_DANG_NHAP, VAI_TRO, ...) sang tên mới
     (USERNAME, ROLE, ...) mà API và Laravel dùng.
  4. TAIKHOAN: thêm FAILED_ATTEMPTS (chống dò mật khẩu) và TOKEN_VERSION (thu hồi phiên khi
     đổi mật khẩu / vô hiệu hóa / đổi quyền) nếu chưa có.
  5. CAMERA: thêm KHOA_THIET_BI_HASH (SHA-256 của khóa bí mật thiết bị gửi qua X-Device-Key).
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.PHAT_HIEN_VIPHAM', N'U') IS NULL OR OBJECT_ID(N'dbo.ANH_VIPHAM', N'U') IS NULL
    THROW 50008, N'Chưa có bảng PHAT_HIEN_VIPHAM/ANH_VIPHAM. Hãy chạy migration 007 trước.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    -- 1. ANH_KHOA cho phép NULL (phải gỡ index lọc trên cột trước khi ALTER COLUMN)
    IF EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID(N'dbo.ANH_VIPHAM')
               AND name = N'ANH_KHOA' AND is_nullable = 0)
    BEGIN
        IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_ANH_VIPHAM_THOI_GIAN_HET_HAN'
                   AND object_id = OBJECT_ID(N'dbo.ANH_VIPHAM'))
            DROP INDEX IX_ANH_VIPHAM_THOI_GIAN_HET_HAN ON dbo.ANH_VIPHAM;

        ALTER TABLE dbo.ANH_VIPHAM ALTER COLUMN ANH_KHOA NVARCHAR(260) NULL;
    END;

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_ANH_VIPHAM_THOI_GIAN_HET_HAN'
                   AND object_id = OBJECT_ID(N'dbo.ANH_VIPHAM'))
        EXEC sp_executesql N'CREATE NONCLUSTERED INDEX IX_ANH_VIPHAM_THOI_GIAN_HET_HAN
            ON dbo.ANH_VIPHAM (THOI_GIAN_HET_HAN) WHERE ANH_KHOA IS NOT NULL;';

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_ANH_VIPHAM_CAMERA'
                   AND object_id = OBJECT_ID(N'dbo.ANH_VIPHAM'))
        EXEC sp_executesql N'CREATE NONCLUSTERED INDEX IX_ANH_VIPHAM_CAMERA ON dbo.ANH_VIPHAM (CAMERA_ID);';

    -- 2. Đổi tên ràng buộc cũ
    IF OBJECT_ID(N'dbo.UQ_ANH_VIPHAM_BAN_GHI', N'UQ') IS NOT NULL
        EXEC sp_rename N'dbo.UQ_ANH_VIPHAM_BAN_GHI', N'UQ_ANH_VIPHAM_PHAT_HIEN', N'OBJECT';
    IF OBJECT_ID(N'dbo.FK_ANH_VIPHAM_BAN_GHI', N'F') IS NOT NULL
        EXEC sp_rename N'dbo.FK_ANH_VIPHAM_BAN_GHI', N'FK_ANH_VIPHAM_PHAT_HIEN', N'OBJECT';
    IF OBJECT_ID(N'dbo.UQ_BAN_GHI_VIPHAM_MA_SU_KIEN', N'UQ') IS NOT NULL
        EXEC sp_rename N'dbo.UQ_BAN_GHI_VIPHAM_MA_SU_KIEN', N'UQ_PHAT_HIEN_VIPHAM_MA_SU_KIEN', N'OBJECT';
    IF OBJECT_ID(N'dbo.FK_BAN_GHI_VIPHAM_LOI', N'F') IS NOT NULL
        EXEC sp_rename N'dbo.FK_BAN_GHI_VIPHAM_LOI', N'FK_PHAT_HIEN_VIPHAM_LOI', N'OBJECT';
    IF OBJECT_ID(N'dbo.PK_BAN_GHI_VIPHAM', N'PK') IS NOT NULL
        EXEC sp_rename N'dbo.PK_BAN_GHI_VIPHAM', N'PK_PHAT_HIEN_VIPHAM', N'OBJECT';

    -- 3. TAIKHOAN: đổi cột tiếng Việt do migration 001 tạo sang tên mà API/Laravel dùng
    --    (DB tạo từ documents/SQL_Querysql.sql đã có tên mới nên bước này bỏ qua).
    DECLARE @renames TABLE (OLD_NAME SYSNAME, NEW_NAME SYSNAME);
    INSERT INTO @renames VALUES
        (N'TEN_DANG_NHAP', N'USERNAME'), (N'HO_TEN', N'FULL_NAME'), (N'MAT_KHAU_HASH', N'PASSWORD_HASH'),
        (N'VAI_TRO', N'ROLE'), (N'DANG_HOAT_DONG', N'ACTIVE'), (N'KHOA_DEN', N'LOCKED_UNTIL'),
        (N'DANG_NHAP_CUOI', N'LAST_LOGIN_AT'), (N'SO_LAN_SAI', N'FAILED_ATTEMPTS'),
        (N'PHIEN_BAN_TOKEN', N'TOKEN_VERSION');
    DECLARE @old SYSNAME, @new SYSNAME, @path NVARCHAR(300);
    DECLARE rename_cursor CURSOR LOCAL FAST_FORWARD FOR SELECT OLD_NAME, NEW_NAME FROM @renames;
    OPEN rename_cursor;
    FETCH NEXT FROM rename_cursor INTO @old, @new;
    WHILE @@FETCH_STATUS = 0
    BEGIN
        IF COL_LENGTH(N'dbo.TAIKHOAN', @old) IS NOT NULL AND COL_LENGTH(N'dbo.TAIKHOAN', @new) IS NULL
        BEGIN
            SET @path = N'dbo.TAIKHOAN.' + @old;
            EXEC sp_rename @path, @new, N'COLUMN';
        END;
        FETCH NEXT FROM rename_cursor INTO @old, @new;
    END;
    CLOSE rename_cursor;
    DEALLOCATE rename_cursor;

    -- 4. TAIKHOAN: cột bảo mật
    IF COL_LENGTH(N'dbo.TAIKHOAN', N'FAILED_ATTEMPTS') IS NULL
        ALTER TABLE dbo.TAIKHOAN ADD FAILED_ATTEMPTS INT NOT NULL
            CONSTRAINT DF_TAIKHOAN_FAILED_ATTEMPTS DEFAULT (0);
    IF COL_LENGTH(N'dbo.TAIKHOAN', N'TOKEN_VERSION') IS NULL
        ALTER TABLE dbo.TAIKHOAN ADD TOKEN_VERSION INT NOT NULL
            CONSTRAINT DF_TAIKHOAN_TOKEN_VERSION DEFAULT (1);

    -- 5. CAMERA: khóa bí mật thiết bị
    IF COL_LENGTH(N'dbo.CAMERA', N'KHOA_THIET_BI_HASH') IS NULL
        ALTER TABLE dbo.CAMERA ADD KHOA_THIET_BI_HASH CHAR(64) NULL;

    IF NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '008')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('008', N'ANH_KHOA cho phép NULL, đổi tên ràng buộc, chuẩn hóa tên cột TAIKHOAN, cột bảo mật TAIKHOAN/CAMERA');

    COMMIT TRANSACTION;
    PRINT N'Migration 008 áp dụng thành công.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
