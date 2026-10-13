/*
  Migration 001 - Bản ghi vi phạm AI, tài khoản Web, khu vực/camera, học kỳ.

  Điều kiện: database đã được tạo bằng SQL_Querysql.sql (KHOA, NGANH, LOP, SINHVIEN,
  LOI_VIPHAM, CT_VIPHAM). Script KHÔNG xóa bảng, KHÔNG xóa dữ liệu.

  - Chạy được nhiều lần (idempotent): mọi thay đổi đều có kiểm tra tồn tại.
  - Toàn bộ chạy trong MỘT transaction: lỗi ở bất kỳ bước nào sẽ rollback toàn bộ.
  - Các câu lệnh tham chiếu cột mới của bảng cũ dùng dynamic SQL để tránh lỗi biên dịch batch.

  Cách chạy:
    sqlcmd -S <server> -d <database> -E -C -I -f 65001 -i Database\migrations\001_violation_records_accounts_cameras.sql
    (-I = QUOTED_IDENTIFIER ON, bắt buộc cho filtered index, kể cả khi chạy DML thủ công bằng sqlcmd)
  hoặc: python -m db.migrate (trong thư mục Final_System), xem README.

  Rollback: Database\migrations\001_violation_records_accounts_cameras.rollback.sql
  Khuyến nghị sao lưu database trước khi chạy trên môi trường thật.
*/
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID(N'dbo.KHOA', N'U') IS NULL OR OBJECT_ID(N'dbo.SINHVIEN', N'U') IS NULL
   OR OBJECT_ID(N'dbo.LOI_VIPHAM', N'U') IS NULL OR OBJECT_ID(N'dbo.CT_VIPHAM', N'U') IS NULL
    THROW 50001, N'Chưa có schema gốc. Hãy chạy SQL_Querysql.sql trước khi chạy migration 001.', 1;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.SCHEMA_MIGRATIONS', N'U') IS NULL
        CREATE TABLE dbo.SCHEMA_MIGRATIONS
        (
            PHIEN_BAN   VARCHAR(50)  NOT NULL CONSTRAINT PK_SCHEMA_MIGRATIONS PRIMARY KEY,
            MO_TA       NVARCHAR(200) NOT NULL,
            AP_DUNG_LUC DATETIME2(0) NOT NULL CONSTRAINT DF_SCHEMA_MIGRATIONS_AP_DUNG_LUC DEFAULT SYSDATETIME()
        );

    /* ---------------------------------------------------------------
       1. LOI_VIPHAM: mã máy đọc được cho AI + trạng thái sử dụng
       --------------------------------------------------------------- */
    IF COL_LENGTH(N'dbo.LOI_VIPHAM', N'MA_LOI') IS NULL
        ALTER TABLE dbo.LOI_VIPHAM ADD MA_LOI VARCHAR(20) NULL;

    IF COL_LENGTH(N'dbo.LOI_VIPHAM', N'DANG_SU_DUNG') IS NULL
        ALTER TABLE dbo.LOI_VIPHAM ADD DANG_SU_DUNG BIT NOT NULL
            CONSTRAINT DF_LOI_VIPHAM_DANG_SU_DUNG DEFAULT (1);

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'UX_LOI_VIPHAM_MA_LOI'
                   AND object_id = OBJECT_ID(N'dbo.LOI_VIPHAM'))
        EXEC (N'CREATE UNIQUE INDEX UX_LOI_VIPHAM_MA_LOI ON dbo.LOI_VIPHAM (MA_LOI) WHERE MA_LOI IS NOT NULL;');

    -- Gắn mã cho 3 loại lỗi có sẵn trong dữ liệu mẫu (khớp đúng tên), không đổi ID.
    EXEC (N'
        UPDATE TOP (1) dbo.LOI_VIPHAM SET MA_LOI = ''UNIFORM''
        WHERE MA_LOI IS NULL AND TEN_LOI = N''Vi phạm sai đồng phục''
          AND NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''UNIFORM'');
        UPDATE TOP (1) dbo.LOI_VIPHAM SET MA_LOI = ''NO_CARD''
        WHERE MA_LOI IS NULL AND TEN_LOI = N''Thiếu thẻ sinh viên''
          AND NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''NO_CARD'');
        UPDATE TOP (1) dbo.LOI_VIPHAM SET MA_LOI = ''BOTH''
        WHERE MA_LOI IS NULL AND TEN_LOI = N''Vi phạm đồng thời sai đồng phục và thiếu thẻ sinh viên''
          AND NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''BOTH'');

        -- Database chưa có dữ liệu mẫu: bổ sung loại lỗi còn thiếu (không ghi đè dòng có sẵn).
        IF NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''UNIFORM'')
            INSERT INTO dbo.LOI_VIPHAM (TEN_LOI, DIEM_TRU, MA_LOI) VALUES (N''Vi phạm sai đồng phục'', 5, ''UNIFORM'');
        IF NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''NO_CARD'')
            INSERT INTO dbo.LOI_VIPHAM (TEN_LOI, DIEM_TRU, MA_LOI) VALUES (N''Thiếu thẻ sinh viên'', 5, ''NO_CARD'');
        IF NOT EXISTS (SELECT 1 FROM dbo.LOI_VIPHAM WHERE MA_LOI = ''BOTH'')
            INSERT INTO dbo.LOI_VIPHAM (TEN_LOI, DIEM_TRU, MA_LOI)
            VALUES (N''Vi phạm đồng thời sai đồng phục và thiếu thẻ sinh viên'', 10, ''BOTH'');
    ');

    /* ---------------------------------------------------------------
       2. CT_VIPHAM: mỗi lần vi phạm có ID riêng (bỏ PK ghép ID_LOI, ID_SV)
          Dữ liệu cũ được giữ nguyên, mỗi dòng được cấp ID tự tăng.
       --------------------------------------------------------------- */
    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'ID') IS NULL
    BEGIN
        DECLARE @oldPk SYSNAME = (
            SELECT name FROM sys.key_constraints
            WHERE parent_object_id = OBJECT_ID(N'dbo.CT_VIPHAM') AND type = 'PK');

        ALTER TABLE dbo.CT_VIPHAM ADD ID INT IDENTITY(1, 1) NOT NULL;

        IF @oldPk IS NOT NULL
        BEGIN
            DECLARE @dropPk NVARCHAR(400) = N'ALTER TABLE dbo.CT_VIPHAM DROP CONSTRAINT ' + QUOTENAME(@oldPk) + N';';
            EXEC sys.sp_executesql @dropPk;
        END

        EXEC (N'ALTER TABLE dbo.CT_VIPHAM ADD CONSTRAINT PK_CT_VIPHAM PRIMARY KEY CLUSTERED (ID);');
    END

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_CT_VIPHAM_SV_LOI'
                   AND object_id = OBJECT_ID(N'dbo.CT_VIPHAM'))
        CREATE INDEX IX_CT_VIPHAM_SV_LOI ON dbo.CT_VIPHAM (ID_SV, ID_LOI, NGAY_VIPHAM);

    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'GHI_CHU') IS NULL
        ALTER TABLE dbo.CT_VIPHAM ADD GHI_CHU NVARCHAR(500) NULL;

    IF COL_LENGTH(N'dbo.CT_VIPHAM', N'NGAY_TAO') IS NULL
        ALTER TABLE dbo.CT_VIPHAM ADD NGAY_TAO DATETIME2(0) NOT NULL
            CONSTRAINT DF_CT_VIPHAM_NGAY_TAO DEFAULT SYSDATETIME();

    /* ---------------------------------------------------------------
       3. TAIKHOAN: tài khoản Web (Quản trị viên / Nhân viên CTSV)
          Không tạo tài khoản mặc định. Dùng: python -m api.cli create-admin
       --------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.TAIKHOAN', N'U') IS NULL
        CREATE TABLE dbo.TAIKHOAN
        (
            ID               INT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_TAIKHOAN PRIMARY KEY,
            TEN_DANG_NHAP    VARCHAR(50)   NOT NULL CONSTRAINT UQ_TAIKHOAN_TEN_DANG_NHAP UNIQUE,
            HO_TEN           NVARCHAR(100) NOT NULL,
            MAT_KHAU_HASH    VARCHAR(255)  NOT NULL,
            VAI_TRO          VARCHAR(10)   NOT NULL
                CONSTRAINT CK_TAIKHOAN_VAI_TRO CHECK (VAI_TRO IN ('ADMIN', 'CTSV')),
            DANG_HOAT_DONG   BIT           NOT NULL CONSTRAINT DF_TAIKHOAN_DANG_HOAT_DONG DEFAULT (1),
            PHIEN_BAN_TOKEN  INT           NOT NULL CONSTRAINT DF_TAIKHOAN_PHIEN_BAN_TOKEN DEFAULT (1),
            SO_LAN_SAI       INT           NOT NULL CONSTRAINT DF_TAIKHOAN_SO_LAN_SAI DEFAULT (0),
            KHOA_DEN         DATETIME2(0)  NULL,
            DANG_NHAP_CUOI   DATETIME2(0)  NULL,
            DOI_MAT_KHAU_LUC DATETIME2(0)  NULL,
            NGAY_TAO         DATETIME2(0)  NOT NULL CONSTRAINT DF_TAIKHOAN_NGAY_TAO DEFAULT SYSDATETIME(),
            NGAY_CAP_NHAT    DATETIME2(0)  NOT NULL CONSTRAINT DF_TAIKHOAN_NGAY_CAP_NHAT DEFAULT SYSDATETIME()
        );

    /* ---------------------------------------------------------------
       4. KHU_VUC, CAMERA
       --------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.KHU_VUC', N'U') IS NULL
        CREATE TABLE dbo.KHU_VUC
        (
            ID            INT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_KHU_VUC PRIMARY KEY,
            MA_KHU_VUC    VARCHAR(20)   NOT NULL CONSTRAINT UQ_KHU_VUC_MA UNIQUE,
            TEN_KHU_VUC   NVARCHAR(100) NOT NULL,
            MO_TA         NVARCHAR(255) NULL,
            DANG_SU_DUNG  BIT           NOT NULL CONSTRAINT DF_KHU_VUC_DANG_SU_DUNG DEFAULT (1),
            NGAY_TAO      DATETIME2(0)  NOT NULL CONSTRAINT DF_KHU_VUC_NGAY_TAO DEFAULT SYSDATETIME(),
            NGAY_CAP_NHAT DATETIME2(0)  NOT NULL CONSTRAINT DF_KHU_VUC_NGAY_CAP_NHAT DEFAULT SYSDATETIME()
        );

    IF OBJECT_ID(N'dbo.CAMERA', N'U') IS NULL
        CREATE TABLE dbo.CAMERA
        (
            ID                  INT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_CAMERA PRIMARY KEY,
            MA_CAMERA           VARCHAR(30)   NOT NULL CONSTRAINT UQ_CAMERA_MA UNIQUE,
            TEN_CAMERA          NVARCHAR(100) NOT NULL,
            KHU_VUC_ID          INT           NOT NULL
                CONSTRAINT FK_CAMERA_KHU_VUC REFERENCES dbo.KHU_VUC (ID),
            VI_TRI              NVARCHAR(255) NULL,
            NGUON_VIDEO         NVARCHAR(500) NULL,   -- thông tin nhạy cảm: chỉ ADMIN được xem
            KHOA_THIET_BI_HASH  CHAR(64)      NULL,   -- SHA-256 của khóa thiết bị, không lưu khóa gốc
            DANG_SU_DUNG        BIT           NOT NULL CONSTRAINT DF_CAMERA_DANG_SU_DUNG DEFAULT (1),
            LAN_CUOI_KET_NOI    DATETIME2(0)  NULL,
            NGAY_TAO            DATETIME2(0)  NOT NULL CONSTRAINT DF_CAMERA_NGAY_TAO DEFAULT SYSDATETIME(),
            NGAY_CAP_NHAT       DATETIME2(0)  NOT NULL CONSTRAINT DF_CAMERA_NGAY_CAP_NHAT DEFAULT SYSDATETIME()
        );

    /* ---------------------------------------------------------------
       5. HOC_KY: khoảng thời gian học kỳ phục vụ thống kê
       --------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.HOC_KY', N'U') IS NULL
        CREATE TABLE dbo.HOC_KY
        (
            ID            INT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_HOC_KY PRIMARY KEY,
            MA_HOC_KY     VARCHAR(20)   NOT NULL CONSTRAINT UQ_HOC_KY_MA UNIQUE,
            TEN_HOC_KY    NVARCHAR(100) NOT NULL,
            NGAY_BAT_DAU  DATE          NOT NULL,
            NGAY_KET_THUC DATE          NOT NULL,
            CONSTRAINT CK_HOC_KY_KHOANG_NGAY CHECK (NGAY_KET_THUC >= NGAY_BAT_DAU)
        );

    /* ---------------------------------------------------------------
       6. BAN_GHI_VIPHAM: bản ghi do Desktop/AI gửi lên.
          Cố ý KHÔNG có cột sinh viên (MSSV/họ tên): AI không nhận diện danh tính.
          Khi ảnh hết hạn chỉ xóa file và cập nhật trạng thái ảnh; bản ghi thống kê được giữ.
       --------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.BAN_GHI_VIPHAM', N'U') IS NULL
        CREATE TABLE dbo.BAN_GHI_VIPHAM
        (
            ID                   BIGINT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_BAN_GHI_VIPHAM PRIMARY KEY,
            MA_SU_KIEN           UNIQUEIDENTIFIER NOT NULL CONSTRAINT UQ_BAN_GHI_VIPHAM_MA_SU_KIEN UNIQUE,
            LOI_ID               INT           NOT NULL
                CONSTRAINT FK_BAN_GHI_VIPHAM_LOI REFERENCES dbo.LOI_VIPHAM (ID),
            CAMERA_ID            INT           NOT NULL
                CONSTRAINT FK_BAN_GHI_VIPHAM_CAMERA REFERENCES dbo.CAMERA (ID),
            KHU_VUC_ID           INT           NOT NULL
                CONSTRAINT FK_BAN_GHI_VIPHAM_KHU_VUC REFERENCES dbo.KHU_VUC (ID),
            THOI_DIEM            DATETIME2(0)  NOT NULL,
            TRACK_ID             INT           NULL,
            DO_TIN_CAY_DONG_PHUC DECIMAL(5, 4) NULL,
            DO_TIN_CAY_THE       DECIMAL(5, 4) NULL,
            ANH_KHOA             NVARCHAR(260) NULL,
            ANH_SHA256           CHAR(64)      NULL,
            ANH_KICH_THUOC       INT           NULL,
            ANH_TRANG_THAI       VARCHAR(12)   NOT NULL CONSTRAINT DF_BAN_GHI_VIPHAM_ANH_TRANG_THAI DEFAULT ('NONE')
                CONSTRAINT CK_BAN_GHI_VIPHAM_ANH_TRANG_THAI CHECK (ANH_TRANG_THAI IN ('AVAILABLE', 'DELETED', 'MISSING', 'NONE')),
            ANH_HET_HAN_LUC      DATETIME2(0)  NULL,
            ANH_DA_XOA_LUC       DATETIME2(0)  NULL,
            TRANG_THAI_XU_LY     VARCHAR(12)   NOT NULL CONSTRAINT DF_BAN_GHI_VIPHAM_TRANG_THAI_XU_LY DEFAULT ('MOI')
                CONSTRAINT CK_BAN_GHI_VIPHAM_TRANG_THAI_XU_LY CHECK (TRANG_THAI_XU_LY IN ('MOI', 'DA_XEM', 'DA_XU_LY', 'BO_QUA')),
            GHI_CHU_XU_LY        NVARCHAR(500) NULL,
            NGUOI_XU_LY_ID       INT           NULL
                CONSTRAINT FK_BAN_GHI_VIPHAM_NGUOI_XU_LY REFERENCES dbo.TAIKHOAN (ID),
            XU_LY_LUC            DATETIME2(0)  NULL,
            NGAY_TAO             DATETIME2(0)  NOT NULL CONSTRAINT DF_BAN_GHI_VIPHAM_NGAY_TAO DEFAULT SYSDATETIME(),
            CONSTRAINT CK_BAN_GHI_VIPHAM_DO_TIN_CAY CHECK (
                (DO_TIN_CAY_DONG_PHUC IS NULL OR DO_TIN_CAY_DONG_PHUC BETWEEN 0 AND 1) AND
                (DO_TIN_CAY_THE IS NULL OR DO_TIN_CAY_THE BETWEEN 0 AND 1))
        );

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_BAN_GHI_VIPHAM_THOI_DIEM'
                   AND object_id = OBJECT_ID(N'dbo.BAN_GHI_VIPHAM'))
        CREATE INDEX IX_BAN_GHI_VIPHAM_THOI_DIEM ON dbo.BAN_GHI_VIPHAM (THOI_DIEM)
            INCLUDE (LOI_ID, KHU_VUC_ID, CAMERA_ID);

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_BAN_GHI_VIPHAM_KHU_VUC_THOI_DIEM'
                   AND object_id = OBJECT_ID(N'dbo.BAN_GHI_VIPHAM'))
        CREATE INDEX IX_BAN_GHI_VIPHAM_KHU_VUC_THOI_DIEM ON dbo.BAN_GHI_VIPHAM (KHU_VUC_ID, THOI_DIEM);

    IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_BAN_GHI_VIPHAM_ANH_HET_HAN'
                   AND object_id = OBJECT_ID(N'dbo.BAN_GHI_VIPHAM'))
        CREATE INDEX IX_BAN_GHI_VIPHAM_ANH_HET_HAN ON dbo.BAN_GHI_VIPHAM (ANH_HET_HAN_LUC)
            WHERE ANH_TRANG_THAI = 'AVAILABLE';

    /* ---------------------------------------------------------------
       7. NHAT_KY_XOA_ANH: nhật ký của job xóa ảnh hết hạn
       --------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.NHAT_KY_XOA_ANH', N'U') IS NULL
        CREATE TABLE dbo.NHAT_KY_XOA_ANH
        (
            ID          BIGINT IDENTITY(1, 1) NOT NULL CONSTRAINT PK_NHAT_KY_XOA_ANH PRIMARY KEY,
            BAN_GHI_ID  BIGINT        NOT NULL
                CONSTRAINT FK_NHAT_KY_XOA_ANH_BAN_GHI REFERENCES dbo.BAN_GHI_VIPHAM (ID),
            KET_QUA     VARCHAR(20)   NOT NULL
                CONSTRAINT CK_NHAT_KY_XOA_ANH_KET_QUA CHECK (KET_QUA IN ('DELETED', 'ALREADY_MISSING', 'UNSAFE_PATH', 'ERROR')),
            CHI_TIET    NVARCHAR(500) NULL,
            THOI_DIEM   DATETIME2(0)  NOT NULL CONSTRAINT DF_NHAT_KY_XOA_ANH_THOI_DIEM DEFAULT SYSDATETIME()
        );

    IF NOT EXISTS (SELECT 1 FROM dbo.SCHEMA_MIGRATIONS WHERE PHIEN_BAN = '001')
        INSERT INTO dbo.SCHEMA_MIGRATIONS (PHIEN_BAN, MO_TA)
        VALUES ('001', N'Bản ghi vi phạm AI, tài khoản, khu vực/camera, học kỳ, ID riêng cho CT_VIPHAM');

    COMMIT TRANSACTION;
    PRINT N'Migration 001 đã áp dụng thành công.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
