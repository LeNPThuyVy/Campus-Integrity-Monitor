/*
  Rollback cho migration 001.

  CẢNH BÁO: rollback XÓA các bảng mới (BAN_GHI_VIPHAM, NHAT_KY_XOA_ANH, CAMERA, KHU_VUC,
  HOC_KY, TAIKHOAN) cùng dữ liệu trong đó. Chỉ chạy khi chắc chắn muốn quay về schema gốc
  và đã sao lưu database. File ảnh bằng chứng trên đĩa không bị script này động tới.

  Bảo vệ dữ liệu gốc:
  - Bảng gốc (KHOA, NGANH, LOP, SINHVIEN, LOI_VIPHAM, CT_VIPHAM) không bị xóa dòng nào.
  - Khôi phục khóa chính ghép (ID_LOI, ID_SV) cho CT_VIPHAM chỉ khi KHÔNG có cặp trùng.
    Nếu đã phát sinh nhiều lần vi phạm cùng loại của một sinh viên, script dừng lại và
    không thay đổi gì (cần xử lý dữ liệu trùng thủ công trước).
  - Loại lỗi do migration tự bổ sung vẫn được giữ lại (chỉ bỏ cột MA_LOI/DANG_SU_DUNG).
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF COL_LENGTH(N'dbo.CT_VIPHAM', N'ID') IS NOT NULL
   AND EXISTS (SELECT ID_LOI, ID_SV FROM dbo.CT_VIPHAM GROUP BY ID_LOI, ID_SV HAVING COUNT(*) > 1)
    THROW 50002, N'CT_VIPHAM có nhiều dòng trùng (ID_LOI, ID_SV); không thể khôi phục khóa chính ghép. Rollback đã dừng.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.NHAT_KY_XOA_ANH', N'U') IS NOT NULL DROP TABLE dbo.NHAT_KY_XOA_ANH;
    IF OBJECT_ID(N'dbo.BAN_GHI_VIPHAM', N'U') IS NOT NULL DROP TABLE dbo.BAN_GHI_VIPHAM;
    IF OBJECT_ID(N'dbo.CAMERA', N'U') IS NOT NULL DROP TABLE dbo.CAMERA;
    IF OBJECT_ID(N'dbo.KHU_VUC', N'U') IS NOT NULL DROP TABLE dbo.KHU_VUC;
    IF OBJECT_ID(N'dbo.HOC_KY', N'U') IS NOT NULL DROP TABLE dbo.HOC_KY;
    IF OBJECT_ID(N'dbo.TAIKHOAN', N'U') IS NOT NULL DROP TABLE dbo.TAIKHOAN;

    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'ID') IS NOT NULL
    BEGIN
        IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_CT_VIPHAM_SV_LOI' AND object_id = OBJECT_ID(N'dbo.CT_VIPHAM'))
            DROP INDEX IX_CT_VIPHAM_SV_LOI ON dbo.CT_VIPHAM;
        IF EXISTS (SELECT 1 FROM sys.key_constraints WHERE name = N'PK_CT_VIPHAM')
            ALTER TABLE dbo.CT_VIPHAM DROP CONSTRAINT PK_CT_VIPHAM;
        ALTER TABLE dbo.CT_VIPHAM DROP COLUMN ID;
        ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT PK_CT_VIPHAM_GOC PRIMARY KEY (ID_LOI, ID_SV);
    END

    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'GHI_CHU') IS NOT NULL
        ALTER TABLE dbo.CT_VIPHAM DROP COLUMN GHI_CHU;
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'NGAY_TAO') IS NOT NULL
    BEGIN
        IF OBJECT_ID(N'dbo.DF_CT_VIPHAM_NGAY_TAO', N'D') IS NOT NULL
            ALTER TABLE dbo.CT_VIPHAM DROP CONSTRAINT DF_CT_VIPHAM_NGAY_TAO;
        ALTER TABLE dbo.CT_VIPHAM DROP COLUMN NGAY_TAO;
    END

    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'UX_LOI_VIPHAM_MA_LOI' AND object_id = OBJECT_ID(N'dbo.LOI_VIPHAM'))
        DROP INDEX UX_LOI_VIPHAM_MA_LOI ON dbo.LOI_VIPHAM;
    IF COL_LENGTH(N'dbo.LOI_VIPHAM', N'MA_LOI') IS NOT NULL
        ALTER TABLE dbo.LOI_VIPHAM DROP COLUMN MA_LOI;
    IF COL_LENGTH(N'dbo.LOI_VIPHAM', N'DANG_SU_DUNG') IS NOT NULL
    BEGIN
        IF OBJECT_ID(N'dbo.DF_LOI_VIPHAM_DANG_SU_DUNG', N'D') IS NOT NULL
            ALTER TABLE dbo.LOI_VIPHAM DROP CONSTRAINT DF_LOI_VIPHAM_DANG_SU_DUNG;
        ALTER TABLE dbo.LOI_VIPHAM DROP COLUMN DANG_SU_DUNG;
    END

    IF OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U') IS NOT NULL
        DELETE FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '001';

    COMMIT TRANSACTION;
    PRINT N'Đã rollback migration 001.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
