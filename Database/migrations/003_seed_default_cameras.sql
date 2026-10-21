/*
  Migration 003 - Khởi tạo dữ liệu camera mẫu cho các khu vực.
  Chạy được nhiều lần (idempotent), không tạo trùng lặp.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;

BEGIN TRY
    BEGIN TRANSACTION;

    IF NOT EXISTS (SELECT 1 FROM dbo.CAMERA WHERE MA_CAMERA = 'CAM-01')
        INSERT INTO dbo.CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, KHOA_THIET_BI_HASH, DANG_SU_DUNG)
        VALUES ('CAM-01', N'Camera Cổng 1', 1, N'Cổng chính vào trường', 'd9946250357eb8f32e45b1d8c6ac407abf688ee78362a0145262b168f4a2c10f', 1);

    IF NOT EXISTS (SELECT 1 FROM dbo.CAMERA WHERE MA_CAMERA = 'CAM-02')
        INSERT INTO dbo.CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, KHOA_THIET_BI_HASH, DANG_SU_DUNG)
        VALUES ('CAM-02', N'Camera Giảng đường A', 2, N'Hành lang tầng 1 Giảng đường A', '71a9b643b00c93024ad342b4a4ab59872d5a8b9072bcf076ce8995bfc420cee5', 1);

    IF NOT EXISTS (SELECT 1 FROM dbo.CAMERA WHERE MA_CAMERA = 'CAM-03')
        INSERT INTO dbo.CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, KHOA_THIET_BI_HASH, DANG_SU_DUNG)
        VALUES ('CAM-03', N'Camera Giảng đường B', 3, N'Hành lang tầng 1 Giảng đường B', 'a580aef9b6ec2de96663e1836e8b18eda5d810a33ddd908c7e3d809b733d4901', 1);

    IF NOT EXISTS (SELECT 1 FROM dbo.CAMERA WHERE MA_CAMERA = 'CAM-04')
        INSERT INTO dbo.CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, KHOA_THIET_BI_HASH, DANG_SU_DUNG)
        VALUES ('CAM-04', N'Camera Sân trường', 4, N'Khu vực cột cờ trung tâm', 'd6bb7b36df6c9c822082e5d9562384af08e66e9b10147a25fb2b40b7fed5ef57', 1);

    IF OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U') IS NOT NULL
       AND NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '003_seed_default_cameras')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('003_seed_default_cameras', N'Khởi tạo camera mặc định cho các khu vực');

    COMMIT TRANSACTION;
    PRINT N'Migration 003 áp dụng thành công: Đã khởi tạo các camera mặc định.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
    DECLARE @ErrorState INT = ERROR_STATE();
    RAISERROR (@ErrorMessage, @ErrorSeverity, @ErrorState);
END CATCH;
