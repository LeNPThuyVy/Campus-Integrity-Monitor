<?php
declare(strict_types=1);

require_once __DIR__ . '/student_module.php';
require_once __DIR__ . '/api_client.php';

/*
 * Web authentication.
 * - Credentials are verified by the API (hashed with scrypt in SQL Server); the Web never stores passwords.
 * - The API access token lives only in the server-side PHP session, never in the browser.
 * - A session ends at the token expiry or after CIM_WEB_SESSION_IDLE_MINUTES of inactivity.
 */

const CIM_ROLES = ['ADMIN', 'CTSV'];

function cim_idle_seconds(): int
{
    $minutes = (int)(cim_env('CIM_WEB_SESSION_IDLE_MINUTES', '30') ?? '30');
    return max(5, $minutes) * 60;
}

function cim_clear_auth(): void
{
    foreach (['authenticated', 'role', 'username', 'display_name', 'user_id', 'api_token', 'token_expires_at',
              'last_activity', 'student_import_previews'] as $key) {
        unset($_SESSION[$key]);
    }
}

/** Current user or null. Expired/idle sessions are cleared here. */
function cim_current_user(): ?array
{
    student_start_session();
    if (($_SESSION['authenticated'] ?? false) !== true || !in_array($_SESSION['role'] ?? null, CIM_ROLES, true)) {
        return null;
    }
    $now = time();
    $expiresAt = (int)($_SESSION['token_expires_at'] ?? 0);
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($expiresAt <= $now || $lastActivity + cim_idle_seconds() < $now) {
        cim_clear_auth();
        $_SESSION['auth_notice'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        return null;
    }
    $_SESSION['last_activity'] = $now;
    return [
        'id' => (int)($_SESSION['user_id'] ?? 0),
        'username' => (string)($_SESSION['username'] ?? ''),
        'display_name' => (string)($_SESSION['display_name'] ?? ''),
        'role' => (string)$_SESSION['role'],
    ];
}

function cim_api_token(): ?string
{
    $token = $_SESSION['api_token'] ?? null;
    return is_string($token) && $token !== '' ? $token : null;
}

function cim_safe_next(?string $next): string
{
    if (!is_string($next) || preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%-]*)?$/', $next) !== 1 || $next === 'login.php') {
        return 'home.php';
    }
    return $next;
}

/** Guard for HTML pages: redirect to login, or show 403 when the role is not allowed. */
function cim_require_page(array $roles = CIM_ROLES): array
{
    $user = cim_current_user();
    if ($user === null) {
        $page = basename($_SERVER['SCRIPT_NAME'] ?? 'home.php');
        header('Location: login.php?next=' . rawurlencode($page), true, 302);
        exit;
    }
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        $GLOBALS['cim_forbidden'] = true;
    }
    return $user;
}

/** Guard for JSON endpoints. */
function cim_require_api_user(array $roles = CIM_ROLES): array
{
    $user = cim_current_user();
    if ($user === null) {
        student_json_error('Phiên đăng nhập đã hết hạn hoặc chưa đăng nhập.', 401);
    }
    if (!in_array($user['role'], $roles, true)) {
        student_json_error('Bạn không có quyền thực hiện thao tác này.', 403);
    }
    return $user;
}

/** @return array{ok:bool, message:string} */
function cim_login(string $username, string $password): array
{
    [$status, $body] = cim_api_json('POST', '/api/v1/auth/login', [], [
        'username' => $username,
        'password' => $password,
    ]);
    if ($status !== 200 || !isset($body['data']['access_token'], $body['data']['user'])) {
        return ['ok' => false, 'message' => cim_api_error_message($body, 'Không thể đăng nhập lúc này. Vui lòng thử lại.')];
    }
    $user = $body['data']['user'];
    if (!in_array($user['role'] ?? null, CIM_ROLES, true)) {
        return ['ok' => false, 'message' => 'Tài khoản không có vai trò hợp lệ.'];
    }
    student_start_session();
    session_regenerate_id(true); // prevent session fixation
    cim_clear_auth();
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['username'] = (string)$user['username'];
    $_SESSION['display_name'] = (string)$user['full_name'];
    $_SESSION['api_token'] = (string)$body['data']['access_token'];
    $_SESSION['token_expires_at'] = (int)$body['data']['expires_at'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['student_csrf_token']);
    return ['ok' => true, 'message' => ''];
}

function cim_logout(): void
{
    student_start_session();
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    if (ini_get('session.use_cookies')) {
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}

function cim_verify_form_csrf(): bool
{
    $provided = $_POST['csrf_token'] ?? '';
    return is_string($provided) && $provided !== '' && hash_equals(student_csrf_token(), $provided);
}

function cim_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
