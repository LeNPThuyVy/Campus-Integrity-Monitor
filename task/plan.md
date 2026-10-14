# Kế Hoạch Giải Quyết Conflict Merge Nhánh Website (Commit 1e9b605 vào thuy)

## Mục tiêu (Goal Description)
Hiện tại dự án đang trong quá trình merge commit `1e9b605` (`Update website features` của bạn Hào) vào nhánh `thuy` (sau khi đã tích hợp thành công nhánh `Qui`).
Có **16 file** trong thư mục [`Website/ui/`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/) đang rơi vào trạng thái conflict (`both added`).

Kế hoạch này phân tích nguồn gốc của sự khác biệt giữa hai nhánh, so sánh chi tiết từng nhóm file và đề xuất chiến lược giải quyết chuẩn xác nhất để không làm hỏng tính năng đã phát triển của toàn hệ thống.

---

## 1. Nguồn Gốc và Bản Chất Của Conflict

| Nhánh / Phiên bản | Nguồn gốc | Đặc điểm mã nguồn |
|---|---|---|
| **Nhánh `thuy` (HEAD - Stage 2)** | Đã tích hợp nhánh của Qui (`427b9d5`) | **Phiên bản hoàn thiện và tích hợp đầy đủ:**<br>• Đã kết nối với FastAPI Server v2.0 và SQL Server.<br>• Có hệ thống xác thực JWT (`auth.php`), phân quyền RBAC (`ADMIN`, `USER`).<br>• Có giao diện hoàn chỉnh cho **Home Dashboard, Danh sách vi phạm, Quản lý tài khoản, Báo cáo & Biểu đồ**.<br>• Phân hệ Sinh viên đã liên kết trực tiếp với vi phạm AI (`student_violation_add/list/delete`).<br>• Phân hệ Danh mục đã đồng bộ `MA_LOI` và `DANG_SU_DUNG` với mô hình AI. |
| **Commit `1e9b605` (Stage 3 - Hào)** | Rẽ nhánh từ commit cũ `2aaf4ae` (ngày 26/09/2026) | **Bản nháp độc lập trước khi có backend:**<br>• Lúc Hào làm, hệ thống chưa có API, chưa có bảng tài khoản và chưa có cơ chế xác thực.<br>• Do đó, Hào đặt mã lỗi **HTTP 503** (`website_render_unavailable`) cho 4 trang chính: `home.php`, `list_vipham.php`, `phanquyen.php`, `reporting.php`.<br>• Dùng trực tiếp `getenv()` thay vì `includes/env.php`.<br>• Chưa có liên kết giữa Sinh viên và bảng Vi phạm. |

---

## 2. Chi Tiết Phân Tích Từng Nhóm File & Đề Xuất Giữ Lại

### Nhóm 1: Các trang chính bị Hào đặt HTTP 503 tạm thời (4 files)
- [`Website/ui/home.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/home.php)
- [`Website/ui/list_vipham.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/list_vipham.php)
- [`Website/ui/phanquyen.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/phanquyen.php)
- [`Website/ui/reporting.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/reporting.php)

> [!IMPORTANT]
> **QUYẾT ĐỊNH: GIỮ NGUYÊN BẢN CỦA HEAD (Stage 2).**
> - **Lý do (Strategy):** Bản của Hào chỉ có 12 dòng hiển thị thông báo "chưa khả dụng do chưa có API". Bản của HEAD là bản đã code hoàn chỉnh giao diện dashboard, bộ lọc theo ngày/tuần/tháng/học kỳ, bảng vi phạm, quản lý camera, biểu đồ Chart.js và xuất file Word/PDF/CSV.
> - **Ảnh hưởng:** Giữ được toàn bộ tính năng cốt lõi của website. Nếu lấy bản của Hào, website sẽ bị quay ngược về trạng thái báo lỗi 503.

---

### Nhóm 2: Khung giao diện Header, Footer và Xác thực (4 files)
- [`Website/ui/includes/header.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/includes/header.php)
- [`Website/ui/includes/footer.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/includes/footer.php)
- [`Website/ui/login.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/login.php)
- [`Website/ui/logout.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/logout.php)

