# Campus-Integrity-Monitor
Hệ thống quản lý tác phong sinh viên: **Desktop** giám sát camera và phát hiện vi phạm bằng AI (đồng phục, thẻ sinh viên),
**Web** cho Phòng CTSV quản lý dữ liệu, tra cứu vi phạm, thống kê và xuất báo cáo, **API** làm trung gian giữa Desktop, Web và **SQL Server**.

> AI **không nhận diện danh tính** và không gán MSSV/họ tên cho người trong ảnh. CTSV tự đối chiếu danh tính và xử lý điểm rèn luyện
> bên ngoài hệ thống. Ảnh bằng chứng tự xóa sau 30 ngày; thông tin thời gian, khu vực, camera và loại vi phạm được giữ lại để thống kê.

## Demo
**DESKTOP APP DEMO**
![Desktop app demo](./README_Images/App%20Image.png)

**FAST API DEMO**
![API demo](./README_Images/API%20Image.png)

## Kiến trúc

```
 Desktop (Tkinter + YOLO + MobileNetV3)                 Web (PHP 8.1+, Tailwind, Chart.js)
   camera/video → phát hiện + theo dõi người             trình duyệt ──► Website/ui/*.php
   → phân loại đồng phục & thẻ → bỏ phiếu nhiều khung       │               │
   → sự kiện vi phạm + ảnh crop → outbox trên đĩa            │  api/cim.php (proxy, session, CSRF)
        │ multipart + X-Camera-Code/X-Device-Key             │               │ Bearer token (phía server)
        ▼                                                    ▼               ▼
   ┌────────────────────────── FastAPI (Final_System/api) ──────────────────────────┐
   │ tài khoản · khu vực/camera · học kỳ · bản ghi vi phạm · ảnh bằng chứng · báo cáo │
   │ job xóa ảnh hết hạn (30 ngày)                                                    │
   └───────────────────────────────┬─────────────────────────────────────────────────┘
                                   ▼ pyodbc (ODBC Driver 17/18)
                              SQL Server  ◄── PDO sqlsrv ── Website/ui/api/students.php, catalogs.php
                                                             (Sinh viên, Khoa/Ngành/Lớp, Loại vi phạm, import Excel)
```

Mỗi bảng chỉ có **một** thành phần được ghi để tránh hai luồng dữ liệu mâu thuẫn:

| Bảng | Ghi bởi | Đọc bởi |
|---|---|---|
| `KHOA`, `NGANH`, `LOP`, `SINHVIEN`, `CT_VIPHAM`, `LOI_VIPHAM` | Web PHP (module sinh viên/danh mục có sẵn) | API đọc `LOI_VIPHAM` |
| `TAIKHOAN`, `KHU_VUC`, `CAMERA`, `HOC_KY`, `BAN_GHI_VIPHAM`, `NHAT_KY_XOA_ANH` | API | Web qua proxy `api/cim.php` |

## Cấu trúc thư mục

```
Campus-Integrity-Monitor/
├── SQL_Querysql.sql              # Schema gốc + dữ liệu mẫu (giữ nguyên)
├── Database/migrations/          # Migration nâng cấp + script rollback
├── .env.example                  # Mẫu cấu hình dùng chung (API, Web, Desktop)
├── docs/API.md                   # Tài liệu API, ví dụ request/response
├── Final_System/
│   ├── ai/                       # Pipeline AI, temporal voting, sinh sự kiện vi phạm
│   ├── api/                      # FastAPI: routers/, repositories/, database.py, retention.py, migrate.py, cli.py
│   ├── desktop/                  # Ứng dụng Desktop + violation_sender.py (outbox, gửi lại)
│   ├── models/                   # Trọng số đã huấn luyện
│   └── test/                     # pytest: unit/, integration/ (SQL Server), reporting/
└── Website/
    ├── ui/                       # Trang PHP, api/ (students, catalogs, cim proxy), assets/
    └── tests/                    # Test PHP (chạy bằng php CLI)
```

> **Hướng dẫn từng bước trên Windows (cài đặt, `.env`, khởi chạy, kiểm thử luồng dữ liệu, xử lý sự cố):
> [docs/HUONG_DAN_WINDOWS.md](docs/HUONG_DAN_WINDOWS.md).** Script tiện ích nằm trong `scripts/`:
> `setup-php-windows.ps1` (PHP + pdo_sqlsrv vào `tools\php`), `start-api.ps1`, `start-web.ps1`, `start-desktop.ps1`, `check-system.ps1`.

## 1. Chuẩn bị

