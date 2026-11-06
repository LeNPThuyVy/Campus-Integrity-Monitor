# Kế hoạch Test Manual toàn hệ thống – Campus Integrity Monitor

> Phạm vi: Desktop AI (`Final_System/desktop`, `Final_System/ai`) → API FastAPI (`Final_System/api`) → SQL Server → Web PHP (`Website/ui`) → Thống kê/Báo cáo → Retention.
> Không bao gồm web Laravel `QuanLyTacPhongSV/`.

**Thông tin lần test**

| Mục | Giá trị |
|---|---|
| Người test | |
| Ngày test | |
| Commit / build | |
| Máy / OS | |
| SQL Server / DB name | |
| URL API | `http://127.0.0.1:8001` |
| URL Web | `http://127.0.0.1:8080` |

**Cách ghi kết quả:** điền cột *Thực tế* và *KQ* (`P` = Pass, `F` = Fail, `B` = Blocked, `N/A`). Case có ghi `[KI-x]` liên quan lỗi đã biết ở **Phụ lục A**. Nếu case fail đúng như mô tả lỗi đã biết, ghi `F (KI-x)`.

---

## 0. Chuẩn bị môi trường

### 0.1 Cấu hình
1. Copy `.env.example` → `.env` ở thư mục gốc, điền:
   - `DB_SERVER`, `DB_NAME`, `DB_DRIVER=ODBC Driver 18 for SQL Server`, `DB_TRUSTED_CONNECTION` hoặc `DB_USER`/`DB_PASSWORD`
   - `CIM_AUTH_SECRET` (≥ 32 ký tự)
   - `CIM_API_BASE_URL=http://127.0.0.1:8001` (thống nhất port **8001** với scripts/Desktop) `[KI-8]`
   - `CIM_ENABLE_INFERENCE=false` (không cần cho luồng Desktop)
   - (tuỳ chọn) `GEMINI_API_KEY` để test PDF có phân tích AI
2. Migrate & tạo admin:
   ```powershell
   cd Final_System
   python -m api.migrate status
   python -m api.migrate apply
   python -m api.cli check-config
   python -m api.cli create-admin --username admin --full-name "Quản trị viên"
   ```
3. Khởi động (mỗi lệnh 1 cửa sổ):
   ```powershell
   .\scripts\start-api.ps1
   .\scripts\start-web.ps1
   .\scripts\start-desktop.ps1
   .\scripts\check-system.ps1
   ```

### 0.2 Dữ liệu test cần có
| Dữ liệu | Giá trị gợi ý |
|---|---|
| Tài khoản ADMIN | `admin` |
| Tài khoản CTSV | `ctsv01` (tạo ở mục 4) |
| Khu vực | KV1 “Cổng chính”, KV2 “Sảnh A” (seed migration 003 có sẵn 4 khu vực) |
| Camera | `CAM-01` thuộc KV1 (seed sẵn) |
| Học kỳ | 1 học kỳ chứa ngày hôm nay |
| Danh mục | 1 Khoa, 1 Ngành, 1 Lớp, ≥ 3 Sinh viên |
| Video mẫu | (a) người mặc đồng phục + đeo thẻ, (b) không đồng phục + có thẻ, (c) đồng phục + không thẻ, (d) không đồng phục + không thẻ, (e) người đứng xa camera |

### 0.3 SQL kiểm tra nhanh
```sql
SELECT TOP 20 p.ID, p.MA_SU_KIEN, p.LOI_ID, p.THOI_DIEM, p.TINH_TRANG_XU_LY, p.XU_LY_LUC,
       a.CAMERA_ID, a.ANH_KHOA, a.ANH_KICH_THUOC, a.THOI_GIAN_HET_HAN
FROM PHAT_HIEN_VIPHAM p LEFT JOIN ANH_VIPHAM a ON a.PHAT_HIEN_ID = p.ID
ORDER BY p.ID DESC;

SELECT ID, USERNAME, ROLE, ACTIVE, FAILED_ATTEMPTS, LOCKED_UNTIL, TOKEN_VERSION FROM TAIKHOAN;
SELECT * FROM CT_VIPHAM ORDER BY ID DESC;
```

---

## 1. Smoke test

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| SM-01 | API chạy | Mở `http://127.0.0.1:8001/health` | 200, trạng thái ok | | |
| SM-02 | API chạy | Mở `/api/v1/health` | 200, có thông tin evidence dir ghi được, inference, giờ server | | |
| SM-03 | API chạy, **tắt SQL Server** | Mở `/api/v1/health` | Báo lỗi DB `[KI-5]` | | |
| SM-04 | API chạy | Mở `/docs` | Swagger hiển thị đủ nhóm endpoint | | |
| SM-05 | Web chạy | Mở `http://127.0.0.1:8080/` | Chuyển hướng tới `login.php` | | |
| SM-06 | Desktop | Chạy `start-desktop.ps1` | Cửa sổ Desktop mở, model load không lỗi | | |
| SM-07 | — | Chạy `scripts/check-system.ps1` và `php Website/tools/check_web.php` | Không có mục FAIL | | |

---