> [!IMPORTANT]
> **QUYẾT ĐỊNH: GIỮ NGUYÊN BẢN CỦA HEAD (Stage 2).**
> - **Lý do (Strategy):** 
>   - `header.php` ở HEAD tích hợp menu tài khoản (hiển thị tên, vai trò Admin/Giám thị, link đổi mật khẩu, form logout CSRF). Bản của Hào các nút bấm bị disable hoặc trỏ cứng.
>   - `footer.php` ở HEAD có vùng hiển thị thông báo toast, dialog xác nhận (`confirm-dialog`) và nạp `common.js`.
>   - `login.php` và `logout.php` ở HEAD kết nối trực tiếp với API JWT backend, có chống brute-force và phân quyền.
> - **Ảnh hưởng:** Đảm bảo luồng đăng nhập, bảo mật và trải nghiệm người dùng liền mạch.

---

### Nhóm 3: Phân hệ Quản lý Sinh viên & Danh mục (8 files)
- [`Website/ui/includes/catalog_module.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/includes/catalog_module.php)
- [`Website/ui/includes/student_module.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/includes/student_module.php)
- [`Website/ui/api/catalogs.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/api/catalogs.php)
- [`Website/ui/api/students.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/api/students.php)
- [`Website/ui/assets/js/catalogs.js`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/assets/js/catalogs.js)
- [`Website/ui/assets/js/students.js`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/assets/js/students.js)
- [`Website/ui/danh_muc.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/danh_muc.php)
- [`Website/ui/ql_sinhvien.php`](file:///t:/OneDrive/Backup/My_Document/GRADUATION_PROJECT_GITHUB_MANAGAMENT/Campus-Integrity-Monitor/Website/ui/ql_sinhvien.php)

> [!IMPORTANT]
> **QUYẾT ĐỊNH: GIỮ NGUYÊN BẢN CỦA HEAD (Stage 2).**
> - **Lý do (Strategy):**
>   - Bản ở HEAD thực chất đã **kế thừa toàn bộ logic nghiệp vụ ban đầu của Hào** nhưng đã được hoàn thiện thêm:
>     1. Đọc cấu hình kết nối DB qua `env.php` (dùng chung file `.env` thống nhất với API).
>     2. Kiểm tra quyền Admin khi thay đổi loại vi phạm `LOI_VIPHAM`.
>     3. Bổ sung trường `MA_LOI` và `DANG_SU_DUNG` vào danh mục lỗi để ăn khớp với mã lỗi AI từ Camera.
>     4. Tích hợp `student_violation_add`, `student_violation_list`, `student_violation_delete` để khi xem chi tiết 1 sinh viên, giám thị xem và gán được lịch sử vi phạm trực tiếp trên giao diện (`window.CIMStudentViolations.show`).
>     5. Thêm bảo vệ trang `cim_require_page()` ngăn chặn truy cập trái phép khi chưa đăng nhập.
> - **Ảnh hưởng:** Hệ thống vừa giữ được đầy đủ tính năng CRUD sinh viên / danh mục / import Excel mà Hào viết, vừa tương thích 100% với toàn bộ phần còn lại của dự án.

---

## 3. Các File Mới Được Thêm Vào Từ Nhánh Của Hào (Đã Tự Động Merge Thành Công)
Các đóng góp độc lập của Hào không bị conflict và được tự động giữ lại trong repo:
- `SQL_Querysql.sql`: Các câu lệnh truy vấn mẫu.
- `Website/tests/catalog_module_test.php`: Unit test cho catalog module.
- `Website/tests/student_module_test.php`: Unit test cho student module.
- `Website/ui/student-import-guide.md`: Hướng dẫn import sinh viên.

---

## 4. Kế Hoạch Thực Hiện (Execution Plan)

1. **Checkout giữ phiên bản của HEAD (ours) cho 16 file conflict**:
   ```powershell
   git checkout --ours Website/ui/api/catalogs.php Website/ui/api/students.php Website/ui/assets/js/catalogs.js Website/ui/assets/js/students.js Website/ui/danh_muc.php Website/ui/home.php Website/ui/includes/catalog_module.php Website/ui/includes/footer.php Website/ui/includes/header.php Website/ui/includes/student_module.php Website/ui/list_vipham.php Website/ui/login.php Website/ui/logout.php Website/ui/phanquyen.php Website/ui/ql_sinhvien.php Website/ui/reporting.php
   ```
2. **Đưa toàn bộ 16 file vào staging**:
   ```powershell
   git add Website/ui/
   ```
3. **Kiểm tra xác minh**:
   - Chạy `git status` xác nhận không còn file nào unmerged.
   - Chạy bộ test Python: `py -3.12 -m pytest Final_System/test/reporting/test_prompt_builder.py` để đảm bảo hệ thống không bị ảnh hưởng.
   - Dọn dẹp các script phân tích tạm thời trong `task/`.
