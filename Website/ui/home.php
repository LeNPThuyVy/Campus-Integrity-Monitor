<?php
declare(strict_types=1);

http_response_code(503);
header('Cache-Control: no-store');
include 'includes/header.php';
require_once 'includes/website_status.php';
website_render_unavailable(
    'Tổng quan chưa khả dụng',
    'Website chưa có connector tới API sự kiện hoặc nguồn SQL Server cho các chỉ số này. Không có số liệu mẫu nào được hiển thị.'
);
include 'includes/footer.php';
