/*
  Migration 002 - Bổ sung thông tin thời điểm (giờ, phút) và địa điểm/phòng học vào bảng CT_VIPHAM.
  Đồng thời tối ưu hóa cho quy trình: Camera cảnh báo -> CTSV điều Cờ Đỏ -> Lập biên bản chi tiết.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.CT_VIPHAM', N'U') IS NULL
    THROW 50002, N'Chưa có bảng CT_VIPHAM. Hãy chạy migration 001 trước.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    -- 1. Thêm cột THOI_DIEM (Lưu cả ngày lẫn giờ phút giây vi phạm)
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'THOI_DIEM') IS NULL
    BEGIN
        ALTER TABLE dbo.CT_VIPHAM ADD THOI_DIEM DATETIME2(0) NULL;
        
        -- Đồng bộ dữ liệu cũ: nếu đã có NGAY_VIPHAM thì gán mặc định thời điểm 07:30:00 của ngày đó
        EXEC(N'UPDATE dbo.CT_VIPHAM SET THOI_DIEM = DATEADD(MINUTE, 450, CAST(NGAY_VIPHAM AS DATETIME2(0))) WHERE THOI_DIEM IS NULL;');
        
        -- Sau khi điền dữ liệu thì áp đặt NOT NULL và DEFAULT
        EXEC(N'ALTER TABLE dbo.CT_VIPHAM ALTER COLUMN THOI_DIEM DATETIME2(0) NOT NULL;');
        EXEC(N'ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT DF_CT_VIPHAM_THOI_DIEM DEFAULT SYSDATETIME() FOR THOI_DIEM;');
    END

    -- 2. Thêm cột KHU_VUC_ID tham chiếu bảng KHU_VUC
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'KHU_VUC_ID') IS NULL
    BEGIN
        ALTER TABLE dbo.CT_VIPHAM ADD KHU_VUC_ID INT NULL
            CONSTRAINT FK_CT_VIPHAM_KHU_VUC REFERENCES dbo.KHU_VUC(ID);
    END

    -- 3. Thêm cột PHONG_HOC (VD: "Phòng A201", "Cổng chính", "Giảng đường B")
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'PHONG_HOC') IS NULL
    BEGIN
        ALTER TABLE dbo.CT_VIPHAM ADD PHONG_HOC NVARCHAR(100) NULL;
    END

    -- 4. Đánh chỉ mục hỗ trợ thống kê theo thời gian và khu vực
    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_CT_VIPHAM_THOI_DIEM'
                   AND object_id = OBJECT_ID(N'dbo.CT_VIPHAM'))
        EXEC(N'CREATE INDEX IX_CT_VIPHAM_THOI_DIEM ON dbo.CT_VIPHAM (THOI_DIEM) INCLUDE (ID_LOI, KHU_VUC_ID);');

    -- 5. Khởi tạo khu vực mặc định nếu bảng KHU_VUC đang trống
    IF OBJECT_ID(N'dbo.KHU_VUC', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.KHU_VUC)
    BEGIN
        INSERT INTO dbo.KHU_VUC (MA_KHU_VUC, TEN_KHU_VUC, MO_TA, DANG_SU_DUNG) VALUES
            ('KV-CONGCHINH', N'Cổng chính', N'Khu vực cổng chính ra vào trường', 1),
            ('KV-GIANGDUONG-A', N'Giảng đường A', N'Khu vực các phòng học tòa A', 1),
            ('KV-GIANGDUONG-B', N'Giảng đường B', N'Khu vực các phòng học tòa B', 1),
            ('KV-SANTRUONG', N'Sân trường', N'Khu vực sân trung tâm và hành lang', 1);
    END

    IF OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U') IS NOT NULL AND NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '002')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('002', N'Bổ sung THOI_DIEM, KHU_VUC_ID, PHONG_HOC cho CT_VIPHAM phục vụ điều phối Cờ Đỏ & thống kê AI');

    COMMIT TRANSACTION;
    PRINT N'Migration 002 đã áp dụng thành công.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