## 2. Xác thực & phiên đăng nhập (AUTH)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| AU-01 | Có `admin` | Nhập đúng “Tên đăng nhập”, “Mật khẩu” → “Đăng nhập” | Vào `home.php`, sidebar hiện tên + vai trò “Quản trị viên” | | |
| AU-02 | — | Sai mật khẩu | Thông báo sai thông tin đăng nhập, không lộ tài khoản có tồn tại hay không | | |
| AU-03 | — | Để trống trường | Validate phía client, không gửi request | | |
| AU-04 | — | Nút hiện/ẩn mật khẩu | Ô mật khẩu chuyển text/password | | |
| AU-05 | Tài khoản `ctsv01` | Sai mật khẩu 5 lần liên tiếp | Lần thứ 5 (hoặc 6) báo tài khoản bị tạm khóa (HTTP 423); `TAIKHOAN.LOCKED_UNTIL` ≈ now + 15 phút | | |
| AU-06 | Sau AU-05 | Đăng nhập bằng mật khẩu **đúng** | Vẫn bị từ chối vì đang khóa | | |
| AU-07 | Sau AU-05 | Đặt `LOCKED_UNTIL` về quá khứ bằng SQL (hoặc chờ 15 phút) → đăng nhập đúng | Thành công, `FAILED_ATTEMPTS` về 0 | | |
| AU-08 | Tài khoản bị vô hiệu hóa | Đăng nhập | Bị từ chối (403), thông báo tài khoản bị vô hiệu hóa | | |
| AU-09 | Chưa đăng nhập | Mở trực tiếp `list_vipham.php` | Chuyển tới `login.php?next=...`; đăng nhập xong quay về `list_vipham.php` | | |
| AU-10 | Chưa đăng nhập | Mở `login.php?next=https://evil.com` rồi đăng nhập | Không chuyển ra ngoài, về `home.php` | | |
| AU-11 | Đã đăng nhập | Menu tài khoản → “Đăng xuất” | Về `login.php?logged_out=...` có thông báo; Back trình duyệt không vào lại được trang | | |
| AU-12 | Đã đăng nhập | Gõ URL `logout.php` (GET) | Không đăng xuất, chỉ chuyển hướng về trang chủ | | |
| AU-13 | Đặt `CIM_WEB_SESSION_IDLE_MINUTES=1`, restart web | Đăng nhập, để yên tab (đóng các trang có polling hoặc chờ) > 1 phút, rồi click menu | Về login với thông báo “Phiên đăng nhập đã hết hạn...” (polling nền `X-CIM-Background` không giữ phiên) | | |
| AU-14 | Đã đăng nhập | Ghi lại cookie session trước/sau khi login (DevTools) | Session ID thay đổi sau khi đăng nhập | | |

### 2.1 Đổi mật khẩu (`doi_mat_khau.php`)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| PW-01 | Đã đăng nhập | “Mật khẩu hiện tại” sai | Lỗi mật khẩu hiện tại không đúng (400) | | |
| PW-02 | | Mật khẩu mới < 8 ký tự / thiếu chữ hoa / thiếu chữ thường / thiếu số | Bị từ chối, nêu rõ yêu cầu; thanh độ mạnh hiển thị “Yếu” | | |
| PW-03 | | Mật khẩu mới trùng mật khẩu cũ | Bị từ chối | | |
| PW-04 | | “Nhập lại mật khẩu mới” không khớp | Báo không khớp, không gửi | | |
| PW-05 | | Đổi hợp lệ | Bị đăng xuất, về `login.php?password_changed=1` có thông báo; đăng nhập bằng mật khẩu mới OK, mật khẩu cũ fail | | |
| PW-06 | Đăng nhập cùng tài khoản ở 2 trình duyệt | Đổi mật khẩu ở trình duyệt A, thao tác ở trình duyệt B | B bị đẩy về login (token cũ bị thu hồi, `TOKEN_VERSION` tăng) | | |

---

## 3. Phân quyền (RBAC)

Thực hiện với cả 2 tài khoản `admin` (ADMIN) và `ctsv01` (CTSV).

| ID | Chức năng | ADMIN mong đợi | CTSV mong đợi | Thực tế ADMIN | Thực tế CTSV | KQ |
|---|---|---|---|---|---|---|
| RB-01 | Menu “Tổng quan”, “Bản ghi vi phạm”, “Thống kê & báo cáo”, “Sinh viên”, “Danh mục”, “Camera & khu vực” | Hiện | Hiện | | | |
| RB-02 | Menu “Tài khoản & phân quyền” | Hiện | **Ẩn** | | | |
| RB-03 | Gõ URL `phanquyen.php` | Vào được | Trang “Không có quyền truy cập”, HTTP 403 | | | |
| RB-04 | Tab Khu vực / Camera: tạo, sửa, vô hiệu hóa | Được | Chỉ xem (nhãn “Chỉ xem”, không có form) | | | |
| RB-05 | Tab Học kỳ: tạo/sửa/xóa | Được | Được | | | |
| RB-06 | Danh mục Khoa/Ngành/Lớp/Loại vi phạm: thêm/sửa/xóa | Được | Được | | | |
| RB-07 | Sinh viên: thêm/sửa/xóa/import/ghi nhận vi phạm | Được | Được | | | |
| RB-08 | Cập nhật trạng thái vi phạm | Được | Được | | | |
| RB-09 | “Xóa toàn bộ bản ghi phát hiện” | Được | Được | | | |
| RB-10 | Xuất Excel/PDF báo cáo | Được | Được | | | |
| RB-11 | DevTools: gửi POST tới `api/cim.php` **không** có header `X-CSRF-Token` | Bị chặn (403) | Bị chặn (403) | | | |
| RB-12 | CTSV gọi trực tiếp API `GET /api/v1/accounts` bằng token CTSV (Swagger/curl) | — | 403 | | | |

---

## 4. Quản lý tài khoản (`phanquyen.php`, ADMIN)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| AC-01 | Login admin | “Tạo tài khoản”: Tên đăng nhập `ctsv01`, Họ tên, Vai trò CTSV, Mật khẩu ban đầu hợp lệ | Tạo thành công, xuất hiện trong bảng, Trạng thái “Đang hoạt động” | | |
| AC-02 | | Tên đăng nhập < 3 ký tự, > 50 ký tự, có dấu cách/ký tự `@`, `#`, tiếng Việt có dấu | Bị từ chối | | |
| AC-03 | | Tạo trùng `ctsv01` | Báo trùng (409) | | |
| AC-04 | | Mật khẩu ban đầu yếu | Bị từ chối | | |
| AC-05 | | Sửa họ tên, đổi vai trò CTSV → ADMIN → CTSV | Lưu đúng; đăng nhập lại bằng tài khoản đó thấy menu thay đổi theo vai trò | | |
| AC-06 | | Bỏ tick “Đang hoạt động” của `ctsv01` | Có hộp xác nhận; sau khi lưu: “Đã vô hiệu hóa”; phiên đang mở của `ctsv01` bị đẩy ra ở request kế tiếp | | |
| AC-07 | | Admin tự bỏ tick “Đang hoạt động” của chính mình | Bị từ chối | | |
| AC-08 | | Admin tự đổi vai trò mình thành CTSV | Bị từ chối | | |
| AC-09 | Chỉ có 1 admin active | Vô hiệu hóa/hạ quyền admin đó bằng admin thứ 2 sau khi admin thứ 2 bị vô hiệu | Hệ thống luôn giữ ≥ 1 admin active | | |
| AC-10 | | “Đặt lại mật khẩu” cho `ctsv01` | Thành công; `ctsv01` đăng nhập bằng mật khẩu mới; phiên cũ bị thu hồi | | |
| AC-11 | `ctsv01` đang bị khóa (AU-05) | Xem bảng | Trạng thái “Tạm khóa đến …” | | |
| AC-12 | | Cột “Đăng nhập gần nhất” | Cập nhật sau mỗi lần đăng nhập thành công | | |

