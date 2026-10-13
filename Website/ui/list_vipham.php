<?php
declare(strict_types=1);

http_response_code(503);
header('Cache-Control: no-store');
include 'includes/header.php';
require_once 'includes/website_status.php';
website_render_unavailable(
    'Danh sách vi phạm chưa khả dụng',
    'Website chưa được cấu hình kết nối có xác thực tới API sự kiện và dịch vụ ảnh. Hiện không có bản ghi thật để tra cứu.'
);
include 'includes/footer.php';
