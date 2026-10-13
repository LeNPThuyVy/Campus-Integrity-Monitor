<?php
declare(strict_types=1);

http_response_code(503);
header('Cache-Control: no-store');
include 'includes/header.php';
require_once 'includes/website_status.php';
website_render_unavailable(
    'Tài khoản và phân quyền chưa khả dụng',
    'Schema SQL Server hiện không có bảng tài khoản/vai trò/quyền; API tài khoản hiện có chưa cung cấp RBAC tương thích với Website.'
);
include 'includes/footer.php';