---

## 5. Danh mục (`danh_muc.php`)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| CA-01 | | Chọn Khoa → thêm Mã khoa (≤ 10), Tên khoa (≤ 50) → “Lưu danh mục” | Thêm thành công, hiện trong bảng | | |
| CA-02 | | Mã khoa 11 ký tự / Tên khoa 51 ký tự / để trống | Lỗi validate (422) | | |
| CA-03 | | Thêm Khoa trùng mã | Lỗi trùng (409) | | |
| CA-04 | | “Sửa” Khoa → đổi tên → Lưu; thử “Hủy sửa” | Lưu đúng; Hủy sửa trả form về trạng thái thêm mới | | |
| CA-05 | Khoa đã có Ngành | “Xóa” Khoa | Hộp confirm; bị chặn vì đang được tham chiếu (409) | | |
| CA-06 | | Ngành: thêm với Khoa cha; Lớp: thêm (Mã ≤ 20) với Ngành cha | Thành công; trong trang Sinh viên chọn cascade thấy dữ liệu mới | | |
| CA-07 | Lớp có sinh viên | Xóa Lớp | Bị chặn (409) | | |
| CA-08 | | Loại vi phạm: sửa TEN_LOI (≤ 100), DIEM_TRU (số nguyên), DANG_SU_DUNG | Lưu đúng; `MA_LOI` (“Mã AI”) chỉ hiển thị, không sửa được | | |
| CA-09 | | DIEM_TRU nhập chữ / số thập phân | Bị từ chối | | |
| CA-10 | Tắt DANG_SU_DUNG của loại `NO_CARD` | Cho Desktop gửi vi phạm NO_CARD (mục 9) | API trả 409; sự kiện vào `outbox/failed`. Bật lại sau khi test | | |
| CA-11 | | “Làm mới từ cơ sở dữ liệu” sau khi sửa trực tiếp trong DB | Bảng cập nhật | | |
| CA-12 | | Nhập tên `<script>alert(1)</script>` | Hiển thị dạng text, không chạy script | | |

---

## 6. Camera, khu vực & học kỳ (`camera_khuvuc.php`)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| FA-01 | Login admin | Tab Khu vực → tạo khu vực mới (mã, tên, mô tả) | Thành công, hiện trong bảng và trong bộ lọc “Khu vực” ở các trang khác | | |
| FA-02 | | Tạo trùng mã khu vực | 409 | | |
| FA-03 | | Sửa khu vực; vô hiệu hóa khu vực | Có hộp xác nhận khi vô hiệu hóa; trạng thái cập nhật | | |
| FA-04 | | Tab Camera → tạo camera `CAM-05` thuộc khu vực mới | Thành công `[KI-1]` | | |
| FA-05 | | Sửa camera (tên, vị trí, khu vực) | Thành công `[KI-1]` | | |
| FA-06 | | “Cấp lại khóa” camera | Hiện dialog “Khóa thiết bị cho Desktop”, “Sao chép khóa” hoạt động `[KI-2]` | | |
| FA-07 | | Cột “Kết nối gần nhất” sau khi Desktop đã gửi vi phạm | Hiển thị thời điểm kết nối `[KI-10]` | | |
| FA-08 | Vô hiệu hóa `CAM-01` (qua SQL nếu UI lỗi) | Desktop → “KIỂM TRA KẾT NỐI API” | Báo lỗi 403 camera không hoạt động | | |
| FA-09 | Vô hiệu hóa khu vực của `CAM-01` | Desktop kiểm tra kết nối | 403 | | |
| FA-10 | | Tab Học kỳ → tạo học kỳ (năm học `YYYY-YYYY`, học kỳ 1/2/3, ngày bắt đầu, ngày kết thúc); bảng hiện tên “Học kỳ N (năm học)”; thêm lại cùng năm học + học kỳ → 409; năm học `2026-2028` → 422 | Thành công; `home.php` thẻ “Học kỳ hiện tại” có số liệu nếu chứa hôm nay | | |
| FA-11 | | Tạo học kỳ có khoảng ngày chồng lấn học kỳ khác | 409 | | |
| FA-12 | | Ngày kết thúc < ngày bắt đầu | Bị từ chối | | |
| FA-13 | Học kỳ được `CT_VIPHAM (NAM_HOC, HOC_KY)` tham chiếu | “Xóa” học kỳ | Bị chặn (409) | | |
| FA-14 | Không có học kỳ nào chứa hôm nay | Mở `home.php` | Thẻ hiện “Chưa khai báo học kỳ hiện tại” | | |

---

