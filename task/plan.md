## Mô tả mục tiêu (Goal Description)
Cấu trúc lại giao diện (UI) hiện tại của dự án để chuẩn hóa theo mô hình ứng dụng web PHP. Mục tiêu chính là làm cho các tệp giao diện trở nên gọn gàng, chia sẻ chung một Layout (từ `phanquyen.html`) để các lập trình viên backend (Dev) dễ dàng tích hợp, phát triển các chức năng xử lý PHP về sau mà không bị trùng lặp code HTML.

## Phê duyệt từ người dùng (User Review Required)
- Các file `.html` hiện tại sẽ được đổi đuôi thành `.php` để hỗ trợ hàm `include` trong PHP.
- Đối với tệp `login.html` (Trang đăng nhập), thông thường trang này không sử dụng Sidebar (Menu trái) và Header điều hướng như các trang quản lý. Kế hoạch hiện tại là trang đăng nhập vẫn giữ thiết kế riêng biệt của nó nhưng sẽ dùng chung file CSS và cấu hình Tailwind. 
- Các file HTML ngoài `phanquyen.html` (như `home.html`, `ql_sinhvien.html`, ...) sẽ bị cắt bỏ toàn bộ phần Sidebar, Header, Head, và Footer lặp lại, chỉ giữ lại phần nội dung (Main content) và bao bọc bởi `include 'includes/header.php';` và `include 'includes/footer.php';`.

## Câu hỏi mở (Open Questions)
- Mọi trang UI sẽ được để trong `Website/ui/`. Có muốn tạo thêm một file `index.php` ở thư mục gốc (hoặc tự động chuyển hướng về `home.php`) không?
- CSS và script cấu hình Tailwind hiện tại đang nằm trực tiếp trong thẻ `<head>`. Kế hoạch là tách chúng ra thành `assets/css/style.css` và `assets/js/tailwind.config.js`.

## Thay đổi đề xuất (Proposed Changes)
Các file sẽ được phân chia lại theo các Module rõ ràng.

### Khởi tạo thư mục và tài nguyên dùng chung (Assets & Includes)
Tạo cấu trúc thư mục mới để tách biệt CSS, JS và các thành phần Layout:
- Tạo thư mục `Website/ui/assets/css/` và `Website/ui/assets/js/`.
- Tạo thư mục `Website/ui/includes/`.

#### [NEW] Website/ui/assets/css/style.css
Chứa toàn bộ mã CSS nằm trong thẻ `<style>` của các file HTML hiện tại.

#### [NEW] Website/ui/assets/js/tailwind.config.js
Chứa script cấu hình màu sắc, typography của thư viện Tailwind đang được đặt trong thẻ `<script id="tailwind-config">`.

#### [NEW] Website/ui/includes/header.php
Sử dụng layout chuẩn từ `phanquyen.html`. File này sẽ chứa:
- Khai báo `<!DOCTYPE html>`, thẻ `<head>` bao gồm việc nhúng thư viện, font chữ, nhúng `style.css` và `tailwind.config.js`.
- Thẻ mở `<body>`.
- Thành phần `<aside>` (Danh mục điều hướng bên trái).
- Thành phần `<header>` (Thanh điều hướng top nav).
- Thẻ mở `<main>` để chuẩn bị chứa nội dung từ các file con.

#### [NEW] Website/ui/includes/footer.php
Chứa:
- Các thẻ đóng `</main>`, `</div>`, `</body>`, `</html>`.
- Các file JavaScript chung (nếu có).

---

### Chuyển đổi và tinh gọn các trang giao diện (Pages)
Biến đổi các trang hiện tại để loại bỏ mã thừa và kế thừa Layout mới.

#### [MODIFY] Website/ui/phanquyen.html -> [NEW] Website/ui/phanquyen.php
File này sẽ cực kỳ tinh gọn, chỉ còn lại nội dung đặc thù của chức năng phân quyền.

#### [MODIFY] Website/ui/home.html -> [NEW] Website/ui/home.php
#### [MODIFY] Website/ui/list_vipham.html -> [NEW] Website/ui/list_vipham.php
#### [MODIFY] Website/ui/ql_sinhvien.html -> [NEW] Website/ui/ql_sinhvien.php
#### [MODIFY] Website/ui/reporting.html -> [NEW] Website/ui/reporting.php
Cắt bỏ toàn bộ phần cấu hình HTML, `<aside>`, `<header>`. Áp dụng cấu trúc `include` tương tự như file `phanquyen.php`.

#### [MODIFY] Website/ui/login.html -> [NEW] Website/ui/login.php
Sẽ được chuyển sang `.php` và dọn dẹp các mã CSS/JS để tham chiếu đến `assets`. Sẽ không chứa layout chung (header/footer có sidebar) vì là trang đăng nhập.

#### [DELETE] Website/ui/*.html (Sau khi chuyển đổi xong)
Xóa bỏ các file HTML cũ.

## Kế hoạch kiểm thử (Verification Plan)
Để đảm bảo các thay đổi không phá hỏng giao diện hiện tại:

### Kiểm thử thủ công (Manual Verification)
1. Cài đặt và khởi chạy một Local PHP Server.
2. Mở trình duyệt và truy cập: `http://localhost:8000/phanquyen.php`.
3. Kiểm tra xem giao diện có hiển thị giống hệt như bản `.html` trước đó không (Sidebar, màu sắc, font chữ).
4. Nhấp thử vào các file khác như `home.php`, `ql_sinhvien.php` và xác nhận việc kế thừa layout diễn ra chính xác, không bị vỡ bố cục.
5. Kiểm tra `login.php` độc lập.
6. Xác nhận kích thước các tệp con rất nhỏ và gọn gàng.
