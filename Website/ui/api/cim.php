<?php
declare(strict_types=1);

/*
 * Web -> API proxy for dashboard pages.
 * - Only whitelisted routes/methods/query keys are forwarded to the FastAPI backend.
 * - The API access token stays in the PHP session; the browser never sees it.
 * - State-changing requests require the CSRF token; roles are checked here AND by the API.
 */
require_once dirname(__DIR__) . '/includes/auth.php';

const CIM_PROXY_MAX_BODY = 65536;

$user = cim_require_api_user();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$route = (string)($_GET['r'] ?? '');
$id = $_GET['id'] ?? null;

$ADMIN = ['ADMIN'];
$ALL = ['ADMIN', 'CTSV'];
$listQuery = ['date_from', 'date_to', 'area_id', 'camera_id', 'type_id', 'status', 'page', 'page_size', 'sort'];
$reportQuery = ['period', 'anchor', 'month', 'semester_id', 'date_from', 'date_to', 'area_id', 'camera_id', 'type_id'];

// route => [method => [api path template, roles, allowed query keys, binary response?]]
$routes = [
    'health' => ['GET' => ['/api/v1/health', $ALL, [], false]],
    'dashboard' => ['GET' => ['/api/v1/dashboard', $ALL, $reportQuery, false]],
    'violation-types' => ['GET' => ['/api/v1/violation-types', $ALL, [], false]],
    'violations' => ['GET' => ['/api/v1/violations', $ALL, $listQuery, false]],
    'violation' => [
        'GET' => ['/api/v1/violations/{id}', $ALL, [], false],
        'PATCH' => ['/api/v1/violations/{id}', $ALL, [], false],
    ],
    'evidence' => ['GET' => ['/api/v1/violations/{id}/evidence', $ALL, [], true]],
    'report-summary' => ['GET' => ['/api/v1/reports/summary', $ALL, $reportQuery, false]],
    'report-export' => ['GET' => ['/api/v1/reports/export', $ALL, array_merge($reportQuery, ['format']), true]],
    'areas' => [
        'GET' => ['/api/v1/areas', $ALL, ['include_inactive'], false],
        'POST' => ['/api/v1/areas', $ADMIN, [], false],
    ],
    'area' => ['PUT' => ['/api/v1/areas/{id}', $ADMIN, [], false]],
    'cameras' => [
        'GET' => ['/api/v1/cameras', $ALL, ['area_id'], false],
        'POST' => ['/api/v1/cameras', $ADMIN, [], false],
    ],
    'camera' => ['PUT' => ['/api/v1/cameras/{id}', $ADMIN, [], false]],
    'camera-key' => ['POST' => ['/api/v1/cameras/{id}/rotate-key', $ADMIN, [], false]],
    'semesters' => [
        'GET' => ['/api/v1/semesters', $ALL, [], false],
        'POST' => ['/api/v1/semesters', $ADMIN, [], false],
    ],
    'semester' => [
        'PUT' => ['/api/v1/semesters/{id}', $ADMIN, [], false],
        'DELETE' => ['/api/v1/semesters/{id}', $ADMIN, [], false],
    ],
    'accounts' => [
        'GET' => ['/api/v1/accounts', $ADMIN, [], false],
        'POST' => ['/api/v1/accounts', $ADMIN, [], false],
    ],
    'account' => ['PUT' => ['/api/v1/accounts/{id}', $ADMIN, [], false]],
    'account-password' => ['POST' => ['/api/v1/accounts/{id}/reset-password', $ADMIN, [], false]],
];

if (!isset($routes[$route])) {
    student_json_error('Chức năng không tồn tại.', 404);
}
if (!isset($routes[$route][$method])) {
    header('Allow: ' . implode(', ', array_keys($routes[$route])));
    student_json_error('Phương thức HTTP không được hỗ trợ.', 405);
}
[$template, $roles, $allowedQuery, $binary] = $routes[$route][$method];
if (!in_array($user['role'], $roles, true)) {
    student_json_error('Bạn không có quyền thực hiện thao tác này.', 403);
}

$path = $template;
if (str_contains($template, '{id}')) {
    $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($numericId === false) {
        student_json_error('Mã bản ghi không hợp lệ.', 422);
    }
    $path = str_replace('{id}', (string)$numericId, $template);
}

$query = [];
foreach ($allowedQuery as $key) {
    if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
        if (strlen($_GET[$key]) > 100) {
            student_json_error('Tham số truy vấn quá dài.', 422);
        }
        $query[$key] = $_GET[$key];
    }
}

$body = null;
if ($method !== 'GET') {
    student_verify_csrf();
    if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            if (strlen($raw) > CIM_PROXY_MAX_BODY) {
                student_json_error('Nội dung yêu cầu quá lớn.', 413);
            }
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                student_json_error('Nội dung JSON không hợp lệ.', 400);
            }
            $body = $decoded;
        } else {
            $body = [];
        }
    }
}

$response = cim_api_request($method, $path, $query, $body, cim_api_token(), $binary ? 60 : 30);

if ($response['status'] === 401) {
    // Token expired or revoked on the API side (password changed, account disabled...).
    cim_clear_auth();
    student_json_error('Phiên đăng nhập đã hết hạn hoặc bị thu hồi. Vui lòng đăng nhập lại.', 401);
}

http_response_code($response['status']);
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
$contentType = $response['headers']['content-type'] ?? 'application/json';
$isJson = str_starts_with($contentType, 'application/json');
if ($binary && !$isJson) {
    $allowedTypes = ['image/jpeg', 'application/pdf',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $baseType = trim(explode(';', $contentType)[0]);
    if (!in_array($baseType, $allowedTypes, true)) {
        student_json_error('Định dạng phản hồi không được hỗ trợ.', 502);
    }
    header('Content-Type: ' . $baseType);
    if (isset($response['headers']['content-disposition'])
        && preg_match('/^(inline|attachment); filename="[A-Za-z0-9._-]+"$/', $response['headers']['content-disposition']) === 1) {
        header('Content-Disposition: ' . $response['headers']['content-disposition']);
    }
    header('Content-Length: ' . strlen($response['body']));
    echo $response['body'];
    exit;
}
header('Content-Type: application/json; charset=utf-8');
echo $response['body'];