## 7. Sinh viên (`ql_sinhvien.php`)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| ST-01 | Có dữ liệu | Mở trang | Thẻ “Tổng sinh viên”, “Số khoa”, “Số lớp” đúng với DB | | |
| ST-02 | | Tìm theo MSSV, theo họ tên (có dấu/không dấu), chuỗi > 100 ký tự | Kết quả đúng; chuỗi quá dài bị chặn | | |
| ST-03 | | Lọc Khoa → Ngành → Lớp (cascade) | Danh sách ngành/lớp phụ thuộc lựa chọn cha; kết quả lọc đúng | | |
| ST-04 | | Đổi “Số dòng”, chuyển trang | Phân trang đúng, STT liên tục | | |
| ST-05 | | “Thêm sinh viên”: MSSV (≤ 10), Họ tên, Niên khóa, Khoa/Ngành/Lớp → Lưu | Thành công | | |
| ST-06 | | Thêm trùng MSSV / MSSV > 10 ký tự / thiếu Lớp | Bị từ chối | | |
| ST-07 | | Nhập Ảnh 3x4, Ngày sinh, Giới tính, Email, SĐT → Lưu → mở lại | Ghi nhận: các trường này **không được lưu** (chỉ UI) `[KI-9]` | | |
| ST-08 | | “Xem chi tiết” → sửa họ tên, lớp → Lưu | Cập nhật đúng | | |
| ST-09 | SV không có vi phạm | “Xóa hồ sơ” | Có xác nhận, xóa thành công | | |
| ST-10 | SV có vi phạm | “Xóa hồ sơ” | Bị chặn (409) | | |
| ST-11 | | Mở chi tiết SV → “Vi phạm ghi nhận thủ công”: chọn Loại vi phạm, Ngày vi phạm, Ghi chú → “Ghi nhận vi phạm” | Thêm vào danh sách vi phạm của SV; cột “Vi phạm tác phong” tăng; `CT_VIPHAM` có bản ghi | | |
| ST-12 | | Dropdown Loại vi phạm | Chỉ có loại đang sử dụng | | |
| ST-13 | | Ngày vi phạm trước 2000-01-01 hoặc sau hôm nay; Ghi chú > 500 ký tự | Bị từ chối | | |
| ST-14 | Sau ST-11 | Ghi nhận cùng loại, cùng ngày | Cảnh báo trùng, hỏi “Vẫn ghi nhận”; chọn đồng ý → thêm được; chọn hủy → không thêm | | |
| ST-15 | | Xóa 1 vi phạm thủ công | Xóa đúng bản ghi, số đếm giảm | | |
| ST-15a | Đăng nhập CTSV | Mở trang Quản lý sinh viên; gọi `POST api/students.php` `{"action":"violation-delete-semester",...}` | Không thấy nút “Xóa vi phạm theo học kỳ”; API trả 403 | | |
| ST-15b | ADMIN, HK có vi phạm thủ công + 1 dòng `NAM_HOC`/`HOC_KY` NULL trong khoảng ngày HK | “Xóa vi phạm theo học kỳ” → chọn HK | Hiện “Sẽ xóa N bản ghi…”; nút Xóa chỉ bật khi nhập đúng mã dạng `HK1-2025-2026` | | |
| ST-15c | Sau ST-15b | Nhập mã HK → “Xóa vi phạm” | Xóa đúng N bản ghi (kể cả dòng `NAM_HOC`/`HOC_KY` NULL); vi phạm HK khác còn nguyên; `PHAT_HIEN_VIPHAM` và báo cáo không đổi | | |
| ST-15d | Mở hộp xác nhận, ở tab khác thêm 1 vi phạm vào HK đó | Bấm “Xóa vi phạm” | Báo 409 “Số bản ghi đã thay đổi”, số đếm cập nhật lại, chưa xóa gì | | |
| ST-15e | | Ghi nhận vi phạm mới có ngày nằm trong một HK | `CT_VIPHAM.NAM_HOC`, `HOC_KY` được điền đúng học kỳ chứa ngày vi phạm (ngoài mọi HK → cả hai NULL) | | |
| ST-16 | | “Xuất danh sách” | Tải file Excel, dữ liệu khớp bộ lọc hiện tại, tiếng Việt hiển thị đúng | | |

### 7.1 Import Excel

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| IM-01 | | “Nhập từ Excel” → “Tải file Excel mẫu” | Tải được file mẫu đúng cột | | |
| IM-02 | | Import lần lượt KHOA → NGANH → LOP → SINHVIEN bằng file mẫu hợp lệ | Preview hiện số “hợp lệ / trùng-bỏ qua / lỗi”; xác nhận → dữ liệu vào DB | | |
| IM-03 | | Upload file `.xls`, `.csv`, `.pdf` | Bị từ chối (chỉ `.xlsx`) | | |
| IM-04 | | Upload file > 15MB | Bị từ chối | | |
| IM-05 | | File có 1 dòng lỗi (thiếu mã, lớp không tồn tại) | Preview báo lỗi, không cho import cả lô | | |
| IM-06 | | File có dòng trùng mã đã có trong DB | Dòng trùng bị bỏ qua, các dòng còn lại import | | |
| IM-07 | | Import SINHVIEN trước khi có LOP tương ứng | Báo lỗi tham chiếu | | |
| IM-08 | | Upload → để preview > 30 phút → xác nhận | Báo preview hết hạn (410), yêu cầu upload lại | | |
| IM-09 | | Ô chứa `=HYPERLINK(...)`, `+1+1`, `@SUM` | Không bị thực thi công thức khi xuất lại; dữ liệu lưu dạng text | | |
| IM-10 | | Tên có dấu tiếng Việt, khoảng trắng thừa | Lưu đúng Unicode, được trim | | |

---

