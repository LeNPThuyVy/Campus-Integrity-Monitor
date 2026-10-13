# Hướng dẫn chạy toàn bộ hệ thống trên Windows

Thứ tự: **SQL Server → `.env` → database → API → tài khoản admin → Web → khai báo khu vực/camera/học kỳ → Desktop → kiểm thử luồng dữ liệu.**
Mọi lệnh chạy trong PowerShell tại thư mục gốc repository (ví dụ `D:\aa\Campus-Integrity-Monitor`).

> Nếu PowerShell chặn script: dùng `powershell -ExecutionPolicy Bypass -File scripts\<tên>.ps1` (chỉ áp dụng cho lệnh đó).

## 0. Phần mềm cần có

| Thành phần | Kiểm tra | Ghi chú |
|---|---|---|
| SQL Server 2017+ (Express được) | `Get-Service MSSQL*` | Dịch vụ phải ở trạng thái `Running` |
| ODBC Driver 18 (hoặc 17) for SQL Server | `Get-OdbcDriver -Platform 64-bit \| ? Name -like '*SQL Server*'` | Dùng cho API Python |
| Python 3.10+ (64-bit) | `python --version` | |
| PHP 8.1+ x64 + `pdo_sqlsrv` | `scripts\setup-php-windows.ps1` cài sẵn vào `tools\php` | Cần VC++ Redistributable 2015-2022 x64 |
| sqlcmd (tùy chọn) | `sqlcmd -?` | Có sẵn khi cài SQL Server/ODBC tools |

Cài thư viện Python (một lần):

```powershell
cd Final_System
python -m pip install -r requirements.txt
cd ..
```

Cài PHP + driver SQL Server cho Web (một lần, không sửa hệ thống, không sửa PATH):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup-php-windows.ps1
```

Đang dùng XAMPP/IIS sẵn có? Chép `php_pdo_sqlsrv_83_ts_x64.dll` (Apache, thread-safe) hoặc `_nts_` (IIS/FastCGI)
từ [Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server)
vào thư mục `ext`, thêm `extension=...` vào `php.ini`, bật `mbstring`, `fileinfo`, `zip`, khởi động lại web server.

## 1. Khởi chạy và chuẩn bị SQL Server

```powershell
Get-Service MSSQL*                       # tên dịch vụ, ví dụ MSSQL$SQLEXPRESS
Start-Service 'MSSQL$SQLEXPRESS'         # nếu đang Stopped (PowerShell chạy quyền Administrator)
```

Tạo database **mới, riêng cho hệ thống** (bỏ qua nếu đã có database chứa dữ liệu của hệ thống):

```powershell
sqlcmd -S .\SQLEXPRESS -E -C -Q "CREATE DATABASE CampusIntegrityMonitor"
```

Chọn cách xác thực:

- **Windows Authentication**: tài khoản Windows chạy API và Web phải có quyền trên database. Với IIS, đó là identity của
  Application Pool; cấp quyền bằng SSMS (Security → Logins) hoặc:
  `CREATE LOGIN [DOMAIN\User] FROM WINDOWS; USE CampusIntegrityMonitor; CREATE USER [DOMAIN\User] FOR LOGIN [DOMAIN\User]; ALTER ROLE db_owner ADD MEMBER [DOMAIN\User];`
- **SQL Server Authentication**: bật *SQL Server and Windows Authentication mode* trong SSMS, tạo login riêng cho ứng dụng
  (không dùng `sa`), cấp `db_owner` trên database của hệ thống (hoặc `db_datareader` + `db_datawriter` + quyền DDL khi chạy migration).

## 2. Cấu hình `.env`

```powershell
copy .env.example .env
notepad .env
```

Ví dụ SQL Server Express cục bộ, Windows Authentication:

```ini
DB_SERVER=localhost\SQLEXPRESS
DB_NAME=CampusIntegrityMonitor
DB_DRIVER=ODBC Driver 18 for SQL Server
DB_TRUSTED_CONNECTION=yes
DB_USER=
DB_PASSWORD=
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=yes      # SQL Server cục bộ dùng chứng chỉ tự ký
CIM_AUTH_SECRET=<dán kết quả lệnh tạo secret bên dưới>
CIM_API_BASE_URL=http://127.0.0.1:8000
```

Tạo secret: `python -c "import secrets; print(secrets.token_urlsafe(48))"`.
SQL Authentication: `DB_TRUSTED_CONNECTION=no`, điền `DB_USER`, `DB_PASSWORD`.
Không commit `.env` (đã có trong `.gitignore`).

Kiểm tra cấu hình (không in mật khẩu/secret):

```powershell
cd Final_System; python -m api.cli check-config; cd ..
```

## 3. Khởi tạo hoặc nâng cấp database

```powershell
cd Final_System
python -m api.migrate status
```

| Trạng thái | Lệnh |
|---|---|
| Database **trống** (vừa tạo) | `python -m api.migrate apply --init-base` (chạy `SQL_Querysql.sql` + migration) |
| Đã có 6 bảng gốc và dữ liệu | **Sao lưu trước**, rồi `python -m api.migrate apply` (chỉ chạy migration còn thiếu) |
| Có một phần bảng gốc | Lệnh tự dừng để tránh hỏng dữ liệu; kiểm tra thủ công |

`SQL_Querysql.sql` không bao giờ được chạy lại trên database đã có dữ liệu. Migration 001 đổi khóa chính `CT_VIPHAM`
sang cột `ID` riêng (giữ nguyên dữ liệu) để một sinh viên có thể vi phạm cùng loại nhiều lần. Sao lưu:
`sqlcmd -S .\SQLEXPRESS -E -C -Q "BACKUP DATABASE CampusIntegrityMonitor TO DISK='D:\backup\cim.bak'"`.

## 4. Tạo tài khoản Quản trị viên đầu tiên

```powershell
python -m api.cli create-admin --username admin_ctsv --full-name "Quản trị hệ thống"
cd ..
```

Mật khẩu nhập ẩn hai lần (≥ 8 ký tự, có chữ hoa, chữ thường, chữ số). Lệnh chỉ tạo được admin **đầu tiên**; các tài khoản
sau tạo trên Web (trang *Tài khoản & phân quyền*). Không có tài khoản mặc định trong mã nguồn.

## 5. Chạy API và Web (mỗi lệnh một cửa sổ PowerShell)

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-api.ps1     # http://127.0.0.1:8000  (/docs, /api/v1/health)
powershell -ExecutionPolicy Bypass -File scripts\start-web.ps1     # http://127.0.0.1:8080/login.php
```

