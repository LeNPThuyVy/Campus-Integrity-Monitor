<?php
declare(strict_types=1);

http_response_code(503);
header('Cache-Control: no-store');
include 'includes/header.php';
require_once 'includes/website_status.php';
website_render_unavailable(
    'Thống kê và báo cáo chưa khả dụng',
    'API hiện có chưa được kết nối từ Website và không cung cấp đầy đủ bộ lọc học kỳ/khu vực/loại vi phạm cùng xuất Excel/PDF. Báo cáo chưa thể tạo.'
);
include 'includes/footer.php';
