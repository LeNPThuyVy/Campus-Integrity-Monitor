/*
  Migration 004 - Chuẩn hóa bảng HOCKY (bỏ HIEN_TAI, tự động xác định học kỳ qua ngày bắt đầu/kết thúc)
  và liên kết khóa ngoại MA_HK vào bảng CT_VIPHAM.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.HOCKY', N'U') IS NULL OR OBJECT_ID(N'dbo.CT_VIPHAM', N'U') IS NULL
    THROW 50004, N'Chưa có bảng HOCKY hoặc CT_VIPHAM. Hãy kiểm tra các migration trước.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    -- 1. Chuẩn hóa dữ liệu mẫu nếu có sai sót ngày bắt đầu của HK1-2025
    UPDATE dbo.HOCKY 
    SET TEN_HK = N'Học kỳ 1 (2025-2026)', 
        NGAY_BAT_DAU = '2025-09-01', 
        NGAY_KET_THUC = '2026-01-15'
    WHERE MA_HK = 'HK1-2025' AND NGAY_BAT_DAU = '2026-09-01';

    -- 2. Xóa default constraint trên cột HIEN_TAI (nếu có) trước khi xóa cột
    DECLARE @dfHienTai SYSNAME;
    SELECT @dfHienTai = d.name
    FROM sys.default_constraints d
    INNER JOIN sys.columns c ON d.parent_object_id = c.object_id AND d.parent_column_id = c.column_id
    WHERE d.parent_object_id = OBJECT_ID(N'dbo.HOCKY') AND c.name = N'HIEN_TAI';

    IF @dfHienTai IS NOT NULL
    BEGIN
        DECLARE @dropDfSql NVARCHAR(400) = N'ALTER TABLE dbo.HOCKY DROP CONSTRAINT ' + QUOTENAME(@dfHienTai) + N';';
        EXEC sys.sp_executesql @dropDfSql;
    END

    -- 3. Xóa cột HIEN_TAI trong bảng HOCKY
    IF COL_LENGTH(N'dbo.HOCKY', N'HIEN_TAI') IS NOT NULL
    BEGIN
        ALTER TABLE dbo.HOCKY DROP COLUMN HIEN_TAI;
    END

    -- 4. Dọn dẹp bảng HOC_KY trống dư thừa (nếu có) để thống nhất 1 bảng HOCKY duy nhất
    IF OBJECT_ID(N'dbo.HOC_KY', N'U') IS NOT NULL
    BEGIN
        DROP TABLE dbo.HOC_KY;
    END

    -- 5. Đảm bảo ràng buộc UNIQUE trên MA_HK để làm đích tham chiếu khóa ngoại
    IF NOT EXISTS (
        SELECT 1 FROM sys.indexes i
        WHERE i.object_id = OBJECT_ID(N'dbo.HOCKY') AND (i.is_unique = 1 OR i.is_unique_constraint = 1)
        AND EXISTS (
            SELECT 1 FROM sys.index_columns ic
            INNER JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
            WHERE ic.object_id = i.object_id AND ic.index_id = i.index_id AND c.name = 'MA_HK'
        )
    )
    BEGIN
        ALTER TABLE dbo.HOCKY ADD CONSTRAINT UQ_HOCKY_MA_HK UNIQUE (MA_HK);
    END

    -- 6. Đảm bảo ràng buộc ngày kết thúc >= ngày bắt đầu
    IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID(N'dbo.HOCKY') AND name = N'CK_HOCKY_KHOANG_NGAY')
    BEGIN
        ALTER TABLE dbo.HOCKY ADD CONSTRAINT CK_HOCKY_KHOANG_NGAY CHECK (NGAY_KET_THUC >= NGAY_BAT_DAU);
    END

    -- 7. Thêm cột MA_HK vào bảng CT_VIPHAM
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'MA_HK') IS NULL
    BEGIN
        ALTER TABLE dbo.CT_VIPHAM ADD MA_HK VARCHAR(20) NULL;
    END

    -- 8. Đồng bộ (backfill) dữ liệu cũ trong CT_VIPHAM dựa theo khoảng ngày của học kỳ
    EXEC(N'
        UPDATE cv
        SET cv.MA_HK = hk.MA_HK
        FROM dbo.CT_VIPHAM cv
        INNER JOIN dbo.HOCKY hk
            ON cv.NGAY_VIPHAM BETWEEN hk.NGAY_BAT_DAU AND hk.NGAY_KET_THUC
        WHERE cv.MA_HK IS NULL;
    ');

    -- 9. Tạo khóa ngoại FK_CT_VIPHAM_HOCKY liên kết CT_VIPHAM(MA_HK) -> HOCKY(MA_HK)
    IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE parent_object_id = OBJECT_ID(N'dbo.CT_VIPHAM') AND name = N'FK_CT_VIPHAM_HOCKY')
    BEGIN
        ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT FK_CT_VIPHAM_HOCKY
            FOREIGN KEY (MA_HK) REFERENCES dbo.HOCKY (MA_HK)
            ON UPDATE CASCADE;
    END

    -- 10. Tạo Index IX_CT_VIPHAM_MA_HK hỗ trợ tra cứu và thống kê theo học kỳ
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_CT_VIPHAM_MA_HK' AND object_id = OBJECT_ID(N'dbo.CT_VIPHAM'))
    BEGIN
        CREATE INDEX IX_CT_VIPHAM_MA_HK ON dbo.CT_VIPHAM (MA_HK, NGAY_VIPHAM);
    END

    -- 11. Ghi nhận vào bảng nhật ký migration
    IF OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '004')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('004', N'Bỏ HIEN_TAI ở HOCKY, liên kết khóa ngoại MA_HK vào CT_VIPHAM');

    COMMIT TRANSACTION;
    PRINT N'Migration 004 đã áp dụng thành công.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