## 8. Desktop AI

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| DK-01 | Desktop mở | Nguồn “Webcam / Camera” → “▶ BẮT ĐẦU GIÁM SÁT” | Hiển thị hình webcam, khung bounding box người | | |
| DK-02 | | Nguồn “Tệp Video” → “Chọn...” file `.mp4/.avi/.mkv/.mov` → Bắt đầu | Phát video và nhận diện | | |
| DK-03 | | Chọn file không phải video | Báo lỗi, không crash | | |
| DK-04 | Đang chạy | “⏸ TẠM DỪNG GIÁM SÁT” rồi bắt đầu lại | Dừng/tiếp tục đúng | | |
| DK-05 | | “↺ Đặt Lại” | Thông số trở về mặc định | | |
| DK-06 | Video (a) đồng phục + thẻ | Chạy | Track hiện “Waiting” vài frame (< 7 kết quả), sau đó kết luận tuân thủ, **không** có cảnh báo | | |
| DK-07 | Video (b) không đồng phục + có thẻ | Chạy | Cảnh báo “Sai đồng phục” (UNIFORM) ở tab “🚨 Cảnh Báo Vi Phạm” | | |
| DK-08 | Video (c) đồng phục + không thẻ | Chạy | Cảnh báo “Thiếu thẻ sinh viên” (NO_CARD) | | |
| DK-09 | Video (d) | Chạy | Cảnh báo BOTH | | |
| DK-10 | Video (e) người ở xa | Chạy | Người có chiều cao box < “Tỷ lệ người tối thiểu” (mặc định 0.25) bị bỏ qua, không phân loại | | |
| DK-11 | Sau DK-07 | Cùng người (cùng track) tiếp tục đứng trong khung | Không gửi lặp trong “Chống lặp (giây)” (mặc định 600s) | | |
| DK-12 | | Người ra khỏi khung > “Ngưỡng biến mất” frame rồi quay lại | Được gán track mới | | |
| DK-13 | | Thay đổi từng slider: Detect Conf, Uniform Conf, Tỷ lệ người tối thiểu, Card Conf, Card IOU, IOU theo dõi, Tỷ lệ khung hình vi phạm | Kết quả nhận diện thay đổi tương ứng (ví dụ tăng Uniform Conf → dễ ra Non_Uniform hơn) | | |
| DK-14 | | Thay đổi spinbox: Khung nhảy, Độ dài lịch sử, Khung tối thiểu, Ngưỡng biến mất, Chống lặp, Chuẩn hoá sáng (0/1), Giữ nhãn | Áp dụng được, không crash | | |
| DK-15 | | Video thiếu sáng / ngược sáng, Chuẩn hoá sáng = 1 rồi = 0 | Bật chuẩn hoá cho kết quả ổn định hơn | | |
| DK-16 | | Nhập giá trị ngoài miền (âm, quá lớn) → “💾 LƯU CẤU HÌNH THÔNG SỐ” | Giá trị bị kẹp về miền hợp lệ; `desktop/config.json` được cập nhật | | |
| DK-17 | | Khởi động lại Desktop | Thông số đã lưu được nạp lại | | |
| DK-18 | | “Địa chỉ API Central Server” = `abc` → Lưu | Bị từ chối (phải bắt đầu `http://`/`https://`) | | |
| DK-19 | API chạy, “Mã Camera xác thực” = `CAM-01` | “KIỂM TRA KẾT NỐI API” | Thành công, hiện tên camera/khu vực | | |
| DK-20 | | Mã camera = `CAM-XYZ` (không tồn tại) | Báo lỗi xác thực (401) | | |
| DK-21 | API tắt, không có tiến trình ở port 8001 | Mở Desktop | Desktop tự khởi động API local trên 8001 | | |
| DK-22 | | Tab “📋 Nhật Ký Theo Dõi” | Có log các track, kết quả gửi API | | |

---

## 9. Ingest vi phạm & outbox

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| IN-01 | API + Desktop chạy | Tạo 1 vi phạm (DK-07) | File `.jpg` + `.json` xuất hiện rồi biến mất khỏi `Final_System/desktop/outbox/pending`; DB có bản ghi `PHAT_HIEN_VIPHAM` trạng thái `MOI` + `ANH_VIPHAM` (THOI_GIAN_HET_HAN ≈ +30 ngày) | | |
| IN-02 | Sau IN-01 | Kiểm tra ảnh crop | Ảnh là crop người (có padding), không phải toàn khung; không chứa thông tin định danh | | |
| IN-03 | **Tắt API** | Tạo 2–3 vi phạm | Sự kiện nằm lại trong `outbox/pending`, Desktop không treo | | |
| IN-04 | Sau IN-03 | Bật API lại | Sự kiện tự gửi lại (backoff tối đa 60s), pending trống, DB đủ bản ghi, không trùng | | |
| IN-05 | | Đổi “Mã Camera xác thực” sang mã sai khi còn sự kiện pending | Sự kiện có camera code khác bị chuyển `outbox/failed/` kèm lý do | | |
| IN-06 | Camera bị vô hiệu hóa | Tạo vi phạm | API trả 403 → Desktop tạm dừng gửi 60s, sự kiện còn trong pending; bật lại camera → gửi thành công | | |

**Kiểm tra API trực tiếp bằng curl** (PowerShell dùng `curl.exe`):
```powershell
$API="http://127.0.0.1:8001/api/v1"
$EID=[guid]::NewGuid().ToString()
$NOW=(Get-Date).ToString("yyyy-MM-ddTHH:mm:sszzz")
curl.exe -i -X POST "$API/violations" -H "X-Camera-Code: CAM-01" `
  -F "event_id=$EID" -F "violation_code=UNIFORM" -F "occurred_at=$NOW" -F "track_id=1" -F "image=@sample.jpg"
