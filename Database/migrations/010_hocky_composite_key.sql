/*
  Migration 010 - HOCKY dùng khóa chính kép (NAM_HOC, HOC_KY).
  - HOCKY: thêm NAM_HOC ('2025-2026') + HOC_KY (1..3) làm PK; bỏ ID, MA_HK, TEN_HK
    (tên hiển thị "Học kỳ N (YYYY-YYYY)" được ghép ở tầng ứng dụng).
  - CT_VIPHAM: thay MA_HK bằng cặp (NAM_HOC, HOC_KY), khóa ngoại kép tới HOCKY.
  Dữ liệu cũ được chuyển đổi: HOC_KY lấy từ "HK<n>" trong MA_HK (hoặc "kỳ <n>" trong TEN_HK),
  NAM_HOC suy ra từ NGAY_BAT_DAU (tháng >= 8 thuộc năm học bắt đầu năm đó).
  Script KHÔNG xóa dòng dữ liệu nào. Rollback: 010_hocky_composite_key.rollback.sql.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.HOCKY', N'U') IS NULL OR OBJECT_ID(N'dbo.CT_VIPHAM', N'U') IS NULL
    THROW 50010, N'Chưa có bảng HOCKY hoặc CT_VIPHAM. Hãy chạy migration 004 trước.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    DECLARE @sql NVARCHAR(MAX);

    -- 1. Thêm cột mới (tạm cho phép NULL để chuyển đổi dữ liệu)
    IF COL_LENGTH(N'dbo.HOCKY', N'NAM_HOC') IS NULL
        ALTER TABLE dbo.HOCKY ADD NAM_HOC CHAR(9) NULL;
    IF COL_LENGTH(N'dbo.HOCKY', N'HOC_KY') IS NULL
        ALTER TABLE dbo.HOCKY ADD HOC_KY TINYINT NULL;
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'NAM_HOC') IS NULL
        ALTER TABLE dbo.CT_VIPHAM ADD NAM_HOC CHAR(9) NULL;
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'HOC_KY') IS NULL
        ALTER TABLE dbo.CT_VIPHAM ADD HOC_KY TINYINT NULL;

    -- 2. Chuyển đổi dữ liệu HOCKY từ MA_HK/TEN_HK cũ
    IF COL_LENGTH(N'dbo.HOCKY', N'MA_HK') IS NOT NULL
    BEGIN
        EXEC(N'
            UPDATE dbo.HOCKY SET
                HOC_KY = COALESCE(
                    TRY_CONVERT(TINYINT, NULLIF(SUBSTRING(UPPER(MA_HK), NULLIF(PATINDEX(''%HK[1-3]%'', UPPER(MA_HK)), 0) + 2, 1), '''')),
                    TRY_CONVERT(TINYINT, NULLIF(SUBSTRING(TEN_HK, NULLIF(PATINDEX(N''%kỳ [1-3]%'', TEN_HK), 0) + 3, 1), ''''))),
                NAM_HOC = CONCAT(YEAR(NGAY_BAT_DAU) - IIF(MONTH(NGAY_BAT_DAU) >= 8, 0, 1), ''-'',
                                 YEAR(NGAY_BAT_DAU) - IIF(MONTH(NGAY_BAT_DAU) >= 8, 0, 1) + 1)
            WHERE HOC_KY IS NULL OR NAM_HOC IS NULL;

            DECLARE @msg NVARCHAR(2048);
            DECLARE @bad NVARCHAR(1000) = (SELECT STRING_AGG(MA_HK, '', '') FROM dbo.HOCKY WHERE HOC_KY IS NULL);
            IF @bad IS NOT NULL
            BEGIN
                SET @msg = N''Không xác định được số học kỳ (1-3) từ MA_HK/TEN_HK: '' + @bad + N''. Hãy sửa rồi chạy lại.'';
                THROW 50011, @msg, 1;
            END;

            DECLARE @dup NVARCHAR(1000) = (
                SELECT STRING_AGG(CONCAT(NAM_HOC, ''/HK'', HOC_KY), '', '')
                FROM (SELECT NAM_HOC, HOC_KY FROM dbo.HOCKY GROUP BY NAM_HOC, HOC_KY HAVING COUNT(*) > 1) d);
            IF @dup IS NOT NULL
            BEGIN
                SET @msg = N''Trùng (năm học, học kỳ) sau khi chuyển đổi: '' + @dup + N''. Hãy sửa ngày bắt đầu/mã học kỳ rồi chạy lại.'';
                THROW 50012, @msg, 1;
            END;

            UPDATE cv SET cv.NAM_HOC = hk.NAM_HOC, cv.HOC_KY = hk.HOC_KY
            FROM dbo.CT_VIPHAM cv INNER JOIN dbo.HOCKY hk ON hk.MA_HK = cv.MA_HK
            WHERE cv.MA_HK IS NOT NULL;
        ');
    END;

    -- 3. Bỏ mọi khóa ngoại trỏ tới HOCKY và mọi PK/UNIQUE cũ trên HOCKY
    SET @sql = N'';
    SELECT @sql += N'ALTER TABLE ' + QUOTENAME(OBJECT_SCHEMA_NAME(fk.parent_object_id)) + N'.'
                 + QUOTENAME(OBJECT_NAME(fk.parent_object_id)) + N' DROP CONSTRAINT ' + QUOTENAME(fk.name) + N';'
    FROM sys.foreign_keys fk
    WHERE fk.referenced_object_id = OBJECT_ID(N'dbo.HOCKY') AND fk.name <> N'FK_CT_VIPHAM_HOCKY_KEP';
    EXEC sys.sp_executesql @sql;

    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_CT_VIPHAM_MA_HK' AND object_id = OBJECT_ID(N'dbo.CT_VIPHAM'))
        DROP INDEX IX_CT_VIPHAM_MA_HK ON dbo.CT_VIPHAM;
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'MA_HK') IS NOT NULL
        ALTER TABLE dbo.CT_VIPHAM DROP COLUMN MA_HK;

    SET @sql = N'';
    SELECT @sql += N'ALTER TABLE dbo.HOCKY DROP CONSTRAINT ' + QUOTENAME(kc.name) + N';'
    FROM sys.key_constraints kc
    WHERE kc.parent_object_id = OBJECT_ID(N'dbo.HOCKY') AND kc.name <> N'PK_HOCKY';
    EXEC sys.sp_executesql @sql;

    -- 4. Bỏ cột cũ của HOCKY
    IF COL_LENGTH(N'dbo.HOCKY', N'ID') IS NOT NULL
        ALTER TABLE dbo.HOCKY DROP COLUMN ID;
    IF COL_LENGTH(N'dbo.HOCKY', N'MA_HK') IS NOT NULL
        ALTER TABLE dbo.HOCKY DROP COLUMN MA_HK;
    IF COL_LENGTH(N'dbo.HOCKY', N'TEN_HK') IS NOT NULL
        ALTER TABLE dbo.HOCKY DROP COLUMN TEN_HK;

    -- 5. Khóa chính kép + ràng buộc định dạng (EXEC: cột vừa thêm trong cùng batch nên phải biên dịch sau)
    --    NOT NULL ở batch riêng: PK trong cùng batch vẫn thấy cột là nullable (lỗi 8111).
    EXEC(N'
        ALTER TABLE dbo.HOCKY ALTER COLUMN NAM_HOC CHAR(9) NOT NULL;
        ALTER TABLE dbo.HOCKY ALTER COLUMN HOC_KY TINYINT NOT NULL;
    ');
    EXEC(N'
        IF OBJECT_ID(N''dbo.PK_HOCKY'', N''PK'') IS NULL
            ALTER TABLE dbo.HOCKY ADD CONSTRAINT PK_HOCKY PRIMARY KEY (NAM_HOC, HOC_KY);
        IF OBJECT_ID(N''dbo.CK_HOCKY_HOC_KY'', N''C'') IS NULL
            ALTER TABLE dbo.HOCKY ADD CONSTRAINT CK_HOCKY_HOC_KY CHECK (HOC_KY BETWEEN 1 AND 3);
        IF OBJECT_ID(N''dbo.CK_HOCKY_NAM_HOC'', N''C'') IS NULL
            ALTER TABLE dbo.HOCKY ADD CONSTRAINT CK_HOCKY_NAM_HOC CHECK (
                NAM_HOC LIKE ''[12][0-9][0-9][0-9]-[12][0-9][0-9][0-9]''
                AND TRY_CONVERT(INT, RIGHT(NAM_HOC, 4)) = TRY_CONVERT(INT, LEFT(NAM_HOC, 4)) + 1);
        IF OBJECT_ID(N''dbo.CK_HOCKY_KHOANG_NGAY'', N''C'') IS NULL
            ALTER TABLE dbo.HOCKY ADD CONSTRAINT CK_HOCKY_KHOANG_NGAY CHECK (NGAY_KET_THUC >= NGAY_BAT_DAU);

        -- 6. CT_VIPHAM: khóa ngoại kép, hai cột cùng NULL hoặc cùng có giá trị
        IF OBJECT_ID(N''dbo.CK_CT_VIPHAM_HOCKY_DU_CAP'', N''C'') IS NULL
            ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT CK_CT_VIPHAM_HOCKY_DU_CAP
                CHECK ((NAM_HOC IS NULL AND HOC_KY IS NULL) OR (NAM_HOC IS NOT NULL AND HOC_KY IS NOT NULL));
        IF OBJECT_ID(N''dbo.FK_CT_VIPHAM_HOCKY_KEP'', N''F'') IS NULL
            ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT FK_CT_VIPHAM_HOCKY_KEP
                FOREIGN KEY (NAM_HOC, HOC_KY) REFERENCES dbo.HOCKY (NAM_HOC, HOC_KY) ON UPDATE CASCADE;
        IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N''IX_CT_VIPHAM_HOCKY'' AND object_id = OBJECT_ID(N''dbo.CT_VIPHAM''))
            CREATE INDEX IX_CT_VIPHAM_HOCKY ON dbo.CT_VIPHAM (NAM_HOC, HOC_KY, NGAY_VIPHAM);
    ');

    IF NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '010')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('010', N'HOCKY dùng khóa chính kép (NAM_HOC, HOC_KY); CT_VIPHAM tham chiếu theo cặp khóa');

    COMMIT TRANSACTION;
    PRINT N'Migration 010 đã áp dụng thành công.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