Kiểm tra toàn bộ (cửa sổ thứ ba):

```powershell
powershell -ExecutionPolicy Bypass -File scripts\check-system.ps1
Invoke-RestMethod http://127.0.0.1:8000/api/v1/health
```

Tất cả dòng phải là `[ OK ]`. Máy khác trong mạng truy cập: chạy `start-api.ps1 -BindHost 0.0.0.0`, `start-web.ps1 -BindHost 0.0.0.0`
và mở port trong Windows Firewall. `php -S` chỉ phù hợp chạy thử/nội bộ; môi trường chính thức nên dùng IIS hoặc Apache trỏ document root
vào `Website\ui` (không đưa thư mục `Website\tools` ra web).

## 6. Cấu hình trên Web (đăng nhập admin)

1. **Camera & khu vực → Khu vực**: thêm khu vực (ví dụ `CONG_CHINH` – Cổng chính).
2. **Camera**: thêm camera thuộc khu vực. Hộp thoại hiển thị **khóa thiết bị một lần**, hãy sao chép lại ngay.
3. **Học kỳ**: khai báo ngày bắt đầu/kết thúc (cần cho báo cáo theo học kỳ).
4. **Tài khoản & phân quyền**: tạo tài khoản Nhân viên CTSV.
5. **Danh mục → Loại vi phạm**: 3 loại có mã AI `UNIFORM`, `NO_CARD`, `BOTH`; có thể đổi tên, điểm tham khảo, ngừng sử dụng.

## 7. Chạy Desktop AI

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-desktop.ps1
```

Trong mục **Kết nối API ghi nhận vi phạm**: nhập `http://127.0.0.1:8000`, mã camera, khóa thiết bị → **Kiểm tra kết nối API**
(phải hiện `✔ Đã kết nối: <camera> - <khu vực>`). Chọn *Webcam / Camera* hoặc *Tệp Video* → **Bắt đầu giám sát**.
Cảnh báo vi phạm xuất hiện ở banner đỏ và tab *Cảnh báo vi phạm*; thanh trạng thái hiển thị số bản ghi đã gửi / chờ gửi / bị từ chối.

## 8. Kiểm thử luồng dữ liệu end-to-end

