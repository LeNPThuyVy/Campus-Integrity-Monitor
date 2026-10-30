<?php

/*
|--------------------------------------------------------------------------
| Campus Integrity Monitor
|--------------------------------------------------------------------------
|
| Every CIM setting is read from .env here so it keeps working after
| `php artisan config:cache` (env() returns null outside config files).
| Passwords have no default on purpose: features fail closed when unset.
|
*/

return [

    'db' => [
        'server' => env('DB_SERVER', env('DB_HOST', 'localhost\\SQLEXPRESS')),
        'name' => env('DB_NAME', env('DB_DATABASE', 'CampusIntegrityMonitor')),
        'trusted' => env('DB_TRUSTED_CONNECTION', true),
        'user' => env('DB_USER', env('DB_USERNAME', '')),
        'password' => env('DB_PASSWORD', ''),
        'encrypt' => env('DB_ENCRYPT', 'yes'),
        'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'yes'),
    ],

    // URL Laravel dùng để gọi FastAPI (server-to-server).
    'api_base_url' => env('CIM_API_BASE_URL', 'http://127.0.0.1:8001'),

    // URL trình duyệt dùng cho SSE; đặt khác api_base_url khi FastAPI không chạy cùng máy người xem.
    'api_public_url' => env('CIM_API_PUBLIC_URL', env('CIM_API_BASE_URL', 'http://127.0.0.1:8001')),

    'session_idle_minutes' => (int) env('CIM_WEB_SESSION_IDLE_MINUTES', 30),

    // Chống dò mật khẩu: dùng chung biến với FastAPI.
    'login_max_failures' => (int) env('CIM_LOGIN_MAX_FAILURES', 5),
    'login_lock_minutes' => (int) env('CIM_LOGIN_LOCK_MINUTES', 15),

    'utc_offset_minutes' => (int) env('CIM_UTC_OFFSET_MINUTES', 420),

    'export_password' => env('CIM_EXPORT_PASSWORD'),

    'reset_password' => env('CIM_RESET_PASSWORD'),

    'r2' => [
        'access_key' => env('CLOUDFLARE_ACCESS_KEY', ''),
        'secret_key' => env('CLOUDFLARE_SECRET_ACCESS_KEY', env('CLOUDFLARE_SECRECT_ACCESS_KEY', '')),
        'endpoint' => env('CLOUDFLARE_S3_API_ENDPOINT', ''),
        'bucket' => env('CLOUDFLARE_R2_BUCKET', 'my-budget-graduate-project'),
        'presign_ttl' => (int) env('CLOUDFLARE_R2_PRESIGN_TTL', 3600),
    ],

];