| Thành phần | Yêu cầu |
|---|---|
| SQL Server | 2017+ (Express được), quyền tạo bảng trên database của hệ thống |
| API / Desktop | Python 3.10+, [Microsoft ODBC Driver 18 (hoặc 17) for SQL Server](https://learn.microsoft.com/sql/connect/odbc/download-odbc-driver-for-sql-server) |
| Web | PHP 8.1+ với `pdo_sqlsrv` ([Microsoft Drivers for PHP for SQL Server](https://learn.microsoft.com/sql/connect/php/download-drivers-php-sql-server)), `mbstring`, `fileinfo`, `zip`, `allow_url_fopen=On` |

```powershell
cd Final_System
python -m pip install -r requirements.txt
```

## 2. Cấu hình `.env`

```powershell
copy .env.example .env      # ở thư mục gốc repository
```

Điền tối thiểu:

| Biến | Ý nghĩa |
|---|---|
| `DB_SERVER`, `DB_NAME` | Ví dụ `localhost\SQLEXPRESS`, `CampusIntegrityMonitor` |
| `DB_TRUSTED_CONNECTION` | `yes` = Windows Authentication (bỏ trống `DB_USER`/`DB_PASSWORD`); `no` = SQL Authentication |
| `DB_USER`, `DB_PASSWORD` | Tài khoản SQL Server khi dùng SQL Authentication |
| `DB_DRIVER` | Tên driver ODBC đã cài, ví dụ `ODBC Driver 18 for SQL Server` |
| `DB_TRUST_SERVER_CERTIFICATE` | `yes` nếu SQL Server dùng chứng chỉ tự ký (thường gặp ở máy cục bộ) |
| `CIM_AUTH_SECRET` | ≥ 32 ký tự ngẫu nhiên: `python -c "import secrets; print(secrets.token_urlsafe(48))"` |
| `CIM_API_BASE_URL` | Địa chỉ API mà Web PHP gọi tới |

API và Web đọc cùng file `.env`; biến môi trường thật của hệ điều hành/web server luôn được ưu tiên.
Windows Authentication với Web: web server (IIS/Apache) phải chạy bằng tài khoản Windows có quyền trên database.

## 3. Khởi tạo / nâng cấp database an toàn

```powershell
cd Final_System
python -m api.migrate status                # xem bảng gốc và migration đã áp dụng
python -m api.migrate apply --init-base     # database TRỐNG: chạy SQL_Querysql.sql rồi migration
python -m api.migrate apply                 # database ĐÃ CÓ dữ liệu: chỉ áp dụng migration còn thiếu
python -m api.cli check-config             # kiểm tra .env, ODBC driver, kết nối, schema (không in secret)
python -m api.cli create-admin --username admin_ctsv --full-name "Quản trị hệ thống"
```
`create-admin` chỉ tạo **admin đầu tiên** (từ chối khi đã có admin đang hoạt động, trừ khi thêm `--additional`).
Kiểm tra phía Web: `php Website/tools/check_web.php` (PHP, pdo_sqlsrv, kết nối SQL Server, migration, API).

- `SQL_Querysql.sql` **chỉ** được chạy khi database chưa có bảng nào của hệ thống; database đã có dữ liệu sẽ không bị khởi tạo lại.
- Migration `001` chạy trong một transaction, chạy lại nhiều lần vẫn an toàn, **không xóa bảng/dữ liệu**. Migration gồm:
  - thêm `MA_LOI`, `DANG_SU_DUNG` cho `LOI_VIPHAM` (gắn mã `UNIFORM`/`NO_CARD`/`BOTH` cho 3 loại lỗi mẫu, không đổi ID);
  - `CT_VIPHAM` có cột `ID` riêng thay cho khóa chính ghép `(ID_LOI, ID_SV)`, nên một sinh viên vi phạm cùng loại nhiều lần vẫn lưu được (dữ liệu cũ giữ nguyên);
  - bảng mới `TAIKHOAN`, `KHU_VUC`, `CAMERA`, `HOC_KY`, `BAN_GHI_VIPHAM` (**không có cột danh tính sinh viên**), `NHAT_KY_XOA_ANH`.
- Có thể chạy thủ công bằng sqlcmd/SSMS:
  `sqlcmd -S <server> -d <database> -E -C -I -f 65001 -i Database\migrations\001_violation_records_accounts_cameras.sql`
  (`-I` bật `QUOTED_IDENTIFIER`, bắt buộc vì có filtered index; khi chạy INSERT/UPDATE/DELETE thủ công bằng sqlcmd cũng cần `-I`.
  SSMS, pyodbc và PDO sqlsrv đã bật sẵn.)
- **Rollback**: `Database\migrations\001_violation_records_accounts_cameras.rollback.sql` (sao lưu trước; script tự dừng nếu `CT_VIPHAM`
  đã có vi phạm lặp không thể đưa về khóa chính ghép).
- Không có tài khoản mặc định trong mã nguồn; mật khẩu admin nhập ẩn (hoặc qua biến `CIM_ADMIN_PASSWORD` khi tự động hóa).

## 4. Chạy hệ thống

**API**
```powershell
cd Final_System
python -m uvicorn api.main:app --host 0.0.0.0 --port 8000
```
Tài liệu: `http://127.0.0.1:8000/docs` · Sức khỏe: `http://127.0.0.1:8000/api/v1/health` · Chi tiết: [docs/API.md](docs/API.md)

**Web** — trỏ document root của web server vào `Website/ui`, hoặc chạy thử:
```powershell
php -S 127.0.0.1:8080 -t Website/ui
```
Đăng nhập tại `http://127.0.0.1:8080/login.php` bằng tài khoản đã tạo ở bước 3. Sau đó vào **Camera & khu vực** để khai báo
khu vực, camera (lưu lại **khóa thiết bị** chỉ hiện một lần) và học kỳ.

**Desktop**
```powershell
cd Final_System
python main.py
```
Ở mục **Kết nối API ghi nhận vi phạm**: nhập địa chỉ API, mã camera, khóa thiết bị → **Kiểm tra kết nối API**.
Cấu hình được lưu ở `Final_System/desktop/config.json` (không commit). Sự kiện vi phạm được lưu vào `desktop/outbox/` trước khi
gửi; nếu mất mạng sẽ tự gửi lại, không tạo bản ghi trùng (khóa `event_id`). Sự kiện bị API từ chối được chuyển vào `outbox/failed/` kèm lý do.

**Xóa ảnh hết hạn**: API tự chạy job mỗi `CIM_RETENTION_INTERVAL_MINUTES`. Có thể chạy độc lập bằng Task Scheduler/cron:
`python -m api.retention --once` (có khóa chống chạy trùng, chỉ xóa trong thư mục ảnh, ghi log vào `NHAT_KY_XOA_ANH`).

**Docker (chỉ API)**: `docker build -t campus-integrity-monitor Final_System` rồi chạy với `-e DB_SERVER=... -e CIM_AUTH_SECRET=...`
và volume `/data/evidence`.

## 5. Vi phạm ghi nhận thủ công (CT_VIPHAM)

Trong **Sinh viên → Xem chi tiết**, CTSV ghi nhận vi phạm vào hồ sơ **sau khi tự đối chiếu danh tính** (ví dụ từ ảnh bằng chứng
và nhắc nhở trực tiếp). Mỗi lần ghi nhận có `ID` riêng, cho phép cùng một loại lỗi nhiều lần; cùng loại + cùng ngày sẽ hỏi xác nhận
để tránh bấm trùng. Hệ thống **không** tự liên kết bản ghi AI hay ảnh với sinh viên và **không** tự trừ điểm rèn luyện
(điểm trong loại vi phạm chỉ để tham khảo). Cần migration 001.

## 6. Nhận diện vi phạm trên Desktop

- Pipeline: YOLO phát hiện và theo dõi người (BoT-SORT), cắt nửa thân trên, rồi hai mô hình MobileNetV3 phân loại đồng phục và thẻ.
- Chỉ kết quả có độ tin cậy ≥ `CLASSIFY_CONF` mới được dùng. Cần tối thiểu `VOTING_THREDSHOLD` khung hình, và chỉ kết luận vi phạm
  khi tỷ lệ khung hình vi phạm ≥ `VIOLATION_RATIO` (mặc định 60%) trong cửa sổ `LEN_HISTORY`.
- Bốn trạng thái: đúng tác phong, sai đồng phục (`UNIFORM`), thiếu thẻ (`NO_CARD`), vi phạm cả hai (`BOTH`).
- Chống ghi lặp: mỗi người (track) chỉ được ghi một lần trong `EVENT_DEDUP_SECONDS`.
- Không tạo bản ghi khi khung hình hoặc ảnh crop không hợp lệ, khi khóa thiết bị sai, hoặc khi camera/khu vực đã ngừng sử dụng.

![AI Pipeline diagram](./README_Images/AI%20Processing%20Pipeline.png)

## 7. Kiểm thử

```powershell
cd Final_System
python -m pytest                                   # unit test (integration test tự bỏ qua nếu không có DB test)
# Integration test với SQL Server: dùng database RIÊNG cho test (tên phải chứa "test"; các bảng mới sẽ bị xóa sạch khi chạy)
$env:CIM_TEST_DB_NAME="CampusIntegrityMonitor_Test"; $env:DB_SERVER=".\SQLEXPRESS"; $env:DB_TRUSTED_CONNECTION="yes"; $env:DB_TRUST_SERVER_CERTIFICATE="yes"
python -m pytest
cd ..\Website
php tests/catalog_module_test.php; php tests/student_module_test.php; php tests/web_auth_test.php
php tests/student_violation_db_test.php   # cần CIM_TEST_DB_NAME; chạy trong transaction rồi rollback
```

## Future Work
- Model Optimization (ONNX Runtime) và benchmark độ trễ/throughput.
- Đánh giá độ chính xác mô hình trên bộ dữ liệu camera thực tế của trường.
- CI/CD: lint, test, build container.