| Bước | Thao tác | Kết quả mong đợi |
|---|---|---|
| 1 | `scripts\check-system.ps1` | Tất cả `[ OK ]` |
| 2 | Desktop: phát video/camera có người | Tab *Cảnh báo vi phạm* có dòng mới, *Đã gửi* tăng, *Chờ gửi* về 0 |
| 3 | Web (CTSV) → **Bản ghi vi phạm** | Bản ghi mới với đúng camera/khu vực, ảnh *Còn ảnh*; *Chi tiết* hiển thị ảnh crop, **không có MSSV/họ tên** |
| 4 | Cập nhật trạng thái *Đã xử lý* + ghi chú | Lưu thành công, lọc theo trạng thái thấy bản ghi |
| 5 | **Tổng quan** / **Thống kê & báo cáo** | Số liệu hôm nay tăng; xuất Excel/PDF tải về được |
| 6 | **Sinh viên** → xem chi tiết một sinh viên → *Vi phạm ghi nhận thủ công* | Sau khi CTSV tự đối chiếu: ghi nhận loại lỗi + ngày; ghi lần 2 cùng loại cùng ngày sẽ hỏi xác nhận; cột *Vi phạm tác phong* tăng |
| 7 | Tắt API rồi để Desktop phát hiện vi phạm, sau đó bật lại API | *Chờ gửi* tăng khi mất kết nối, tự gửi lại, không tạo bản ghi trùng |
| 8 | Ngừng sử dụng camera trên Web rồi *Kiểm tra kết nối API* trên Desktop | Desktop báo camera ngừng sử dụng và không ghi nhận vi phạm |

Kiểm tra trực tiếp trong SQL Server (cần `-I` khi dùng sqlcmd):

```powershell
sqlcmd -S .\SQLEXPRESS -E -C -I -d CampusIntegrityMonitor -Q "SELECT TOP 5 b.ID, b.THOI_DIEM, l.MA_LOI, c.MA_CAMERA, b.ANH_TRANG_THAI FROM BAN_GHI_VIPHAM b JOIN LOI_VIPHAM l ON l.ID=b.LOI_ID JOIN CAMERA c ON c.ID=b.CAMERA_ID ORDER BY b.ID DESC"
```

## 9. Test tự động

```powershell
cd Final_System
python -m pytest                                      # unit test (test cần DB tự bỏ qua)
# Integration test: dùng database TEST riêng (tên chứa "test"; các bảng mới bị xóa sạch khi chạy)
sqlcmd -S .\SQLEXPRESS -E -C -Q "CREATE DATABASE CampusIntegrityMonitor_Test"
$env:DB_SERVER=".\SQLEXPRESS"; $env:DB_TRUSTED_CONNECTION="yes"; $env:DB_TRUST_SERVER_CERTIFICATE="yes"
$env:DB_NAME="CampusIntegrityMonitor_Test"; python -m api.migrate apply --init-base
$env:CIM_TEST_DB_NAME="CampusIntegrityMonitor_Test"; python -m pytest
cd ..\Website
..\tools\php\php.exe tests\catalog_module_test.php
..\tools\php\php.exe tests\student_module_test.php
..\tools\php\php.exe tests\web_auth_test.php
..\tools\php\php.exe tests\student_violation_db_test.php   # dùng CIM_TEST_DB_NAME, rollback sau khi chạy
```

## 10. Xử lý sự cố

| Triệu chứng | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| `[LỖI] ODBC driver` | Tên `DB_DRIVER` khác driver đã cài | Đặt đúng tên in ra trong dòng lỗi |
| `Không thể kết nối tới SQL Server` | Sai `DB_SERVER`, dịch vụ dừng, TCP/IP tắt, firewall | `Get-Service MSSQL*`; SQL Server Configuration Manager → bật TCP/IP; với instance có tên bật SQL Browser |
| Lỗi chứng chỉ (SSL/certificate) | SQL Server dùng chứng chỉ tự ký | `DB_TRUST_SERVER_CERTIFICATE=yes` (mạng nội bộ) hoặc cài chứng chỉ hợp lệ |
| `Login failed for user` | Sai mật khẩu, hoặc tài khoản Windows chạy Web/API chưa có quyền | Kiểm tra `DB_USER/DB_PASSWORD` hoặc cấp quyền cho identity |
| `[LỖI] Extension pdo_sqlsrv` | PHP chưa nạp driver | Chạy `scripts\setup-php-windows.ps1` hoặc thêm DLL đúng phiên bản PHP (TS/NTS, x64) |
| Web báo "Không kết nối được tới máy chủ API" | API chưa chạy / sai `CIM_API_BASE_URL` | Chạy `start-api.ps1`, kiểm tra URL |
| Đăng nhập báo `CIM_AUTH_SECRET` chưa cấu hình | Thiếu secret trên API | Điền `.env`, khởi động lại API |
| `QUOTED_IDENTIFIER` khi chạy SQL thủ công | sqlcmd mặc định tắt | Thêm `-I` |
| Desktop: "Thông tin xác thực thiết bị không hợp lệ" | Sai mã camera/khóa, hoặc khóa đã được cấp lại | Cấp lại khóa trên Web và nhập lại |
| Đăng nhập bị khóa 15 phút | Sai mật khẩu nhiều lần | Chờ hết thời gian hoặc admin đặt lại mật khẩu |