```

| ID | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|
| IN-07 | Gửi lệnh trên | 201, trả về ID bản ghi | | |
| IN-08 | Gửi lại **cùng** `event_id` | 200, `duplicate: true`, DB không thêm bản ghi | | |
| IN-09 | Không có header `X-Camera-Code` / mã sai | 401 | | |
| IN-10 | Gửi JSON thay vì multipart | 415 | | |
| IN-11 | Ảnh > 2MB (`CIM_EVIDENCE_MAX_BYTES`) | 413 | | |
| IN-12 | Ảnh < 16px hoặc > 4096px, hoặc file không phải ảnh | 422 | | |
| IN-13 | Thêm field `-F "mssv=123"` / `ho_ten` / `ma_sv` | 422 (không nhận dữ liệu định danh) | | |
| IN-14 | `occurred_at` tương lai > 5 phút | 422 | | |
| IN-15 | `occurred_at` cũ hơn 7 ngày | 422 | | |
| IN-16 | `violation_code=ABC` | 422 | | |
| IN-17 | `GET $API/device/me` với `X-Camera-Code: CAM-01` | 200, có camera, area, `violation_codes`, `max_image_bytes` | | |
| IN-18 | Gửi kèm `uniform_confidence=0.9`, `card_confidence=0.8`; mở chi tiết trên web | Hiển thị độ tin cậy `[KI-4]` | | |

---

## 10. Bản ghi vi phạm (`list_vipham.php`)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| VL-01 | Có ≥ 15 bản ghi | Mở trang | Bảng có cột Mã, Thời điểm, Loại, Khu vực/camera, Ảnh, Xử lý, “Chi tiết”; mặc định mới nhất trước | | |
| VL-02 | | Lọc “Từ ngày”/“Đến ngày” | Chỉ hiện bản ghi trong khoảng; Từ ngày > Đến ngày → báo lỗi | | |
| VL-03 | | Lọc Khu vực → Camera | Danh sách camera theo khu vực; kết quả đúng | | |
| VL-04 | | Lọc “Loại vi phạm” | Đúng loại | | |
| VL-05 | | Lọc “Trạng thái xử lý”: Mới / Đã xem / Đã xử lý / Bỏ qua (cảnh báo sai) | Đúng trạng thái | | |
| VL-06 | | “Sắp xếp” mới nhất / cũ nhất | Thứ tự đúng | | |
| VL-07 | | Nút đặt lại bộ lọc | Xóa toàn bộ điều kiện, về trang 1 | | |
| VL-08 | | “Mỗi trang” 10/20/50/100; trang trước/sau | Phân trang đúng, không trùng/mất bản ghi | | |
| VL-09 | | Cột Ảnh | Badge “Còn ảnh” cho bản ghi mới; các trạng thái khác xem mục 13 | | |
| VL-10 | | “Chi tiết” | Dialog hiện ảnh bằng chứng, loại, thời điểm, khu vực, camera, trạng thái | | |
| VL-11 | | Trong dialog → “Cập nhật xử lý” chọn “Đã xử lý”, nhập ghi chú → “Lưu trạng thái” | Toast thành công; bảng cập nhật; DB `TINH_TRANG_XU_LY=DA_XU_LY`, `XU_LY_LUC` có giá trị | | |
| VL-12 | | Mở lại chi tiết bản ghi vừa xử lý | Ghi chú và “Người xử lý” được hiển thị `[KI-4]` | | |
| VL-13 | | Chọn “Bỏ qua (cảnh báo sai)” | Có hộp xác nhận trước khi lưu | | |
| VL-14 | | Ghi chú > 500 ký tự | Bị chặn | | |
| VL-15 | Bản ghi đang “Đã xử lý” | Đặt về “Mới” | DB `XU_LY_LUC = NULL` | | |
| VL-16 | | Mở `list_vipham.php?status=MOI&id=<ID>` | Bộ lọc trạng thái = Mới, dialog chi tiết bản ghi `<ID>` tự mở | | |
| VL-17 | Đang ở trang 1, sắp xếp mới nhất, không mở dialog | Desktop gửi vi phạm mới | Danh sách tự thêm bản ghi mới | | |
| VL-18 | Đang ở trang 2 hoặc đang mở dialog | Desktop gửi vi phạm mới | Hiện thanh thông báo kèm nút “Tải lại danh sách”; bấm → tải lại | | |
| VL-19 | Có bản ghi + có `CT_VIPHAM` thủ công | “Xóa toàn bộ bản ghi phát hiện” → “Xóa toàn bộ” | Hộp xác nhận màu đỏ; sau khi xóa: bảng trống, `PHAT_HIEN_VIPHAM`/`ANH_VIPHAM` trống, file ảnh bị xóa; **`CT_VIPHAM` vẫn còn** | | |
| VL-20 | | Bấm “Hủy” ở hộp xác nhận VL-19 | Không xóa gì | | |

---

## 11. Thông báo realtime (chuông trên header)

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| NT-01 | Có bản ghi `MOI` | Mở bất kỳ trang nào | Chuông có badge = số vi phạm Mới | | |
| NT-02 | | Desktop gửi vi phạm mới | Trong ~5s: badge tăng, chuông rung, toast “Có N vi phạm mới từ camera.” | | |
| NT-03 | | Bấm chuông | Panel “Vi phạm chưa xử lý” liệt kê tối đa 10 bản ghi + “Xem tất cả vi phạm mới” | | |
| NT-04 | | Bấm 1 mục trong panel | Mở `list_vipham.php?status=MOI&id=...` với dialog chi tiết | | |
| NT-05 | | Xử lý bản ghi (VL-11) | Badge giảm ngay | | |
| NT-06 | > 99 bản ghi Mới (chèn SQL hoặc curl hàng loạt) | Xem badge | Hiển thị “99+” | | |
| NT-07 | Mở 2 tab | Gửi 1 vi phạm mới | Chỉ 1 tab hiện toast (không trùng) | | |
| NT-08 | | Chuyển sang tab khác rồi gửi vi phạm | Tiêu đề tab ẩn có tiền tố “(n)”; quay lại tab thì bỏ tiền tố | | |
| NT-09 | | Tắt API khi đang mở web | Không spam toast lỗi; tần suất polling giãn dần (DevTools Network, tối đa ~60s); bật lại API → tự phục hồi | | |
| NT-10 | Đang ở `home.php` | Gửi vi phạm mới | Thẻ đếm và biểu đồ tự làm mới | | |

---

## 12. Tổng quan & Thống kê/Báo cáo

### 12.1 `home.php`

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| DB-01 | Có dữ liệu nhiều ngày | Mở trang | Thẻ Hôm nay / Tuần này / Tháng này / Học kỳ hiện tại khớp SQL đếm `PHAT_HIEN_VIPHAM` | | |
| DB-02 | | “Kỳ thống kê” mặc định | “Theo tháng” | | |
| DB-03 | | Lần lượt chọn Theo ngày / tuần / tháng / học kỳ / Khoảng tùy chọn → “Áp dụng bộ lọc” | Trường ngày hiển thị phù hợp từng kỳ; biểu đồ cập nhật | | |
| DB-04 | | Khoảng tùy chọn > 400 ngày | Bị từ chối | | |
| DB-05 | | Lọc “Khu vực” | Mọi biểu đồ/bảng chỉ tính khu vực đó | | |
| DB-06 | | Kiểm tra 4 biểu đồ: Vi phạm theo ngày, Theo loại lỗi (doughnut), Khung giờ, Theo khu vực | Hiển thị đúng, tooltip đúng số | | |
| DB-07 | | Bảng “Khu vực & khung giờ nhiều vi phạm”, “Thống kê theo loại lỗi” | Số và % đúng (tổng % = 100) | | |
| DB-08 | DB trống | Mở trang | Trạng thái rỗng, không lỗi JS | | |

### 12.2 `reporting.php`

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| RP-01 | | Mở trang | Kỳ mặc định “Theo tuần”; KPI: Tổng số vi phạm, Khung giờ cao điểm, Khu vực nhiều nhất, Đã xử lý / bỏ qua | | |
| RP-02 | | Lọc thêm Camera, Loại vi phạm | KPI, biểu đồ, bảng (hotspot, Theo khu vực, Theo thứ trong tuần) cập nhật đúng | | |
| RP-03 | | “Xuất Excel” | Tải `Bao_cao_Giam_sat_Tac_phong_Sinh_vien_(dd_mm-dd_mm_yyyy).xlsx`; số liệu khớp màn hình và bộ lọc | | |
| RP-04 | Có `GEMINI_API_KEY` | “Xuất PDF” | PDF có tiếng Việt đúng font, có phần phân tích AI | | |
| RP-05 | Không có / sai `GEMINI_API_KEY` | “Xuất PDF” | Vẫn xuất được, phần phân tích dùng nội dung mặc định | | |
| RP-06 | | Xuất khi không có dữ liệu trong kỳ | File vẫn tạo được, ghi rõ không có dữ liệu | | |
| RP-07 | Chưa đăng nhập | `POST /api/v1/reports/ai-pdf` bằng curl | 401 `[KI-6]` | | |

---

## 13. Retention (tự xóa dữ liệu)

Chạy thủ công: `cd Final_System; python -m api.retention --once`

| ID | Tiền điều kiện | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| RT-01 | Bản ghi `MOI` có ảnh | `UPDATE ANH_VIPHAM SET THOI_GIAN_HET_HAN = DATEADD(day,-1,GETDATE()) WHERE PHAT_HIEN_ID=<ID>` → chạy retention | File ảnh bị xóa, `ANH_KHOA = NULL`, **bản ghi vẫn còn** | | |
| RT-02 | Sau RT-01 | Xem trên `list_vipham.php` | Badge ảnh “Hết hạn” / “Đã xóa (30 ngày)”; chi tiết không hiện ảnh | | |
| RT-03 | Sau RT-01 | `GET /api/v1/violations/<ID>/evidence` | 410 | | |
| RT-04 | Xóa tay file ảnh trong `CIM_EVIDENCE_DIR` (DB vẫn còn key) | Mở chi tiết | 410, badge “Mất tệp” | | |
| RT-05 | Bản ghi `DA_XU_LY` | `UPDATE PHAT_HIEN_VIPHAM SET XU_LY_LUC = DATEADD(hour,-25,GETDATE()) WHERE ID=<ID>` → chạy retention | Bản ghi + ảnh bị xóa | | |
| RT-06 | Tương tự RT-05 với `BO_QUA` và `DA_XEM` | Chạy retention | Bị xóa `[KI-7]` | | |
| RT-07 | Bản ghi `MOI` cũ | Chạy retention | **Không** bị xóa | | |
| RT-08 | Bản ghi `DA_XU_LY` mới xử lý < 24h | Chạy retention | Không bị xóa | | |
| RT-09 | `CIM_RETENTION_INTERVAL_MINUTES=1`, restart API | Chờ 1–2 phút | Job tự chạy (xem log API) | | |
| RT-10 | Đang chạy retention | Bấm “Xóa toàn bộ bản ghi phát hiện” cùng lúc | Có thể nhận 409 `purge_in_progress`, không lỗi dữ liệu | | |
| RT-11 | | Sau retention, kiểm tra `CT_VIPHAM` | Không bị ảnh hưởng | | |
| RT-12 | Ghi lại số liệu Dashboard + Báo cáo tháng | Chạy retention (hoặc “Xóa toàn bộ bản ghi phát hiện”) | Tổng, theo loại, theo giờ, theo khu vực, theo trạng thái **không đổi**; `THONG_KE_PHAT_HIEN` có dòng mới; file xuất có ghi chú “Số liệu tổng gồm cả bản ghi đã được dọn” | | |

---

## 14. Luồng End-to-End tổng hợp

Chạy một lượt từ DB sạch (đã migrate + có admin). Ghi lại thời gian từng bước.

| Bước | Vai trò | Thao tác | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|---|
| E2E-01 | ADMIN | Đăng nhập web, tạo tài khoản `ctsv01` | Thành công | | |
| E2E-02 | ADMIN | Tạo/kiểm tra khu vực “Cổng chính”, camera `CAM-01` active, học kỳ chứa hôm nay | Dữ liệu sẵn sàng | | |
| E2E-03 | ADMIN/CTSV | Tạo Khoa/Ngành/Lớp, import 5 sinh viên bằng Excel | Hiện trong `ql_sinhvien.php` | | |
| E2E-04 | Desktop | Cấu hình API `http://127.0.0.1:8001`, mã `CAM-01` → “KIỂM TRA KẾT NỐI API” | Kết nối OK | | |
| E2E-05 | Desktop | Chạy video có 3 loại vi phạm (b), (c), (d) và 1 người tuân thủ (a) | Desktop cảnh báo đúng 3 vi phạm, không cảnh báo người (a) | | |
| E2E-06 | DB | Chạy SQL 0.3 | 3 bản ghi `MOI` đúng loại, có ảnh, đúng camera | | |
| E2E-07 | CTSV | Đăng nhập web | Chuông hiện 3; toast vi phạm mới | | |
| E2E-08 | CTSV | `list_vipham.php`: xem ảnh từng bản ghi; đặt 1 bản ghi “Đã xử lý”, 1 “Bỏ qua”, giữ 1 “Mới” | Badge chuông còn 1 | | |
| E2E-09 | CTSV | Từ ảnh bằng chứng, xác định sinh viên → `ql_sinhvien.php` ghi nhận vi phạm thủ công cho SV đó | Cột “Vi phạm tác phong” của SV tăng | | |
| E2E-10 | CTSV | `home.php` và `reporting.php` với kỳ “Theo ngày” hôm nay | Tổng = 3; theo loại, khu vực, khung giờ, “Đã xử lý / bỏ qua” = 2 | | |
| E2E-11 | CTSV | Xuất Excel + PDF | Số liệu khớp bước 10 | | |
| E2E-12 | Hệ thống | Lùi `XU_LY_LUC` 25h cho 2 bản ghi đã xử lý → chạy retention | 2 bản ghi bị xóa; bản ghi `MOI` còn; `CT_VIPHAM` ở bước 9 còn | | |
| E2E-13 | Desktop | Tắt API → tạo vi phạm → bật API | Vi phạm đến web sau khi API bật (outbox) | | |
| E2E-14 | CTSV | Đăng xuất | Về login | | |

