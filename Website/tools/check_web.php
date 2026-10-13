<?php
declare(strict_types=1);

/*
 * Web environment check (run from the command line, never exposed through the web server):
 *   php Website/tools/check_web.php
 * Checks PHP version/extensions (pdo_sqlsrv...), configuration variables (secret values are not
 * printed), the SQL Server connection used by the student/catalog pages, migration 001 and the API.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/ui/includes/student_module.php';
require_once dirname(__DIR__) . '/ui/includes/api_client.php';

$healthy = true;
function check_line(?bool $ok, string $label, string $detail = ''): void
{
    global $healthy;
    $mark = $ok === null ? '[ -- ]' : ($ok ? '[ OK ]' : '[LỖI]');
    echo $mark . ' ' . $label . ($detail !== '' ? ': ' . $detail : '') . PHP_EOL;
    if ($ok === false) {
        $healthy = false;
    }
}

check_line(PHP_VERSION_ID >= 80100, 'PHP', PHP_VERSION . ' (cần >= 8.1), ini: ' . (php_ini_loaded_file() ?: 'không có php.ini'));
foreach (['pdo_sqlsrv' => 'kết nối SQL Server', 'mbstring' => 'xử lý tiếng Việt', 'fileinfo' => 'kiểm tra tệp import',
          'zip' => 'đọc/ghi Excel', 'json' => 'API JSON'] as $extension => $purpose) {
    check_line(extension_loaded($extension), "Extension {$extension}", $purpose);
}
check_line(filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN), 'allow_url_fopen', 'cần On để Web gọi API');
check_line(PHP_INT_SIZE === 8 ? true : null, 'Kiến trúc', PHP_INT_SIZE === 8 ? 'x64' : 'x86 (khuyến nghị x64)');

$envFile = getenv('CIM_ENV_FILE') ?: dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
check_line(is_file($envFile) ? true : null, 'File .env', is_file($envFile) ? $envFile : 'không có (dùng biến môi trường hệ thống)');
foreach (['DB_SERVER', 'DB_NAME', 'DB_TRUSTED_CONNECTION', 'DB_USER', 'DB_PASSWORD', 'DB_ENCRYPT',
          'DB_TRUST_SERVER_CERTIFICATE', 'CIM_API_BASE_URL', 'CIM_WEB_SESSION_IDLE_MINUTES'] as $name) {
    $value = cim_env($name);
    $shown = $value === null ? '(chưa đặt)' : ($name === 'DB_PASSWORD' ? '(đã đặt)' : $value);
    echo "       {$name} = {$shown}" . PHP_EOL;
}

if (extension_loaded('pdo_sqlsrv')) {
    try {
        $pdo = student_db();
        $tables = $pdo->query("SELECT name FROM sys.tables WHERE name IN ('KHOA','NGANH','LOP','SINHVIEN','LOI_VIPHAM','CT_VIPHAM')")
            ->fetchAll(PDO::FETCH_COLUMN);
        check_line(count($tables) === 6, 'SQL Server (PDO sqlsrv)', 'kết nối được, bảng gốc ' . count($tables) . '/6');
        check_line(student_violation_schema_ready($pdo), 'Migration 001 (CT_VIPHAM.ID)',
            student_violation_schema_ready($pdo) ? 'đã áp dụng' : 'chưa áp dụng -> python -m api.migrate apply');
        $students = (int)$pdo->query('SELECT COUNT_BIG(*) FROM SINHVIEN')->fetchColumn();
        check_line(null, 'Số sinh viên trong database', (string)$students);
    } catch (Throwable $error) {
        // Do not print the DSN or credentials; the driver message helps diagnose network/login issues.
        $message = preg_replace('/(PWD|Password)=[^;]*/i', '$1=***', $error->getMessage());
        check_line(false, 'SQL Server (PDO sqlsrv)', get_class($error) . ': ' . $message);
    }
} else {
    check_line(false, 'SQL Server (PDO sqlsrv)', 'thiếu extension pdo_sqlsrv, xem docs/HUONG_DAN_WINDOWS.md');
}

$response = cim_api_request('GET', '/api/v1/health', [], null, null, 10);
$health = json_decode($response['body'], true);
$apiOk = $response['status'] === 200 && ($health['status'] ?? '') === 'ok';
$detail = cim_env('CIM_API_BASE_URL', 'http://127.0.0.1:8000') . ' -> HTTP ' . $response['status'];
if (isset($health['error']['message'])) {
    $detail .= ' - ' . $health['error']['message'];
} elseif (is_array($health)) {
    $detail .= ', database: ' . ($health['database']['status'] ?? '?');
    if (isset($health['auth_configured']) && !$health['auth_configured']) {
        $detail .= ', CIM_AUTH_SECRET chưa cấu hình trên API';
    }
}
check_line($apiOk, 'API', $detail);

echo PHP_EOL . ($healthy ? 'Môi trường Web sẵn sàng.' : 'Có mục LỖI, xem hướng dẫn trong docs/HUONG_DAN_WINDOWS.md.') . PHP_EOL;
exit($healthy ? 0 : 1);