---

## 15. Bảo mật & phi chức năng

| ID | Các bước | Kết quả mong đợi | Thực tế | KQ |
|---|---|---|---|---|
| NF-01 | Nhập `<img src=x onerror=alert(1)>` vào tên khoa, tên SV, họ tên tài khoản, ghi chú vi phạm | Hiển thị dạng text ở mọi trang, không thực thi | | |
| NF-02 | Nhập `' OR 1=1 --` vào ô tìm kiếm SV, ô đăng nhập | Không lỗi SQL, không lộ dữ liệu | | |
| NF-03 | DevTools: xem request/response của trình duyệt | Token API không xuất hiện ở phía trình duyệt (chỉ có cookie session) | | |
| NF-04 | Gửi body > 64KB qua `api/cim.php` | Bị từ chối | | |
| NF-05 | Query param > 100 ký tự qua `api/cim.php` | Bị từ chối | | |
| NF-06 | Gọi `api/cim.php` với route không có trong whitelist | Bị từ chối | | |
| NF-07 | Xuất Excel báo cáo/sinh viên có dữ liệu bắt đầu bằng `=`, `+`, `-`, `@` | Mở bằng Excel không thực thi công thức | | |
| NF-08 | Mở các trang ở độ rộng 375px (DevTools mobile) | Không vỡ layout, không cuộn ngang, menu dùng được | | |
| NF-09 | Chèn ~1000 bản ghi vi phạm (curl vòng lặp) → mở list, dashboard, báo cáo | Thời gian tải < 3s, phân trang mượt | | |
| NF-10 | Chrome, Edge, Firefox bản mới | Chức năng chính hoạt động như nhau | | |
| NF-11 | Restart API khi web đang mở | Web báo lỗi kết nối thân thiện, tự phục hồi khi API lên lại | | |
| NF-12 | Desktop chạy video liên tục 30 phút | Không rò bộ nhớ đáng kể, FPS ổn định, không crash | | |

---

## Phụ lục A – Lỗi đã biết (Known Issues)

Các lỗi dưới đây được xác định qua đọc code tại thời điểm viết tài liệu. Khi test, ghi nhận để xác nhận; không tính là phát hiện mới.

| Mã | Mô tả | Ảnh hưởng tới case |
|---|---|---|
| KI-1 | Form camera trên web gửi trường `stream_source`, API (`CameraRequest`) cấm trường lạ → tạo/sửa camera trả **422** | FA-04, FA-05 |
| KI-2 | “Cấp lại khóa” gọi `/cameras/{id}/rotate-key` không tồn tại (404); tạo camera không trả `device_key` nên dialog khóa không hiện | FA-06 |
| KI-3 | API chỉ xác thực thiết bị bằng `X-Camera-Code`, không kiểm tra khóa thiết bị | IN-09 (chỉ cần biết mã camera là gửi được) |
| KI-4 | `uniform_confidence`/`card_confidence` không lưu; ghi chú xử lý và “Người xử lý” không lưu → hiển thị “—” | IN-18, VL-12 |
| KI-5 | `/api/v1/health` không kiểm tra kết nối DB | SM-03 |
| KI-6 | `POST /api/v1/reports/ai-pdf` không yêu cầu đăng nhập | RP-07 |
| KI-7 | Đặt “Đã xem” cũng bắt đầu đồng hồ 24h → bản ghi bị xóa dù chưa xử lý thực sự | RT-06 |
| KI-8 | README/docs ghi port API 8000; scripts/Desktop/Docker dùng 8001 | 0.1 |
| KI-9 | Ảnh 3x4, Ngày sinh, Giới tính, Email, SĐT của sinh viên chỉ có trên UI, không lưu DB | ST-07 |
| KI-10 | Cột “Kết nối gần nhất” luôn “Chưa kết nối” (cột đã bị bỏ ở migration 006) | FA-07 |
| KI-11 | `python -m api.migrate --init-base` tìm `SQL_Querysql.sql` ở thư mục gốc, file thực tế ở `documents/` | 0.1 (DB mới hoàn toàn) |

---

## Phụ lục B – Tổng hợp kết quả

| Module | Số case | Pass | Fail | Blocked | N/A | Ghi chú |
|---|---|---|---|---|---|---|
| 1. Smoke | 7 | | | | | |
| 2. Auth + đổi mật khẩu | 20 | | | | | |
| 3. Phân quyền | 12 | | | | | |
| 4. Tài khoản | 12 | | | | | |
| 5. Danh mục | 12 | | | | | |
| 6. Camera/khu vực/học kỳ | 14 | | | | | |
| 7. Sinh viên + import | 26 | | | | | |
| 8. Desktop AI | 22 | | | | | |
| 9. Ingest & outbox | 18 | | | | | |
| 10. Bản ghi vi phạm | 20 | | | | | |
| 11. Thông báo | 10 | | | | | |
| 12. Dashboard & báo cáo | 15 | | | | | |
| 13. Retention | 11 | | | | | |
| 14. E2E | 14 | | | | | |
| 15. Bảo mật & phi chức năng | 12 | | | | | |
| **Tổng** | **225** | | | | | |

**Danh sách lỗi phát hiện mới**

| # | Case ID | Mô tả lỗi | Bước tái hiện | Mức độ (Cao/TB/Thấp) | Ảnh chụp | Trạng thái |
|---|---|---|---|---|---|---|
| 1 | | | | | | |
| 2 | | | | | | |
