<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CimApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CimProxyController extends Controller
{
    public function __construct(
        protected CimApiClient $apiClient
    ) {}

    public function handle(Request $request): Response
    {
        $role = (string)$request->session()->get('role', '');
        $token = (string)$request->session()->get('api_token', '');
        $method = strtoupper($request->method());
        $route = (string)$request->query('r', '');
        $id = $request->query('id');

        // NF-04: Giới hạn kích thước payload body tối đa 64KB (65536 bytes)
        $contentLength = (int)$request->header('Content-Length', 0);
        $rawBody = $request->getContent();
        if ($contentLength > 65536 || strlen($rawBody) > 65536) {
            return response()->json(['error' => 'Kích thước payload vượt quá giới hạn cho phép (tối đa 64KB).'], 413);
        }

        // NF-05: Kiểm tra độ dài query param không vượt quá 100 ký tự (cả tên key, value, và mảng nested)
        $validateQueryLength = function (mixed $value, string $keyName) use (&$validateQueryLength): ?string {
            if (mb_strlen($keyName) > 100) {
                return "Tên tham số '{$keyName}' quá dài (tối đa 100 ký tự).";
            }
            if (is_array($value)) {
                foreach ($value as $subKey => $subVal) {
                    $err = $validateQueryLength($subVal, "{$keyName}.{$subKey}");
                    if ($err !== null) {
                        return $err;
                    }
                }
                return null;
            }
            if (is_string($value) && mb_strlen($value) > 100) {
                return "Tham số '{$keyName}' quá dài (tối đa 100 ký tự).";
            }
            return null;
        };

        foreach ($request->query() as $key => $val) {
            $err = $validateQueryLength($val, (string)$key);
            if ($err !== null) {
                return response()->json(['error' => $err], 422);
            }
        }

        // VL-02: Kiểm tra khoảng ngày hợp lệ
        $dateFromParam = $request->query('date_from');
        $dateToParam = $request->query('date_to');
        if (is_string($dateFromParam) && is_string($dateToParam) && $dateFromParam !== '' && $dateToParam !== '' && $dateFromParam > $dateToParam) {
            return response()->json(['error' => 'Khoảng thời gian không hợp lệ: Từ ngày phải nhỏ hơn hoặc bằng Đến ngày.'], 422);
        }

        // RB-11: Bắt buộc xác thực header X-CSRF-Token cho mọi request thay đổi dữ liệu
        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $csrfHeader = (string)$request->header('X-CSRF-Token', '');
            $sessionToken = (string)$request->session()->token();
            if ($csrfHeader === '' || $sessionToken === '' || !hash_equals($sessionToken, $csrfHeader)) {
                return response()->json(['error' => 'CSRF token không hợp lệ hoặc bị thiếu (HTTP 403 Forbidden).'], 403);
            }
        }

        $ADMIN = ['ADMIN'];
        $ALL = ['ADMIN', 'CTSV'];
        $listQuery = ['date_from', 'date_to', 'area_id', 'camera_id', 'type_id', 'status', 'source', 'page', 'page_size', 'sort'];
        $reportQuery = ['period', 'anchor', 'month', 'semester_id', 'date_from', 'date_to', 'area_id', 'camera_id', 'type_id'];

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
            'area' => [
                'PUT' => ['/api/v1/areas/{id}', $ADMIN, [], false],
                'DELETE' => ['/api/v1/areas/{id}', $ADMIN, [], false],
            ],
            'cameras' => [
                'GET' => ['/api/v1/cameras', $ALL, ['area_id'], false],
                'POST' => ['/api/v1/cameras', $ADMIN, [], false],
            ],
            'camera' => [
                'PUT' => ['/api/v1/cameras/{id}', $ADMIN, [], false],
                'DELETE' => ['/api/v1/cameras/{id}', $ADMIN, [], false],
            ],
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
            'purge-camera-violations' => ['POST' => ['', $ALL, [], false]],
        ];

        if (!isset($routes[$route])) {
            return response()->json(['error' => 'Chức năng không tồn tại.'], 404);
        }

        if (!isset($routes[$route][$method])) {
            return response()->json(['error' => 'Phương thức HTTP không được hỗ trợ.'], 405);
        }

        [$template, $allowedRoles, $allowedQuery, $binary] = $routes[$route][$method];
        if (!in_array($role, $allowedRoles, true)) {
            return response()->json(['error' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        $path = $template;
        if (str_contains($template, '{id}')) {
            $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($numericId === false) {
                return response()->json(['error' => 'Mã bản ghi không hợp lệ.'], 422);
            }
            $path = str_replace('{id}', (string)$numericId, $template);
        }

        if (in_array($route, ['accounts', 'account', 'account-password'], true)) {
            return $this->handleAccounts($request, $route, $method, $id);
        }

        if ($route === 'dashboard') {
            return $this->handleDashboard($request);
        }

        if ($route === 'report-summary') {
            return $this->handleReportSummary($request);
        }

        if ($route === 'report-export') {
            return $this->handleReportExport($request);
        }

        if ($route === 'violation-types') {
            return $this->handleViolationTypes();
        }

        if ($route === 'purge-camera-violations') {
            return $this->handlePurgeCameraViolations();
        }

        if ($route === 'violations') {
            return $this->handleViolations($request);
        }

        if ($route === 'violation') {
            return $this->handleViolationDetail($request, $method, $id);
        }

        if ($route === 'evidence') {
            return $this->handleEvidence($request, $id);
        }

        if ($route === 'areas' || $route === 'area') {
            return $this->handleAreas($request, $method, $id);
        }

        if ($route === 'cameras' || $route === 'camera' || $route === 'camera-key') {
            return $this->handleCameras($request, $method, $id, $route);
        }

        if ($route === 'semesters' || $route === 'semester') {
            return $this->handleSemesters($request, $method, $id);
        }

        if ($route === 'health') {
            return response()->json(['status' => 'healthy', 'version' => '1.0.0']);
        }

        $query = [];
        foreach ($allowedQuery as $key) {
            $val = $request->query($key);
            if (is_string($val) && $val !== '') {
                $query[$key] = $val;
            }
        }

        $body = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $body = $request->isJson() ? $request->json()->all() : $request->all();
        }

        $result = $this->apiClient->request($method, $path, $query, $body, $token);

        if ($binary && $result['status'] === 200) {
            $contentType = $result['headers']['content-type'] ?? 'application/octet-stream';
            $disposition = $result['headers']['content-disposition'] ?? null;
            $headers = ['Content-Type' => $contentType];
            if ($disposition) {
                $headers['Content-Disposition'] = $disposition;
            }
            return response($result['body'], 200, $headers);
        }

        $decoded = json_decode($result['body'], true);
        return response()->json($decoded ?: ['error' => $result['body']], $result['status']);
    }

    protected static ?\PDO $cachedPdo = null;

    protected function getPdo(): \PDO
    {
        if (self::$cachedPdo !== null) {
            try {
                self::$cachedPdo->query('SELECT 1');
                return self::$cachedPdo;
            } catch (\Throwable) {
                self::$cachedPdo = null;
            }
        }

        if (extension_loaded('pdo_sqlsrv')) {
            try {
                $pdo = \Illuminate\Support\Facades\DB::connection('sqlsrv')->getPdo();
                self::$cachedPdo = $pdo;
                return $pdo;
            } catch (\Throwable) {
                // Tiếp tục thử kết nối trực tiếp
            }
        }

        $host = (string)config('cim.db.server');
        $database = (string)config('cim.db.name');
        $trusted = filter_var(config('cim.db.trusted'), FILTER_VALIDATE_BOOLEAN);
        $username = (string)config('cim.db.user');
        $password = (string)config('cim.db.password');

        if (extension_loaded('pdo_odbc')) {
            $odbcDrivers = ['ODBC Driver 17 for SQL Server', 'ODBC Driver 18 for SQL Server', 'SQL Server'];
            foreach ($odbcDrivers as $drv) {
                try {
                    if ($trusted) {
                        $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;Trusted_Connection=yes;', $drv, $host, $database);
                        $pdo = new \PDO($dsn, null, null, [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                            \PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                        self::$cachedPdo = $pdo;
                        return $pdo;
                    }

                    $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;', $drv, $host, $database);
                    $pdo = new \PDO($dsn, $username, $password, [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    self::$cachedPdo = $pdo;
                    return $pdo;
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if (extension_loaded('pdo_sqlsrv')) {
            $encrypt = config('cim.db.encrypt');
            $trustCert = config('cim.db.trust_server_certificate');
            $dsn = sprintf('sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s', $host, $database, $encrypt, $trustCert);
            $pdo = new \PDO($dsn, $trusted ? null : $username, $trusted ? null : $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            self::$cachedPdo = $pdo;
            return $pdo;
        }

        throw new \RuntimeException('Không tìm thấy driver PDO tương thích (pdo_odbc hoặc pdo_sqlsrv) để kết nối SQL Server.');
    }

    protected function handlePurgeCameraViolations(): Response
    {
        try {
            $pdo = $this->getPdo();
            $pdo->beginTransaction();
            $pdo->exec('DELETE FROM ANH_VIPHAM');
            $pdo->exec('DELETE FROM PHAT_HIEN_VIPHAM');
            $pdo->commit();

            $evidenceDir = storage_path('app/public/evidence');
            if (is_dir($evidenceDir)) {
                $files = glob($evidenceDir . '/*');
                foreach ($files as $f) {
                    if (is_file($f)) {
                        @unlink($f);
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Đã xóa toàn bộ bản ghi phát hiện tại PHAT_HIEN_VIPHAM và ANH_VIPHAM thành công. Bản ghi CT_VIPHAM được bảo toàn nguyên vẹn.'
            ]);
        } catch (\Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return response()->json(['error' => 'Lỗi khi xóa bản ghi phát hiện: ' . $e->getMessage()], 500);
        }
    }

    protected function handleAccounts(Request $request, string $route, string $method, mixed $id): Response
    {
        try {
            $pdo = $this->getPdo();

            if ($route === 'accounts' && $method === 'GET') {
                $stmt = $pdo->query('SELECT ID AS id, USERNAME AS username, FULL_NAME AS full_name, CONVERT(VARCHAR(MAX), CAST(FULL_NAME AS VARBINARY(MAX)), 2) AS full_name_hex, ROLE AS role, ACTIVE AS active, LAST_LOGIN_AT AS last_login_at, LOCKED_UNTIL AS locked_until FROM TAIKHOAN ORDER BY ID');
                $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($items as &$item) {
                    $item['full_name'] = $this->decodeHexUnicode($item['full_name_hex'] ?? null, $item['full_name']);
                    unset($item['full_name_hex']);
                    $item['active'] = (bool)$item['active'];
                    if (!empty($item['last_login_at'])) {
                        $item['last_login_at'] = date('Y-m-d H:i:s', strtotime((string)$item['last_login_at']));
                    }
                    if (!empty($item['locked_until'])) {
                        $item['locked_until'] = date('Y-m-d H:i:s', strtotime((string)$item['locked_until']));
                    }
                }
                return response()->json(['items' => $items]);
            }

            if ($route === 'accounts' && $method === 'POST') {
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $username = trim((string)($data['username'] ?? ''));
                $fullName = trim((string)($data['full_name'] ?? ''));
                $role = (string)($data['role'] ?? 'CTSV');
                $password = (string)($data['password'] ?? '');

                if ($username === '' || $fullName === '' || $password === '') {
                    return response()->json(['error' => 'Vui lòng điền đầy đủ thông tin.'], 422);
                }
                $pwdErrors = AuthController::validatePasswordStrength($password);
                if (!empty($pwdErrors)) {
                    return response()->json(['error' => 'Mật khẩu chưa đủ độ mạnh: ' . implode(' ', $pwdErrors)], 422);
                }

                $check = $pdo->prepare('SELECT COUNT(*) FROM TAIKHOAN WHERE USERNAME = :u');
                $check->execute(['u' => $username]);
                if ($check->fetchColumn() > 0) {
                    return response()->json(['error' => 'Tên đăng nhập đã tồn tại.'], 409);
                }

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare('INSERT INTO TAIKHOAN (USERNAME, PASSWORD_HASH, FULL_NAME, ROLE, ACTIVE) VALUES (:u, :p, :n, :r, 1)');
                $insert->execute([
                    'u' => $username,
                    'p' => $hash,
                    'n' => $fullName,
                    'r' => in_array($role, ['ADMIN', 'CTSV'], true) ? $role : 'CTSV',
                ]);

                return response()->json(['message' => 'Đã tạo tài khoản thành công.'], 201);
            }

            if ($route === 'account' && $method === 'PUT') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã bản ghi không hợp lệ.'], 422);
                }
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $fullName = trim((string)($data['full_name'] ?? ''));
                $role = (string)($data['role'] ?? 'CTSV');
                $role = in_array($role, ['ADMIN', 'CTSV'], true) ? $role : 'CTSV';
                $active = !empty($data['active']) ? 1 : 0;
                if ($fullName === '') {
                    return response()->json(['error' => 'Họ tên không được để trống.'], 422);
                }

                $actorId = (int)$request->session()->get('user_id', 0);
                $actorUsername = strtolower(trim((string)$request->session()->get('username', '')));

                $pdo->beginTransaction();
                try {
                    $cur = $pdo->prepare('SELECT USERNAME, ROLE, ACTIVE FROM TAIKHOAN WITH (UPDLOCK) WHERE ID = :id');
                    $cur->execute(['id' => $numericId]);
                    $current = $cur->fetch(\PDO::FETCH_ASSOC);
                    if (!$current) {
                        $pdo->rollBack();
                        return response()->json(['error' => 'Không tìm thấy tài khoản.'], 404);
                    }

                    $isSelf = ($actorId > 0 && $numericId === $actorId)
                        || ($actorUsername !== '' && strtolower(trim((string)$current['USERNAME'])) === $actorUsername);
                    if ($isSelf) {
                        if (!$active) {
                            $pdo->rollBack();
                            return response()->json(['error' => 'Không thể tự vô hiệu hóa tài khoản đang đăng nhập.'], 409);
                        }
                        if ($role !== 'ADMIN') {
                            $pdo->rollBack();
                            return response()->json(['error' => 'Không thể tự hạ quyền tài khoản đang đăng nhập.'], 409);
                        }
                    }

                    // Giống FastAPI: luôn phải còn ít nhất một quản trị viên đang hoạt động.
                    $wasActiveAdmin = $current['ROLE'] === 'ADMIN' && (bool)$current['ACTIVE'];
                    if ($wasActiveAdmin && ($role !== 'ADMIN' || !$active)) {
                        $others = $pdo->prepare("SELECT COUNT(*) FROM TAIKHOAN WITH (UPDLOCK, HOLDLOCK)
                                                 WHERE ROLE = 'ADMIN' AND ACTIVE = 1 AND ID <> :id");
                        $others->execute(['id' => $numericId]);
                        if ((int)$others->fetchColumn() === 0) {
                            $pdo->rollBack();
                            return response()->json(['error' => 'Phải còn ít nhất một quản trị viên đang hoạt động.'], 409);
                        }
                    }

                    // Đổi quyền hoặc vô hiệu hóa thì thu hồi phiên đang mở của tài khoản đó.
                    $bump = ($current['ROLE'] !== $role || (bool)$current['ACTIVE'] !== (bool)$active) ? 1 : 0;
                    $upd = $pdo->prepare('UPDATE TAIKHOAN SET FULL_NAME = :n, ROLE = :r, ACTIVE = :a,
                                          TOKEN_VERSION = TOKEN_VERSION + :b WHERE ID = :id');
                    $upd->execute(['n' => $fullName, 'r' => $role, 'a' => $active, 'b' => $bump, 'id' => $numericId]);
                    $pdo->commit();
                } catch (\Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }

                return response()->json(['message' => 'Đã cập nhật tài khoản.']);
            }

            if ($route === 'account-password' && $method === 'POST') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã bản ghi không hợp lệ.'], 422);
                }
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $newPassword = (string)($data['new_password'] ?? '');
                $pwdErrors = AuthController::validatePasswordStrength($newPassword);
                if (!empty($pwdErrors)) {
                    return response()->json(['error' => 'Mật khẩu mới chưa đủ độ mạnh: ' . implode(' ', $pwdErrors)], 422);
                }

                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                $upd = $pdo->prepare('UPDATE TAIKHOAN SET PASSWORD_HASH = :p, LOCKED_UNTIL = NULL, FAILED_ATTEMPTS = 0,
                                      TOKEN_VERSION = TOKEN_VERSION + 1 WHERE ID = :id');
                $upd->execute([
                    'p' => $hash,
                    'id' => $numericId,
                ]);
                if ($upd->rowCount() === 0) {
                    return response()->json(['error' => 'Không tìm thấy tài khoản.'], 404);
                }

                return response()->json(['message' => 'Đã đặt lại mật khẩu thành công.']);
            }

            return response()->json(['error' => 'Hành động không hợp lệ.'], 400);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Không xử lý được yêu cầu tài khoản do lỗi hệ thống.'], 500);
        }
    }

    protected function cleanUtf8(mixed $str): string
    {
        if ($str === null || $str === '') {
            return '';
        }
        $val = (string)$str;
        return mb_check_encoding($val, 'UTF-8') ? $val : mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
    }

    protected function decodeHexUnicode(?string $hex, mixed $fallback = ''): string
    {
        if ($hex && ctype_xdigit($hex) && strlen($hex) % 4 === 0) {
            $bin = @hex2bin($hex);
            if ($bin !== false) {
                $utf8 = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16LE');
                if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) {
                $unmojibake = @mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');
                if ($unmojibake !== false && mb_check_encoding($unmojibake, 'UTF-8') && !str_contains($unmojibake, '?')) {
                    if (preg_match('/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ]/u', $unmojibake)) {
                        return $unmojibake;
                    }
                }
                    return $utf8;
                }
            }
        }
        if ($fallback === null || $fallback === '') {
            return '';
        }
        $val = (string)$fallback;
        return mb_check_encoding($val, 'UTF-8') ? $val : mb_convert_encoding($val, 'UTF-8', 'Windows-1252');
    }

    protected function handleViolationTypes(): Response
    {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->query('SELECT ID as id, TEN_LOI as name, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) as name_hex, DIEM_TRU as points_deducted FROM LOI_VIPHAM ORDER BY ID');
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $items = [];
            foreach ($rows as $r) {
                $items[] = [
                    'id' => (int)$r['id'],
                    'name' => $this->decodeHexUnicode($r['name_hex'] ?? null, $r['name']),
                    'points_deducted' => (int)$r['points_deducted'],
                ];
            }
            return response()->json(['items' => $items]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi tải danh mục loại lỗi: ' . $e->getMessage()], 500);
        }
    }

    protected function getR2PresignedUrl(string $key, int $expiresIn = 0): ?string
    {
        $expiresIn = $expiresIn > 0 ? $expiresIn : max(60, min(604800, (int)config('cim.r2.presign_ttl')));
        $accessKey = (string)config('cim.r2.access_key');
        $secretKey = (string)config('cim.r2.secret_key');
        $endpoint = rtrim((string)config('cim.r2.endpoint'), '/');
        $bucket = (string)config('cim.r2.bucket');

        if ($accessKey === '' || $secretKey === '' || $endpoint === '') {
            return null;
        }

        $parsed = parse_url($endpoint);
        $host = $parsed['host'] ?? '';
        if (!$host) {
            return null;
        }

        $now = time();
        $amzDate = gmdate('Ymd\THis\Z', $now);
        $dateStamp = gmdate('Ymd', $now);
        $region = 'auto';
        $service = 's3';

        $uriPath = '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
        $credentialScope = "{$dateStamp}/{$region}/{$service}/aws4_request";

        $canonicalQuery = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => "{$accessKey}/{$credentialScope}",
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => (string)$expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($canonicalQuery);
        $queryString = http_build_query($canonicalQuery, '', '&', PHP_QUERY_RFC3986);

        $canonicalRequest = "GET\n{$uriPath}\n{$queryString}\nhost:{$host}\n\nhost\nUNSIGNED-PAYLOAD";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateStamp, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        return "{$endpoint}{$uriPath}?{$queryString}&X-Amz-Signature={$signature}";
    }

    protected function handleEvidence(Request $request, mixed $id): Response
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($numericId === false) {
            return response()->json(['error' => 'Mã bản ghi không hợp lệ.'], 422);
        }

        // 1. Kiểm tra trực tiếp trong SQL Server xem bản ghi có ANH_KHOA trên Cloudflare R2 không (bảng ANH_VIPHAM)
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare("SELECT ANH_KHOA FROM ANH_VIPHAM
                                   WHERE PHAT_HIEN_ID = CAST(:id AS BIGINT)
                                     AND (THOI_GIAN_HET_HAN IS NULL OR THOI_GIAN_HET_HAN > SYSDATETIME())");
            $stmt->bindValue(':id', $numericId, \PDO::PARAM_INT);
            $stmt->execute();
            $rec = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($rec && !empty($rec['ANH_KHOA'])) {
                $presignedUrl = $this->getR2PresignedUrl((string)$rec['ANH_KHOA']);
                if ($presignedUrl !== null) {
                    return redirect()->away($presignedUrl, 302, [
                        'Cache-Control' => 'private, max-age=300',
                    ]);
                }
            }
        } catch (\Throwable) {
            // Tiếp tục thử gọi qua FastAPI nếu truy vấn DB trực tiếp gặp sự cố
        }

        // 2. Dự phòng: Gửi yêu cầu qua FastAPI nếu khả dụng
        $token = (string)$request->session()->get('api_token', '');
        $result = $this->apiClient->request('GET', "/api/v1/violations/{$numericId}/evidence", [], null, $token, 20, true);

        if ($result['status'] === 200) {
            $contentType = $result['headers']['content-type'] ?? 'image/jpeg';
            return response($result['body'], 200, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300"><rect width="400" height="300" fill="#f1f5f9"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-family="sans-serif" font-size="16">Không có ảnh bằng chứng</text></svg>';
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    protected function handleAreas(Request $request, string $method, mixed $id): Response
    {
        try {
            $pdo = $this->getPdo();

            if ($method === 'GET') {
                $stmt = $pdo->query('SELECT k.ID as id, k.MA_KHU_VUC as code, k.TEN_KHU_VUC as name, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as name_hex, k.MO_TA as description, CONVERT(VARCHAR(MAX), CAST(k.MO_TA AS VARBINARY(MAX)), 2) as description_hex, k.DANG_SU_DUNG as active, (SELECT COUNT(*) FROM CAMERA c WHERE c.KHU_VUC_ID = k.ID) as camera_count FROM KHU_VUC k ORDER BY k.ID');
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $items = [];
                foreach ($rows as $r) {
                    $items[] = [
                        'id' => (int)$r['id'],
                        'code' => (string)$r['code'],
                        'name' => $this->decodeHexUnicode($r['name_hex'] ?? null, $r['name']),
                        'description' => $this->decodeHexUnicode($r['description_hex'] ?? null, $r['description']),
                        'camera_count' => (int)($r['camera_count'] ?? 0),
                        'active' => (bool)$r['active'],
                    ];
                }
                return response()->json(['items' => $items]);
            }

            if ($method === 'POST') {
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $code = strtoupper(trim((string)($data['code'] ?? '')));
                $name = trim((string)($data['name'] ?? ''));
                $description = trim((string)($data['description'] ?? ''));
                $active = !empty($data['active']) ? 1 : 0;

                if ($code === '' || $name === '') {
                    return response()->json(['error' => 'Vui lòng nhập mã và tên khu vực.'], 422);
                }

                $check = $pdo->prepare('SELECT COUNT(*) FROM KHU_VUC WHERE MA_KHU_VUC = :c');
                $check->execute(['c' => $code]);
                if ($check->fetchColumn() > 0) {
                    return response()->json(['error' => 'Mã khu vực đã tồn tại trong hệ thống.'], 409);
                }

                $ins = $pdo->prepare('INSERT INTO KHU_VUC (MA_KHU_VUC, TEN_KHU_VUC, MO_TA, DANG_SU_DUNG) VALUES (:c, :n, :d, :act)');
                $ins->execute([
                    'c' => $code,
                    'n' => $name,
                    'd' => $description ?: null,
                    'act' => $active,
                ]);

                return response()->json(['message' => 'Đã thêm khu vực thành công.'], 201);
            }

            if ($method === 'PUT') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã khu vực không hợp lệ.'], 422);
                }
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $name = trim((string)($data['name'] ?? ''));
                $description = trim((string)($data['description'] ?? ''));
                $active = !empty($data['active']) ? 1 : 0;

                $upd = $pdo->prepare('UPDATE KHU_VUC SET TEN_KHU_VUC = COALESCE(NULLIF(CAST(:n AS NVARCHAR(100)), \'\'), TEN_KHU_VUC), MO_TA = CAST(:d AS NVARCHAR(255)), DANG_SU_DUNG = :act WHERE ID = :id');
                $upd->execute([
                    'n' => $name,
                    'd' => $description ?: null,
                    'act' => $active,
                    'id' => $numericId,
                ]);

                return response()->json(['message' => 'Đã cập nhật khu vực thành công.']);
            }

            if ($method === 'DELETE') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã khu vực không hợp lệ.'], 422);
                }

                // 1. Kiểm tra camera trực thuộc
                $camCheck = $pdo->prepare('SELECT COUNT(*) FROM CAMERA WHERE KHU_VUC_ID = :id');
                $camCheck->execute(['id' => $numericId]);
                $camCount = (int)$camCheck->fetchColumn();
                if ($camCount > 0) {
                    return response()->json(['error' => "Không thể xóa khu vực này vì đang có {$camCount} camera trực thuộc. Vui lòng chuyển hoặc xóa camera trước."], 409);
                }

                // 2. Kiểm tra bản ghi vi phạm
                $vCheck = $pdo->prepare('SELECT COUNT(*) FROM CT_VIPHAM WHERE KHU_VUC_ID = :id');
                $vCheck->execute(['id' => $numericId]);
                $vCount = (int)$vCheck->fetchColumn();
                if ($vCount > 0) {
                    return response()->json(['error' => "Khu vực này đã có {$vCount} bản ghi vi phạm trong hệ thống. Để bảo toàn lịch sử vi phạm, vui lòng chuyển sang trạng thái 'Ngừng sử dụng' thay vì xóa vĩnh viễn."], 409);
                }

                // 3. Thực hiện xóa
                $del = $pdo->prepare('DELETE FROM KHU_VUC WHERE ID = :id');
                $del->execute(['id' => $numericId]);
                return response()->json(['message' => 'Đã xóa khu vực thành công.']);
            }

            return response()->json(['error' => 'Phương thức không được hỗ trợ.'], 405);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi xử lý khu vực: ' . $e->getMessage()], 500);
        }
    }

    protected function handleCameras(Request $request, string $method, mixed $id, string $route): Response
    {
        try {
            $pdo = $this->getPdo();

            if ($method === 'GET') {
                $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);
                $sql = 'SELECT c.ID as id, c.MA_CAMERA as code, c.TEN_CAMERA as name, CONVERT(VARCHAR(MAX), CAST(c.TEN_CAMERA AS VARBINARY(MAX)), 2) as name_hex, c.VI_TRI as location, c.DANG_SU_DUNG as active, k.ID as area_id, k.TEN_KHU_VUC as area_name, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex FROM CAMERA c LEFT JOIN KHU_VUC k ON k.ID = c.KHU_VUC_ID';
                if ($areaId) {
                    $sql .= ' WHERE c.KHU_VUC_ID = :area_id';
                }
                $sql .= ' ORDER BY c.ID';
                $stmt = $pdo->prepare($sql);
                if ($areaId) {
                    $stmt->execute(['area_id' => $areaId]);
                } else {
                    $stmt->execute();
                }
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $items = [];
                foreach ($rows as $r) {
                    $items[] = [
                        'id' => (int)$r['id'],
                        'code' => (string)$r['code'],
                        'name' => $this->decodeHexUnicode($r['name_hex'] ?? null, $r['name']),
                        'location' => (string)($r['location'] ?? ''),
                        'active' => (bool)$r['active'],
                        'area_id' => (int)($r['area_id'] ?? 1),
                        'area' => [
                            'id' => (int)($r['area_id'] ?? 1),
                            'name' => $this->decodeHexUnicode($r['area_name_hex'] ?? null, $r['area_name'] ?? 'Khu vực'),
                        ],
                    ];
                }
                return response()->json(['items' => $items]);
            }

            if ($route === 'cameras' && $method === 'POST') {
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $code = strtoupper(trim((string)($data['code'] ?? '')));
                $name = trim((string)($data['name'] ?? ''));
                $areaId = (int)($data['area_id'] ?? 1);
                $location = trim((string)($data['location'] ?? ''));
                $active = !empty($data['active']) ? 1 : 0;

                if ($code === '' || $name === '') {
                    return response()->json(['error' => 'Vui lòng nhập mã và tên camera.'], 422);
                }

                $check = $pdo->prepare('SELECT COUNT(*) FROM CAMERA WHERE MA_CAMERA = :c');
                $check->execute(['c' => $code]);
                if ($check->fetchColumn() > 0) {
                    return response()->json(['error' => 'Mã camera đã tồn tại trong hệ thống.'], 409);
                }

                $ins = $pdo->prepare('INSERT INTO CAMERA (MA_CAMERA, TEN_CAMERA, KHU_VUC_ID, VI_TRI, DANG_SU_DUNG) VALUES (:c, :n, :a, :loc, :act)');
                $ins->execute([
                    'c' => $code,
                    'n' => $name,
                    'a' => $areaId,
                    'loc' => $location ?: null,
                    'act' => $active,
                ]);

                return response()->json([
                    'message' => 'Đã thêm camera thành công.',
                    'data' => ['code' => $code],
                ], 201);
            }

            if ($route === 'camera' && $method === 'PUT') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã camera không hợp lệ.'], 422);
                }
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $name = trim((string)($data['name'] ?? ''));
                $areaId = (int)($data['area_id'] ?? 1);
                $location = trim((string)($data['location'] ?? ''));
                $active = !empty($data['active']) ? 1 : 0;

                $upd = $pdo->prepare('UPDATE CAMERA SET TEN_CAMERA = COALESCE(NULLIF(CAST(:n AS NVARCHAR(100)), \'\'), TEN_CAMERA), KHU_VUC_ID = :a, VI_TRI = CAST(:loc AS NVARCHAR(255)), DANG_SU_DUNG = :act WHERE ID = :id');
                $upd->execute([
                    'n' => $name,
                    'a' => $areaId,
                    'loc' => $location ?: null,
                    'act' => $active,
                    'id' => $numericId,
                ]);

                return response()->json(['message' => 'Đã cập nhật camera thành công.']);
            }

            if ($route === 'camera' && $method === 'DELETE') {
                $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($numericId === false) {
                    return response()->json(['error' => 'Mã camera không hợp lệ.'], 422);
                }

                // Kiểm tra bản ghi vi phạm liên kết
                $vCheck = $pdo->prepare('SELECT COUNT(*) FROM ANH_VIPHAM WHERE CAMERA_ID = :id');
                $vCheck->execute(['id' => $numericId]);
                $vCount = (int)$vCheck->fetchColumn();
                if ($vCount > 0) {
                    return response()->json(['error' => "Không thể xóa camera này vì đã có {$vCount} bản ghi vi phạm liên kết. Để bảo toàn lịch sử vi phạm, vui lòng chọn 'Ngừng sử dụng' thay vì xóa vĩnh viễn."], 409);
                }

                $del = $pdo->prepare('DELETE FROM CAMERA WHERE ID = :id');
                $del->execute(['id' => $numericId]);
                return response()->json(['message' => 'Đã xóa camera thành công.']);
            }

            return response()->json(['error' => 'Hành động không hợp lệ.'], 400);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi xử lý camera: ' . $e->getMessage()], 500);
        }
    }

    protected function handleSemesters(Request $request, string $method, mixed $id): Response
    {
        try {
            $pdo = $this->getPdo();

            if ($method === 'GET') {
                $today = date('Y-m-d');
                $stmt = $pdo->query('SELECT NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY ORDER BY NGAY_BAT_DAU ASC');
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $items = [];
                foreach ($rows as $r) {
                    $sDate = date('Y-m-d', strtotime((string)$r['NGAY_BAT_DAU']));
                    $eDate = date('Y-m-d', strtotime((string)$r['NGAY_KET_THUC']));
                    $isCurrent = ($today >= $sDate && $today <= $eDate);
                    $academicYear = trim((string)$r['NAM_HOC']);
                    $term = (int)$r['HOC_KY'];
                    $name = "Học kỳ {$term} ({$academicYear})";
                    $items[] = [
                        'id' => "{$academicYear}|{$term}",
                        'academic_year' => $academicYear,
                        'term' => $term,
                        'code' => "HK{$term}-{$academicYear}",
                        'name' => $name,
                        'start_date' => $sDate,
                        'end_date' => $eDate,
                        'is_current' => $isCurrent,
                    ];
                }
                return response()->json(['items' => $items]);
            }

            if ($method === 'POST') {
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $academicYear = trim((string)($data['academic_year'] ?? ''));
                $term = filter_var($data['term'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
                $startDate = trim((string)($data['start_date'] ?? ''));
                $endDate = trim((string)($data['end_date'] ?? ''));

                // Hỗ trợ trường hợp client cũ gửi mã code VD: HK1-2025-2026 hoặc HK1-2026
                if ($academicYear === '' && !empty($data['code'])) {
                    if (preg_match('/HK([1-3])-(\d{4}-\d{4})/i', (string)$data['code'], $m)) {
                        $term = (int)$m[1];
                        $academicYear = $m[2];
                    }
                }

                if ($academicYear === '' || !preg_match('/^\d{4}-\d{4}$/', $academicYear)) {
                    return response()->json(['error' => 'Năm học phải có định dạng YYYY-YYYY (ví dụ: 2025-2026).'], 422);
                }
                if ($term === false || $term === null) {
                    return response()->json(['error' => 'Học kỳ phải là 1, 2 hoặc 3.'], 422);
                }
                if ($startDate === '' || $endDate === '') {
                    return response()->json(['error' => 'Vui lòng chọn ngày bắt đầu và kết thúc.'], 422);
                }
                if ($endDate < $startDate) {
                    return response()->json(['error' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.'], 422);
                }

                // Kiểm tra trùng khóa chính
                $dupCheck = $pdo->prepare('SELECT COUNT(*) FROM HOCKY WHERE NAM_HOC = :y AND HOC_KY = :t');
                $dupCheck->execute(['y' => $academicYear, 't' => $term]);
                if ((int)$dupCheck->fetchColumn() > 0) {
                    return response()->json(['error' => "Học kỳ {$term} ({$academicYear}) đã tồn tại trong hệ thống."], 409);
                }

                // Kiểm tra trùng lặp khoảng thời gian với học kỳ khác
                $overlapCheck = $pdo->prepare('SELECT TOP 1 NAM_HOC, HOC_KY FROM HOCKY WHERE NOT (NAM_HOC = :y AND HOC_KY = :t) AND NGAY_BAT_DAU <= :e AND NGAY_KET_THUC >= :s');
                $overlapCheck->execute(['y' => $academicYear, 't' => $term, 's' => $startDate, 'e' => $endDate]);
                $overlap = $overlapCheck->fetch(\PDO::FETCH_ASSOC);
                if ($overlap) {
                    $ovYear = trim((string)$overlap['NAM_HOC']);
                    $ovTerm = (int)$overlap['HOC_KY'];
                    return response()->json(['error' => "Khoảng thời gian bị trùng với Học kỳ {$ovTerm} ({$ovYear})."], 409);
                }

                $ins = $pdo->prepare('INSERT INTO HOCKY (NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC) VALUES (:y, :t, :s, :e)');
                $ins->execute([
                    'y' => $academicYear,
                    't' => $term,
                    's' => $startDate,
                    'e' => $endDate,
                ]);

                return response()->json(['message' => 'Đã thêm học kỳ thành công.'], 201);
            }

            if ($method === 'PUT') {
                $data = $request->isJson() ? $request->json()->all() : $request->all();
                $academicYear = trim((string)($request->query('academic_year') ?? $data['academic_year'] ?? ''));
                $term = filter_var($request->query('term') ?? $data['term'] ?? null, FILTER_VALIDATE_INT);

                if ((!$academicYear || !$term) && is_string($id) && str_contains($id, '|')) {
                    [$academicYear, $term] = explode('|', $id);
                    $term = (int)$term;
                }

                if (!$academicYear || !$term) {
                    return response()->json(['error' => 'Thông tin định danh học kỳ không hợp lệ.'], 422);
                }

                $startDate = trim((string)($data['start_date'] ?? ''));
                $endDate = trim((string)($data['end_date'] ?? ''));
                if ($startDate === '' || $endDate === '') {
                    return response()->json(['error' => 'Vui lòng chọn ngày bắt đầu và kết thúc.'], 422);
                }
                if ($endDate < $startDate) {
                    return response()->json(['error' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.'], 422);
                }

                // Kiểm tra trùng lặp khoảng thời gian
                $overlapCheck = $pdo->prepare('SELECT TOP 1 NAM_HOC, HOC_KY FROM HOCKY WHERE NOT (NAM_HOC = :y AND HOC_KY = :t) AND NGAY_BAT_DAU <= :e AND NGAY_KET_THUC >= :s');
                $overlapCheck->execute(['y' => $academicYear, 't' => $term, 's' => $startDate, 'e' => $endDate]);
                $overlap = $overlapCheck->fetch(\PDO::FETCH_ASSOC);
                if ($overlap) {
                    $ovYear = trim((string)$overlap['NAM_HOC']);
                    $ovTerm = (int)$overlap['HOC_KY'];
                    return response()->json(['error' => "Khoảng thời gian bị trùng với Học kỳ {$ovTerm} ({$ovYear})."], 409);
                }

                $upd = $pdo->prepare('UPDATE HOCKY SET NGAY_BAT_DAU = :s, NGAY_KET_THUC = :e WHERE NAM_HOC = :y AND HOC_KY = :t');
                $upd->execute([
                    's' => $startDate,
                    'e' => $endDate,
                    'y' => $academicYear,
                    't' => $term,
                ]);

                return response()->json(['message' => 'Đã cập nhật học kỳ thành công.']);
            }

            if ($method === 'DELETE') {
                $academicYear = trim((string)($request->query('academic_year') ?? ''));
                $term = filter_var($request->query('term'), FILTER_VALIDATE_INT);
                if ((!$academicYear || !$term) && is_string($id) && str_contains($id, '|')) {
                    [$academicYear, $term] = explode('|', $id);
                    $term = (int)$term;
                }

                if (!$academicYear || !$term) {
                    return response()->json(['error' => 'Thông tin định danh học kỳ không hợp lệ.'], 422);
                }

                $chk = $pdo->prepare('SELECT COUNT(*) FROM CT_VIPHAM WHERE NAM_HOC = :y AND HOC_KY = :t');
                $chk->execute(['y' => $academicYear, 't' => $term]);
                $count = (int)$chk->fetchColumn();
                if ($count > 0) {
                    return response()->json(['error' => "Không thể xóa học kỳ vì đã có {$count} chi tiết vi phạm liên kết trong hệ thống."], 409);
                }
                $del = $pdo->prepare('DELETE FROM HOCKY WHERE NAM_HOC = :y AND HOC_KY = :t');
                $del->execute(['y' => $academicYear, 't' => $term]);
                return response()->json(['message' => 'Đã xóa học kỳ.']);
            }

            return response()->json(['error' => 'Phương thức không được hỗ trợ.'], 405);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi xử lý học kỳ: ' . $e->getMessage()], 500);
        }
    }

    protected function handleViolations(Request $request): Response
    {
        try {
            $pdo = $this->getPdo();
            $page = max(1, (int)$request->query('page', 1));
            $pageSize = min(100, max(1, (int)$request->query('page_size', 20)));
            $offset = ($page - 1) * $pageSize;

            $dateFrom = trim((string)$request->query('date_from', ''));
            $dateTo = trim((string)$request->query('date_to', ''));
            $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);
            $typeId = filter_var($request->query('type_id'), FILTER_VALIDATE_INT);
            $cameraId = filter_var($request->query('camera_id'), FILTER_VALIDATE_INT);
            $status = trim((string)$request->query('status', ''));
            $source = strtoupper(trim((string)$request->query('source', '')));
            $sort = trim((string)$request->query('sort', 'newest'));
            $orderDir = ($sort === 'oldest') ? 'ASC' : 'DESC';

            // Xây dựng truy vấn hợp nhất giữa Camera AI và Biên bản Cờ Đỏ (CT_VIPHAM)
            $unions = [];

            if ($source === '' || $source === 'ALL' || $source === 'CAMERA') {
                $unions[] = "
                    SELECT 
                        b.ID,
                        'CAMERA' as NGUON,
                        b.THOI_DIEM,
                        b.LOI_ID,
                        lv.TEN_LOI,
                        CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2) as type_name_hex,
                        c.KHU_VUC_ID,
                        k.TEN_KHU_VUC,
                        CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex,
                        a.CAMERA_ID,
                        c.MA_CAMERA,
                        c.TEN_CAMERA,
                        CONVERT(VARCHAR(MAX), CAST(c.TEN_CAMERA AS VARBINARY(MAX)), 2) as camera_name_hex,
                        CASE WHEN a.ID IS NULL THEN 'NONE'
                             WHEN a.ANH_KHOA IS NULL THEN 'DELETED'
                             WHEN a.THOI_GIAN_HET_HAN <= SYSDATETIME() THEN 'EXPIRED'
                             ELSE 'AVAILABLE' END as ANH_TRANG_THAI,
                        b.TINH_TRANG_XU_LY as TRANG_THAI_XU_LY,
                        CAST(NULL AS NVARCHAR(500)) as GHI_CHU_XU_LY,
                        b.XU_LY_LUC,
                        CAST(NULL AS NVARCHAR(100)) as nguoi_xu_ly,
                        CAST(NULL AS VARCHAR(MAX)) as nguoi_xu_ly_hex,
                        CAST(NULL AS INT) as SV_ID,
                        CAST(NULL AS VARCHAR(30)) as MA_SV,
                        CAST(NULL AS NVARCHAR(100)) as HOTEN,
                        CAST(NULL AS VARCHAR(MAX)) as HOTEN_HEX,
                        CAST(NULL AS VARCHAR(30)) as MA_LOP,
                        CAST(NULL AS CHAR(9)) as NAM_HOC,
                        CAST(NULL AS TINYINT) as HOC_KY,
                        CAST(NULL AS NVARCHAR(100)) as TEN_HK,
                        CAST(NULL AS VARCHAR(MAX)) as TEN_HK_HEX
                    FROM PHAT_HIEN_VIPHAM b
                    LEFT JOIN ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID
                    LEFT JOIN LOI_VIPHAM lv ON b.LOI_ID = lv.ID
                    LEFT JOIN CAMERA c ON a.CAMERA_ID = c.ID
                    LEFT JOIN KHU_VUC k ON c.KHU_VUC_ID = k.ID
                ";
            }

            // Dữ liệu từ CT_VIPHAM (Biên bản Đội Cờ Đỏ / Cán bộ CTSV)
            if ($source === '' || $source === 'ALL' || $source === 'CO_DO') {
                $unions[] = "
                    SELECT 
                        cv.ID,
                        'CO_DO' as NGUON,
                        COALESCE(cv.THOI_DIEM, DATEADD(MINUTE, 450, CAST(cv.NGAY_VIPHAM AS DATETIME2(0)))) as THOI_DIEM,
                        cv.ID_LOI as LOI_ID,
                        lv.TEN_LOI,
                        CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2) as type_name_hex,
                        cv.KHU_VUC_ID,
                        k.TEN_KHU_VUC,
                        CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex,
                        CAST(NULL AS INT) as CAMERA_ID,
                        'CO_DO' as MA_CAMERA,
                        N'Biên bản Đội Cờ Đỏ' as TEN_CAMERA,
                        CAST(NULL AS VARCHAR(MAX)) as camera_name_hex,
                        'NONE' as ANH_TRANG_THAI,
                        'DA_XU_LY' as TRANG_THAI_XU_LY,
                        cv.GHI_CHU as GHI_CHU_XU_LY,
                        COALESCE(cv.THOI_DIEM, DATEADD(MINUTE, 450, CAST(cv.NGAY_VIPHAM AS DATETIME2(0)))) as XU_LY_LUC,
                        N'Đội Cờ Đỏ / CTSV' as nguoi_xu_ly,
                        CAST(NULL AS VARCHAR(MAX)) as nguoi_xu_ly_hex,
                        sv.ID as SV_ID,
                        sv.MA_SV,
                        sv.HOTEN,
                        CONVERT(VARCHAR(MAX), CAST(sv.HOTEN AS VARBINARY(MAX)), 2) as HOTEN_HEX,
                        l.MA_LOP,
                        cv.NAM_HOC,
                        cv.HOC_KY,
                        CASE WHEN cv.HOC_KY IS NOT NULL THEN N'Học kỳ ' + CAST(cv.HOC_KY AS NVARCHAR(5)) + N' (' + CAST(cv.NAM_HOC AS NVARCHAR(9)) + N')' ELSE NULL END as TEN_HK,
                        CAST(NULL AS VARCHAR(MAX)) as TEN_HK_HEX
                    FROM CT_VIPHAM cv
                    LEFT JOIN LOI_VIPHAM lv ON cv.ID_LOI = lv.ID
                    LEFT JOIN KHU_VUC k ON cv.KHU_VUC_ID = k.ID
                    LEFT JOIN SINHVIEN sv ON cv.ID_SV = sv.ID
                    LEFT JOIN LOP l ON sv.LOP_ID = l.ID
                    LEFT JOIN HOCKY hk ON cv.NAM_HOC = hk.NAM_HOC AND cv.HOC_KY = hk.HOC_KY
                ";
            }

            if (empty($unions)) {
                return response()->json([
                    'items' => [],
                    'total' => 0,
                    'page' => $page,
                    'pages' => 1,
                ]);
            }

            $subQuery = implode(' UNION ALL ', $unions);

            $where = [];
            $params = [];
            if ($dateFrom !== '') {
                $where[] = 'CAST(u.THOI_DIEM AS DATE) >= :date_from';
                $params['date_from'] = $dateFrom;
            }
            if ($dateTo !== '') {
                $where[] = 'CAST(u.THOI_DIEM AS DATE) <= :date_to';
                $params['date_to'] = $dateTo;
            }
            if ($areaId) {
                $where[] = 'u.KHU_VUC_ID = :area_id';
                $params['area_id'] = $areaId;
            }
            if ($cameraId) {
                $where[] = 'u.CAMERA_ID = :camera_id';
                $params['camera_id'] = $cameraId;
            }
            if ($typeId) {
                $where[] = 'u.LOI_ID = :type_id';
                $params['type_id'] = $typeId;
            }
            if ($status !== '') {
                $where[] = 'u.TRANG_THAI_XU_LY = :status';
                $params['status'] = $status;
            }

            $filter = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            // NF-09: Tối ưu count query không thực hiện cast/hex text nặng trên toàn bộ dữ liệu
            $countUnions = [];
            if ($source === '' || $source === 'ALL' || $source === 'CAMERA') {
                $countUnions[] = "
                    SELECT b.ID, b.THOI_DIEM, b.LOI_ID, c.KHU_VUC_ID, a.CAMERA_ID, b.TINH_TRANG_XU_LY as TRANG_THAI_XU_LY
                    FROM PHAT_HIEN_VIPHAM b
                    LEFT JOIN ANH_VIPHAM a ON a.PHAT_HIEN_ID = b.ID
                    LEFT JOIN CAMERA c ON a.CAMERA_ID = c.ID
                ";
            }
            if ($source === '' || $source === 'ALL' || $source === 'CO_DO') {
                $countUnions[] = "
                    SELECT cv.ID, COALESCE(cv.THOI_DIEM, DATEADD(MINUTE, 450, CAST(cv.NGAY_VIPHAM AS DATETIME2(0)))) as THOI_DIEM, cv.ID_LOI as LOI_ID, cv.KHU_VUC_ID, CAST(NULL AS INT) as CAMERA_ID, 'DA_XU_LY' as TRANG_THAI_XU_LY
                    FROM CT_VIPHAM cv
                ";
            }
            $countSubQuery = implode(' UNION ALL ', $countUnions);
            $countSql = "SELECT COUNT(*) FROM ({$countSubQuery}) u" . $filter;
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $k => $v) {
                $countStmt->bindValue(':' . $k, $v);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            $sql = "SELECT u.* FROM ({$subQuery}) u" . $filter . "
                    ORDER BY u.THOI_DIEM {$orderDir}, u.ID {$orderDir}
                    OFFSET {$offset} ROWS FETCH NEXT {$pageSize} ROWS ONLY";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . $k, $v);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $items = [];
            foreach ($rows as $r) {
                $timeStr = !empty($r['THOI_DIEM']) ? date('Y-m-d H:i:s', strtotime((string)$r['THOI_DIEM'])) : date('Y-m-d H:i:s');
                $isCamera = ($r['NGUON'] ?? '') === 'CAMERA';
                $statusVal = (string)($r['TRANG_THAI_XU_LY'] ?? 'MOI');

                $areaName = $this->decodeHexUnicode($r['area_name_hex'] ?? null, $r['TEN_KHU_VUC'] ?? 'Khu vực chung');

                $studentObj = null;
                if (!empty($r['SV_ID']) || !empty($r['MA_SV'])) {
                    $studentObj = [
                        'id' => (int)($r['SV_ID'] ?? 0),
                        'code' => (string)($r['MA_SV'] ?? ''),
                        'name' => $this->decodeHexUnicode($r['HOTEN_HEX'] ?? null, $r['HOTEN'] ?? ''),
                        'class_name' => (string)($r['MA_LOP'] ?? ''),
                    ];
                }

                $cameraName = !empty($r['camera_name_hex'])
                    ? $this->decodeHexUnicode($r['camera_name_hex'], $r['TEN_CAMERA'] ?? 'Camera')
                    : (string)($r['TEN_CAMERA'] ?? ($isCamera ? 'Camera' : 'Biên bản Đội Cờ Đỏ'));

                $items[] = [
                    'id' => (int)$r['ID'],
                    'source' => (string)($r['NGUON'] ?? 'CAMERA'),
                    'occurred_at' => $timeStr,
                    'type' => [
                        'id' => (int)($r['LOI_ID'] ?? 1),
                        'name' => $this->decodeHexUnicode($r['type_name_hex'] ?? null, $r['TEN_LOI'] ?? 'Vi phạm tác phong'),
                    ],
                    'student' => $studentObj,
                    'area' => [
                        'id' => $r['KHU_VUC_ID'] !== null ? (int)$r['KHU_VUC_ID'] : null,
                        'name' => $areaName,
                    ],
                    'camera' => [
                        'id' => $r['CAMERA_ID'] !== null ? (int)$r['CAMERA_ID'] : null,
                        'code' => (string)($r['MA_CAMERA'] ?? ($isCamera ? 'CAM-01' : 'CO_DO')),
                        'name' => $cameraName,
                    ],
                    'semester' => [
                        'academic_year' => (string)($r['NAM_HOC'] ?? ''),
                        'term' => $r['HOC_KY'] !== null ? (int)$r['HOC_KY'] : null,
                        'code' => (!empty($r['NAM_HOC']) && !empty($r['HOC_KY'])) ? "HK{$r['HOC_KY']}-{$r['NAM_HOC']}" : '',
                        'name' => (string)($r['TEN_HK'] ?? ''),
                    ],
                    'evidence' => [
                        'status' => $isCamera ? strtolower((string)($r['ANH_TRANG_THAI'] ?? 'available')) : 'none',
                        'available' => $isCamera && (($r['ANH_TRANG_THAI'] ?? '') === 'AVAILABLE'),
                        'message' => $isCamera ? 'Ảnh bằng chứng vi phạm từ Camera AI.' : 'Biên bản do Đội Cờ Đỏ ghi nhận trực tiếp.',
                    ],
                    'processing' => [
                        'status' => $statusVal,
                        'processed_by' => $this->decodeHexUnicode($r['nguoi_xu_ly_hex'] ?? null, $r['nguoi_xu_ly'] ?? ($isCamera ? 'CTSV' : 'Đội Cờ Đỏ / CTSV')),
                        'processed_at' => !empty($r['XU_LY_LUC']) ? date('Y-m-d H:i:s', strtotime((string)$r['XU_LY_LUC'])) : null,
                        'note' => (string)($r['GHI_CHU_XU_LY'] ?? ''),
                    ],
                ];
            }

            return response()->json([
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'pages' => max(1, (int)ceil($total / $pageSize)),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Không thể tải danh sách vi phạm.'], 500);
        }
    }

    protected function handleViolationDetail(Request $request, string $method, mixed $id): Response
    {
        $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($numericId === false) {
            return response()->json(['error' => 'Mã vi phạm không hợp lệ.'], 422);
        }
        $body = $method === 'PATCH' ? ($request->isJson() ? $request->json()->all() : $request->all()) : [];
        $source = strtoupper((string)($body['source'] ?? $request->query('source', 'CAMERA')));
        if (!in_array($source, ['CAMERA', 'CO_DO'], true)) {
            return response()->json(['error' => 'Nguồn vi phạm không hợp lệ.'], 422);
        }

        try {
            $pdo = $this->getPdo();
            if ($method === 'PATCH') {
                if ($source === 'CAMERA') {
                    $status = (string)($body['status'] ?? 'DA_XU_LY');
                    if (!in_array($status, ['MOI', 'DA_XEM', 'DA_XU_LY', 'BO_QUA'], true)) {
                        return response()->json(['error' => 'Trạng thái xử lý không hợp lệ.'], 422);
                    }
                    $timeExpr = ($status === 'MOI') ? 'NULL' : 'SYSDATETIME()';
                    $upd = $pdo->prepare("UPDATE PHAT_HIEN_VIPHAM SET TINH_TRANG_XU_LY = :st,
                                          XU_LY_LUC = {$timeExpr}
                                          WHERE ID = :id");
                    $upd->execute(['st' => $status, 'id' => $numericId]);
                } else {
                    $note = mb_substr(trim((string)($body['note'] ?? '')), 0, 500);
                    $upd = $pdo->prepare('UPDATE CT_VIPHAM SET GHI_CHU = :nt WHERE ID = :id');
                    $upd->execute(['nt' => $note, 'id' => $numericId]);
                }
                if ($upd->rowCount() === 0) {
                    return response()->json(['error' => 'Không tìm thấy bản ghi vi phạm.'], 404);
                }
            }

            $data = $source === 'CAMERA'
                ? $this->findCameraViolation($pdo, $numericId)
                : $this->findRedFlagViolation($pdo, $numericId);
            if ($data === null) {
                return response()->json(['error' => 'Không tìm thấy bản ghi vi phạm.'], 404);
            }
            $payload = ['data' => $data];
            if ($method === 'PATCH') {
                $payload['message'] = 'Đã cập nhật trạng thái xử lý.';
            }
            return response()->json($payload);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['error' => 'Không thể tải hoặc cập nhật bản ghi vi phạm.'], 500);
        }
    }

    protected function findCameraViolation(\PDO $pdo, int $id): ?array
    {
        $sql = "SELECT p.ID, p.THOI_DIEM, p.LOI_ID, lv.TEN_LOI, CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2) as type_name_hex,
                       k.ID AS KHU_VUC_ID, k.TEN_KHU_VUC, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex,
                       c.ID AS CAMERA_ID, c.MA_CAMERA, c.TEN_CAMERA, CONVERT(VARCHAR(MAX), CAST(c.TEN_CAMERA AS VARBINARY(MAX)), 2) as camera_name_hex,
                       a.ID AS ANH_ID, a.ANH_KHOA, a.THOI_GIAN_HET_HAN, p.TINH_TRANG_XU_LY, p.XU_LY_LUC
                FROM PHAT_HIEN_VIPHAM p
                LEFT JOIN ANH_VIPHAM a ON a.PHAT_HIEN_ID = p.ID
                LEFT JOIN LOI_VIPHAM lv ON p.LOI_ID = lv.ID
                LEFT JOIN CAMERA c ON a.CAMERA_ID = c.ID
                LEFT JOIN KHU_VUC k ON c.KHU_VUC_ID = k.ID
                WHERE p.ID = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $expiresAt = !empty($row['THOI_GIAN_HET_HAN']) ? strtotime((string)$row['THOI_GIAN_HET_HAN']) : null;
        if (empty($row['ANH_ID'])) {
            $evidenceStatus = 'none';
        } elseif (empty($row['ANH_KHOA'])) {
            $evidenceStatus = 'deleted';
        } elseif ($expiresAt !== null && $expiresAt <= time()) {
            $evidenceStatus = 'expired';
        } else {
            $evidenceStatus = 'available';
        }
        $isAvailable = $evidenceStatus === 'available';
        $processedAt = !empty($row['XU_LY_LUC']) ? date('Y-m-d H:i:s', strtotime((string)$row['XU_LY_LUC'])) : null;

        return [
            'id' => (int)$row['ID'],
            'source' => 'CAMERA',
            'occurred_at' => date('Y-m-d H:i:s', strtotime((string)$row['THOI_DIEM'])),
            'type' => [
                'id' => (int)$row['LOI_ID'],
                'name' => $this->decodeHexUnicode($row['type_name_hex'] ?? null, $row['TEN_LOI'] ?? 'Vi phạm tác phong'),
            ],
            'student' => null,
            'area' => [
                'id' => $row['KHU_VUC_ID'] !== null ? (int)$row['KHU_VUC_ID'] : null,
                'name' => $this->decodeHexUnicode($row['area_name_hex'] ?? null, $row['TEN_KHU_VUC'] ?? 'Không xác định'),
            ],
            'camera' => [
                'id' => $row['CAMERA_ID'] !== null ? (int)$row['CAMERA_ID'] : null,
                'code' => (string)($row['MA_CAMERA'] ?? '—'),
                'name' => $this->decodeHexUnicode($row['camera_name_hex'] ?? null, $row['TEN_CAMERA'] ?? 'Không xác định'),
            ],
            'evidence' => [
                'available' => $isAvailable,
                'status' => $evidenceStatus,
                'expires_at' => $expiresAt !== null ? date('Y-m-d H:i:s', $expiresAt) : null,
                'message' => $isAvailable ? 'Ảnh bằng chứng vi phạm từ Camera AI.' : 'Không có ảnh bằng chứng (ảnh đã hết hạn hoặc bị xóa).',
            ],
            'processing' => [
                'status' => (string)$row['TINH_TRANG_XU_LY'],
                'processed_by' => $processedAt !== null ? 'CTSV' : null,
                'processed_at' => $processedAt,
                'note' => null,
            ],
        ];
    }

    protected function findRedFlagViolation(\PDO $pdo, int $id): ?array
    {
        $ctSql = "SELECT cv.ID, cv.NGAY_VIPHAM, cv.THOI_DIEM, cv.KHU_VUC_ID,
                         k.TEN_KHU_VUC, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex,
                         lv.ID as type_id, lv.TEN_LOI, CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2) as type_name_hex,
                         sv.ID as sv_id, sv.MA_SV, sv.HOTEN, CONVERT(VARCHAR(MAX), CAST(sv.HOTEN AS VARBINARY(MAX)), 2) as sv_name_hex,
                         l.MA_LOP, cv.GHI_CHU
                  FROM CT_VIPHAM cv
                  LEFT JOIN LOI_VIPHAM lv ON cv.ID_LOI = lv.ID
                  LEFT JOIN KHU_VUC k ON cv.KHU_VUC_ID = k.ID
                  LEFT JOIN SINHVIEN sv ON cv.ID_SV = sv.ID
                  LEFT JOIN LOP l ON sv.LOP_ID = l.ID
                  WHERE cv.ID = :id";
        $ctStmt = $pdo->prepare($ctSql);
        $ctStmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $ctStmt->execute();
        $ctRow = $ctStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$ctRow) {
            return null;
        }

        $timeStr = !empty($ctRow['THOI_DIEM'])
            ? date('Y-m-d H:i:s', strtotime((string)$ctRow['THOI_DIEM']))
            : (!empty($ctRow['NGAY_VIPHAM']) ? date('Y-m-d 07:30:00', strtotime((string)$ctRow['NGAY_VIPHAM'])) : null);

        return [
            'id' => (int)$ctRow['ID'],
            'source' => 'CO_DO',
            'occurred_at' => $timeStr,
            'type' => [
                'id' => (int)($ctRow['type_id'] ?? 0),
                'name' => $this->decodeHexUnicode($ctRow['type_name_hex'] ?? null, $ctRow['TEN_LOI'] ?? 'Vi phạm tác phong'),
            ],
            'student' => [
                'id' => (int)($ctRow['sv_id'] ?? 0),
                'code' => (string)($ctRow['MA_SV'] ?? ''),
                'name' => $this->decodeHexUnicode($ctRow['sv_name_hex'] ?? null, $ctRow['HOTEN'] ?? ''),
                'class_name' => (string)($ctRow['MA_LOP'] ?? ''),
            ],
            'area' => [
                'id' => $ctRow['KHU_VUC_ID'] !== null ? (int)$ctRow['KHU_VUC_ID'] : null,
                'name' => $this->decodeHexUnicode($ctRow['area_name_hex'] ?? null, $ctRow['TEN_KHU_VUC'] ?? 'Không xác định'),
            ],
            'camera' => [
                'id' => null,
                'code' => 'CO_DO',
                'name' => 'Biên bản Đội Cờ Đỏ',
            ],
            'evidence' => [
                'available' => false,
                'status' => 'none',
                'message' => 'Biên bản do Đội Cờ Đỏ kiểm tra và ghi nhận trực tiếp.',
            ],
            'processing' => [
                'status' => 'DA_XU_LY',
                'processed_by' => 'Đội Cờ Đỏ / CTSV',
                'processed_at' => $timeStr,
                'note' => (string)($ctRow['GHI_CHU'] ?? ''),
            ],
        ];
    }

    protected function resolveDateRange(Request $request, string $defaultPeriod = 'month'): array
    {
        $period = (string)$request->query('period', $defaultPeriod);
        $anchor = trim((string)$request->query('anchor', ''));
        $month = trim((string)$request->query('month', ''));
        $semesterId = $request->query('semester_id');
        $dateFrom = trim((string)$request->query('date_from', ''));
        $dateTo = trim((string)$request->query('date_to', ''));

        switch ($period) {
            case 'day':
                $day = $anchor !== '' ? $anchor : date('Y-m-d');
                $d = date('d/m/Y', strtotime($day));
                return [
                    'period' => 'day',
                    'date_from' => $day,
                    'date_to' => $day,
                    'label' => "Ngày {$d}",
                ];

            case 'week':
                $time = $anchor !== '' ? strtotime($anchor) : time();
                $monday = date('Y-m-d', strtotime('monday this week', $time));
                $sunday = date('Y-m-d', strtotime('sunday this week', $time));
                $dStart = date('d/m', strtotime($monday));
                $dEnd = date('d/m/Y', strtotime($sunday));
                return [
                    'period' => 'week',
                    'date_from' => $monday,
                    'date_to' => $sunday,
                    'label' => "Tuần {$dStart} - {$dEnd}",
                ];

            case 'semester':
                try {
                    $pdo = $this->getPdo();
                    $academicYear = trim((string)$request->query('academic_year', ''));
                    $term = filter_var($request->query('term'), FILTER_VALIDATE_INT);
                    if ((!$academicYear || !$term) && $semesterId && is_string($semesterId) && str_contains($semesterId, '|')) {
                        [$academicYear, $term] = explode('|', $semesterId);
                        $term = (int)$term;
                    }
                    if ($academicYear && $term) {
                        $stmt = $pdo->prepare('SELECT NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY WHERE NAM_HOC = :y AND HOC_KY = :t');
                        $stmt->execute(['y' => $academicYear, 't' => $term]);
                        $hk = $stmt->fetch(\PDO::FETCH_ASSOC);
                    } else {
                        $today = date('Y-m-d');
                        $stmt = $pdo->prepare('SELECT TOP 1 NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY WHERE :today BETWEEN NGAY_BAT_DAU AND NGAY_KET_THUC ORDER BY NGAY_BAT_DAU DESC');
                        $stmt->execute(['today' => $today]);
                        $hk = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if (!$hk) {
                            $hk = $pdo->query('SELECT TOP 1 NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY ORDER BY NGAY_BAT_DAU DESC')->fetch(\PDO::FETCH_ASSOC);
                        }
                    }

                    if ($hk) {
                        $y = trim((string)$hk['NAM_HOC']);
                        $t = (int)$hk['HOC_KY'];
                        $label = "Học kỳ {$t} ({$y})";
                        return [
                            'period' => 'semester',
                            'date_from' => date('Y-m-d', strtotime((string)$hk['NGAY_BAT_DAU'])),
                            'date_to' => date('Y-m-d', strtotime((string)$hk['NGAY_KET_THUC'])),
                            'label' => $label,
                            'academic_year' => $y,
                            'term' => $t,
                            'ma_hk' => "HK{$t}-{$y}",
                        ];
                    }
                } catch (\Throwable) {
                    // Fallback
                }
                return [
                    'period' => 'semester',
                    'date_from' => '2026-08-15',
                    'date_to' => '2027-01-15',
                    'label' => 'Học kỳ 1 (2026-2027)',
                    'academic_year' => '2026-2027',
                    'term' => 1,
                    'ma_hk' => 'HK1-2026-2027',
                ];

            case 'custom':
                $from = $dateFrom !== '' ? $dateFrom : date('Y-m-01');
                $to = $dateTo !== '' ? $dateTo : date('Y-m-d');
                $dFrom = date('d/m/Y', strtotime($from));
                $dTo = date('d/m/Y', strtotime($to));
                return [
                    'period' => 'custom',
                    'date_from' => $from,
                    'date_to' => $to,
                    'label' => "Từ {$dFrom} đến {$dTo}",
                ];

            case 'month':
            default:
                if ($month !== '' && preg_match('/^\d{4}-\d{2}$/', $month)) {
                    $mTime = strtotime($month . '-01');
                } else {
                    $mTime = time();
                }
                $firstDay = date('Y-m-01', $mTime);
                $lastDay = date('Y-m-t', $mTime);
                $label = 'Tháng ' . date('m/Y', $mTime);
                return [
                    'period' => 'month',
                    'date_from' => $firstDay,
                    'date_to' => $lastDay,
                    'label' => $label,
                ];
        }
    }



    protected function getSummaryData(\PDO $pdo, Request $request, array $dateRange): array
    {
        $where = [
            'cv.NGAY_VIPHAM >= :date_from',
            'cv.NGAY_VIPHAM <= :date_to',
        ];
        $params = [
            'date_from' => $dateRange['date_from'],
            'date_to' => $dateRange['date_to'],
        ];

        $typeId = filter_var($request->query('type_id'), FILTER_VALIDATE_INT);
        if ($typeId) {
            $where[] = 'cv.ID_LOI = :type_id';
            $params['type_id'] = $typeId;
        }

        $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);
        if ($areaId) {
            $where[] = 'cv.KHU_VUC_ID = :area_id';
            $params['area_id'] = $areaId;
        }

        $filterSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM CT_VIPHAM cv' . $filterSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // NT-10: Bao gồm cả cảnh báo Camera AI vào tổng vi phạm
        try {
            $camWhere = [
                'CAST(pv.THOI_DIEM AS DATE) >= :date_from',
                'CAST(pv.THOI_DIEM AS DATE) <= :date_to',
            ];
            $camParams = [
                'date_from' => $dateRange['date_from'],
                'date_to' => $dateRange['date_to'],
            ];
            if ($typeId) {
                $camWhere[] = 'pv.LOI_ID = :type_id';
                $camParams['type_id'] = $typeId;
            }
            $camFilterSql = ' WHERE ' . implode(' AND ', $camWhere);
            $camCountStmt = $pdo->prepare('SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM pv' . $camFilterSql);
            $camCountStmt->execute($camParams);
            $total += (int)$camCountStmt->fetchColumn();
        } catch (\Throwable) {}

        // 1. Theo loại vi phạm
        $byType = [];
        $typeSql = 'SELECT lv.ID as id, MAX(lv.TEN_LOI) as name, MAX(CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2)) as name_hex, COUNT(cv.ID) as count 
            FROM LOI_VIPHAM lv 
            LEFT JOIN CT_VIPHAM cv ON lv.ID = cv.ID_LOI AND cv.NGAY_VIPHAM >= :date_from AND cv.NGAY_VIPHAM <= :date_to';
        if ($typeId) {
            $typeSql .= ' AND lv.ID = :type_id';
        }
        if ($areaId) {
            $typeSql .= ' AND cv.KHU_VUC_ID = :area_id';
        }
        $typeSql .= ' GROUP BY lv.ID ORDER BY lv.ID';
        $typeStmt = $pdo->prepare($typeSql);
        $typeStmt->bindValue(':date_from', $dateRange['date_from']);
        $typeStmt->bindValue(':date_to', $dateRange['date_to']);
        if ($typeId) $typeStmt->bindValue(':type_id', $typeId, \PDO::PARAM_INT);
        if ($areaId) $typeStmt->bindValue(':area_id', $areaId, \PDO::PARAM_INT);
        $typeStmt->execute();
        while ($r = $typeStmt->fetch(\PDO::FETCH_ASSOC)) {
            $byType[] = [
                'id' => (int)$r['id'],
                'name' => $this->decodeHexUnicode($r['name_hex'] ?? null, $r['name']),
                'count' => (int)$r['count'],
            ];
        }

        // 2. Theo ngày
        $byDay = [];
        $daySql = 'SELECT cv.NGAY_VIPHAM as date, COUNT(*) as count 
            FROM CT_VIPHAM cv ' . $filterSql . ' 
            GROUP BY cv.NGAY_VIPHAM 
            ORDER BY cv.NGAY_VIPHAM ASC';
        $dayStmt = $pdo->prepare($daySql);
        $dayStmt->execute($params);
        while ($r = $dayStmt->fetch(\PDO::FETCH_ASSOC)) {
            $byDay[] = [
                'date' => date('Y-m-d', strtotime((string)$r['date'])),
                'count' => (int)$r['count'],
            ];
        }

        // 3. Theo khung giờ (Thật 100% từ THOI_DIEM)
        $hourCounts = array_fill(0, 24, 0);
        try {
            $hourSql = "SELECT DATEPART(HOUR, COALESCE(cv.THOI_DIEM, DATEADD(HOUR, 7, CAST(cv.NGAY_VIPHAM AS DATETIME2)))) as gio, COUNT(*) as count
                FROM CT_VIPHAM cv " . $filterSql . "
                GROUP BY DATEPART(HOUR, COALESCE(cv.THOI_DIEM, DATEADD(HOUR, 7, CAST(cv.NGAY_VIPHAM AS DATETIME2))))";
            $hourStmt = $pdo->prepare($hourSql);
            $hourStmt->execute($params);
            while ($hr = $hourStmt->fetch(\PDO::FETCH_ASSOC)) {
                $h = (int)$hr['gio'];
                if ($h >= 0 && $h < 24) $hourCounts[$h] = (int)$hr['count'];
            }
        } catch (\Throwable) {}

        $byHour = [];
        foreach ([6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18] as $h) {
            $byHour[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00 - %02d:59', $h, $h),
                'count' => $hourCounts[$h] ?? 0,
            ];
        }

        // 4. Theo khu vực (Thật 100% từ KHU_VUC & CT_VIPHAM)
        $byArea = [];
        try {
            $areaSql = "SELECT k.ID as id, k.TEN_KHU_VUC as name, CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as name_hex, COUNT(cv.ID) as count
                FROM KHU_VUC k
                LEFT JOIN CT_VIPHAM cv ON cv.KHU_VUC_ID = k.ID AND cv.NGAY_VIPHAM >= :date_from AND cv.NGAY_VIPHAM <= :date_to
                GROUP BY k.ID, k.TEN_KHU_VUC ORDER BY count DESC";
            $areaStmt = $pdo->prepare($areaSql);
            $areaStmt->bindValue(':date_from', $dateRange['date_from']);
            $areaStmt->bindValue(':date_to', $dateRange['date_to']);
            $areaStmt->execute();
            while ($ar = $areaStmt->fetch(\PDO::FETCH_ASSOC)) {
                $byArea[] = [
                    'id' => (int)$ar['id'],
                    'name' => $this->decodeHexUnicode($ar['name_hex'] ?? null, $ar['name']),
                    'count' => (int)$ar['count'],
                ];
            }

            // Đếm các bản ghi chưa gắn khu vực cụ thể (Khu vực chung)
            $unassignedSql = "SELECT COUNT(*) as unassigned_count FROM CT_VIPHAM cv 
                WHERE cv.KHU_VUC_ID IS NULL AND cv.NGAY_VIPHAM >= :date_from AND cv.NGAY_VIPHAM <= :date_to";
            $unStmt = $pdo->prepare($unassignedSql);
            $unStmt->bindValue(':date_from', $dateRange['date_from']);
            $unStmt->bindValue(':date_to', $dateRange['date_to']);
            $unStmt->execute();
            $unRow = $unStmt->fetch(\PDO::FETCH_ASSOC);
            $unCount = (int)($unRow['unassigned_count'] ?? 0);
            if ($unCount > 0) {
                $byArea[] = [
                    'id' => 0,
                    'name' => 'Khu vực chung',
                    'count' => $unCount,
                ];
                usort($byArea, fn($a, $b) => $b['count'] <=> $a['count']);
            }
        } catch (\Throwable) {}

        if (empty($byArea)) {
            $byArea = [
                ['id' => 1, 'name' => 'Cổng chính', 'count' => 0],
                ['id' => 2, 'name' => 'Sân trường', 'count' => 0],
                ['id' => 3, 'name' => 'Nhà giữ xe', 'count' => 0],
            ];
        }

        // 5. Theo thứ trong tuần (Thật 100%)
        $weekdayNames = [
            1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'
        ];
        $weekdayCounts = array_fill(1, 7, 0);
        try {
            $wSql = "SELECT DATEPART(WEEKDAY, cv.NGAY_VIPHAM) as thu, COUNT(*) as count
                FROM CT_VIPHAM cv " . $filterSql . "
                GROUP BY DATEPART(WEEKDAY, cv.NGAY_VIPHAM)";
            $wStmt = $pdo->prepare($wSql);
            $wStmt->execute($params);
            while ($wr = $wStmt->fetch(\PDO::FETCH_ASSOC)) {
                $wIdx = (int)$wr['thu']; // SQL Server default: 1 = Sunday, 2 = Monday...
                $mappedIdx = ($wIdx === 1) ? 7 : ($wIdx - 1);
                $weekdayCounts[$mappedIdx] = (int)$wr['count'];
            }
        } catch (\Throwable) {}

        $byWeekday = [];
        foreach ([1, 2, 3, 4, 5, 6, 7] as $dNum) {
            $byWeekday[] = [
                'name' => $weekdayNames[$dNum],
                'count' => $weekdayCounts[$dNum] ?? 0,
            ];
        }

        // 6. Điểm nóng (Hotspots: Phòng học/Vị trí • Khung giờ)
        $hotspots = [];
        try {
            $hotSql = "SELECT TOP 5 
                          COALESCE(k.TEN_KHU_VUC, 'DEFAULT_CAMPUS_AREA') as place,
                          CONVERT(VARCHAR(MAX), CAST(COALESCE(k.TEN_KHU_VUC, 'DEFAULT_CAMPUS_AREA') AS VARBINARY(MAX)), 2) as place_hex,
                          DATEPART(HOUR, COALESCE(cv.THOI_DIEM, DATEADD(HOUR, 7, CAST(cv.NGAY_VIPHAM AS DATETIME2)))) as gio,
                          COUNT(*) as count
                       FROM CT_VIPHAM cv
                       LEFT JOIN KHU_VUC k ON cv.KHU_VUC_ID = k.ID
                       " . $filterSql . "
                       GROUP BY COALESCE(k.TEN_KHU_VUC, 'DEFAULT_CAMPUS_AREA'),
                                DATEPART(HOUR, COALESCE(cv.THOI_DIEM, DATEADD(HOUR, 7, CAST(cv.NGAY_VIPHAM AS DATETIME2))))
                       ORDER BY count DESC";
            $hotStmt = $pdo->prepare($hotSql);
            $hotStmt->execute($params);
            while ($hr = $hotStmt->fetch(\PDO::FETCH_ASSOC)) {
                $rawPlace = $this->decodeHexUnicode($hr['place_hex'] ?? null, $hr['place']);
                $pName = ($rawPlace === 'DEFAULT_CAMPUS_AREA' || empty($rawPlace)) ? 'Khu vực chung' : $rawPlace;
                $hVal = (int)$hr['gio'];
                $hotspots[] = [
                    'area_name' => $pName,
                    'label' => sprintf('%s • %02d:00 - %02d:59', $pName, $hVal, $hVal),
                    'hour' => $hVal,
                    'count' => (int)$hr['count'],
                ];
            }
        } catch (\Throwable) {}


        $peakHourObj = null;
        $maxHCount = -1;
        foreach ($byHour as $bh) {
            if ($bh['count'] > $maxHCount && $bh['count'] > 0) {
                $maxHCount = $bh['count'];
                $peakHourObj = ['label' => $bh['label'], 'count' => $bh['count']];
            }
        }

        $peakAreaObj = null;
        $maxACount = -1;
        foreach ($byArea as $ba) {
            if ($ba['count'] > $maxACount && $ba['count'] > 0) {
                $maxACount = $ba['count'];
                $peakAreaObj = ['name' => $ba['name'], 'count' => $ba['count']];
            }
        }

        return [
            'total' => $total,
            'by_day' => $byDay,
            'by_type' => $byType,
            'by_hour' => $byHour,
            'by_area' => $byArea,
            'hotspots' => $hotspots,
            'peak_hour' => $peakHourObj,
            'peak_area' => $peakAreaObj,
            'by_status' => [
                'DA_XU_LY' => $total,
                'BO_QUA' => 0,
                'CHO_XU_LY' => 0,
            ],
            'by_weekday' => $byWeekday,
        ];
    }

    protected function handleDashboard(Request $request): Response
    {
        try {
            $pdo = $this->getPdo();
            $dateRange = $this->resolveDateRange($request, 'month');
            $summary = $this->getSummaryData($pdo, $request, $dateRange);

            // NT-10: KPI bộ đếm thực tế (hợp nhất cả CT_VIPHAM và PHAT_HIEN_VIPHAM)
            $todayStr = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $weekEnd = date('Y-m-d', strtotime('sunday this week'));
            $monthStart = date('Y-m-01');

            $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);

            if ($areaId) {
                $tStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM = :d AND KHU_VUC_ID = :area) + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) = :d2)");
                $tStmt->execute(['d' => $todayStr, 'd2' => $todayStr, 'area' => $areaId]);
                $cToday = (int)$tStmt->fetchColumn();

                $wStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM >= :w1 AND NGAY_VIPHAM <= :w2 AND KHU_VUC_ID = :area) + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) >= :w3 AND CAST(THOI_DIEM AS DATE) <= :w4)");
                $wStmt->execute(['w1' => $weekStart, 'w2' => $weekEnd, 'w3' => $weekStart, 'w4' => $weekEnd, 'area' => $areaId]);
                $cWeek = (int)$wStmt->fetchColumn();
            } else {
                $cToday = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM = '{$todayStr}') + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) = '{$todayStr}')")->fetchColumn();
                $cWeek = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM >= '{$weekStart}' AND NGAY_VIPHAM <= '{$weekEnd}') + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) >= '{$weekStart}' AND CAST(THOI_DIEM AS DATE) <= '{$weekEnd}')")->fetchColumn();
            }

            // Đếm số lượng vi phạm cho thẻ Tháng dựa trên bộ lọc nếu người dùng lọc theo tháng
            if ($dateRange['period'] === 'month') {
                $mFrom = $dateRange['date_from'];
                $mTo = $dateRange['date_to'];
                $cMonthStmt = $pdo->prepare("SELECT 
                    (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM >= :m_from AND NGAY_VIPHAM <= :m_to" . ($areaId ? " AND KHU_VUC_ID = :area1" : "") . ") 
                    + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) >= :m_from2 AND CAST(THOI_DIEM AS DATE) <= :m_to2)");
                $cMonthParams = ['m_from' => $mFrom, 'm_to' => $mTo, 'm_from2' => $mFrom, 'm_to2' => $mTo];
                if ($areaId) {
                    $cMonthParams['area1'] = $areaId;
                }
                $cMonthStmt->execute($cMonthParams);
                $cMonth = (int)$cMonthStmt->fetchColumn();
            } else {
                $cMonth = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM CT_VIPHAM WHERE NGAY_VIPHAM >= '{$monthStart}') + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) >= '{$monthStart}')")->fetchColumn();
            }

            // Học kỳ: Nếu người dùng lọc theo học kỳ cụ thể
            if ($dateRange['period'] === 'semester' && !empty($dateRange['academic_year']) && !empty($dateRange['term'])) {
                $currentSemYear = $dateRange['academic_year'];
                $currentSemTerm = (int)$dateRange['term'];
                $currentSemName = $dateRange['label'];
                $cSemStart = $dateRange['date_from'];
                $cSemEnd = $dateRange['date_to'];
            } else {
                $semStmt = $pdo->prepare('SELECT TOP 1 NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY WHERE :today BETWEEN NGAY_BAT_DAU AND NGAY_KET_THUC ORDER BY NGAY_BAT_DAU DESC');
                $semStmt->execute(['today' => $todayStr]);
                $currentSemRow = $semStmt->fetch(\PDO::FETCH_ASSOC);
                if (!$currentSemRow) {
                    $currentSemRow = $pdo->query('SELECT TOP 1 NAM_HOC, HOC_KY, NGAY_BAT_DAU, NGAY_KET_THUC FROM HOCKY ORDER BY NGAY_BAT_DAU DESC')->fetch(\PDO::FETCH_ASSOC);
                }
                $currentSemYear = $currentSemRow ? trim((string)$currentSemRow['NAM_HOC']) : '2026-2027';
                $currentSemTerm = $currentSemRow ? (int)$currentSemRow['HOC_KY'] : 1;
                $currentSemName = $currentSemRow ? "Học kỳ {$currentSemTerm} ({$currentSemYear})" : 'Học kỳ 1 (2026-2027)';
                $cSemStart = $currentSemRow ? $currentSemRow['NGAY_BAT_DAU'] : '2026-08-15';
                $cSemEnd = $currentSemRow ? $currentSemRow['NGAY_KET_THUC'] : '2027-01-15';
            }

            $cSemesterStmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM CT_VIPHAM
                                             WHERE ((NAM_HOC = :y AND HOC_KY = :t)
                                                OR (NAM_HOC IS NULL AND NGAY_VIPHAM BETWEEN :start AND :end))" . ($areaId ? " AND KHU_VUC_ID = :area1" : "") . ")
                                                + (SELECT COUNT(*) FROM PHAT_HIEN_VIPHAM WHERE CAST(THOI_DIEM AS DATE) BETWEEN :start2 AND :end2)");
            $cSemParams = [
                'y' => $currentSemYear,
                't' => $currentSemTerm,
                'start' => $cSemStart,
                'end' => $cSemEnd,
                'start2' => $cSemStart,
                'end2' => $cSemEnd,
            ];
            if ($areaId) {
                $cSemParams['area1'] = $areaId;
            }
            $cSemesterStmt->execute($cSemParams);
            $cSemester = (int)$cSemesterStmt->fetchColumn();

            $isCurrentMonth = ($dateRange['period'] === 'month' && $dateRange['date_from'] === date('Y-m-01'));
            $monthLabel = ($dateRange['period'] === 'month') ? $dateRange['label'] : ('Tháng ' . date('m/Y'));

            $data = [
                'counters' => [
                    'today' => $cToday,
                    'week' => $cWeek,
                    'month' => $cMonth,
                    'semester' => $cSemester,
                ],
                'month_label' => $monthLabel,
                'is_current_month' => $isCurrentMonth,
                'current_semester' => ['name' => $currentSemName],
                'range' => ['label' => $dateRange['label'], 'period' => $dateRange['period']],
                'summary' => $summary,
            ];

            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi tải trang tổng quan: ' . $e->getMessage()], 500);
        }
    }

    protected function handleReportSummary(Request $request): Response
    {
        try {
            $pdo = $this->getPdo();
            $dateRange = $this->resolveDateRange($request, 'week');
            $summary = $this->getSummaryData($pdo, $request, $dateRange);

            $filtersList = [
                ['label' => 'Kỳ báo cáo', 'value' => $dateRange['label']],
            ];

            $typeId = filter_var($request->query('type_id'), FILTER_VALIDATE_INT);
            if ($typeId) {
                $tStmt = $pdo->prepare("SELECT TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) as hex FROM LOI_VIPHAM WHERE ID = :id");
                $tStmt->execute(['id' => $typeId]);
                $tRow = $tStmt->fetch(\PDO::FETCH_ASSOC);
                if ($tRow) {
                    $filtersList[] = ['label' => 'Loại vi phạm', 'value' => $this->decodeHexUnicode($tRow['hex'] ?? null, $tRow['TEN_LOI'])];
                }
            } else {
                $filtersList[] = ['label' => 'Loại vi phạm', 'value' => 'Tất cả'];
            }

            $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);
            if ($areaId) {
                $areaNames = [1 => 'Cổng chính', 2 => 'Sân trường', 3 => 'Nhà giữ xe'];
                $filtersList[] = ['label' => 'Khu vực', 'value' => $areaNames[$areaId] ?? "Khu vực #{$areaId}"];
            }

            $data = [
                'range' => ['label' => $dateRange['label']],
                'filters' => $filtersList,
                'summary' => $summary,
            ];

            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi tải báo cáo: ' . $e->getMessage()], 500);
        }
    }

    protected function handleReportExport(Request $request): Response
    {
        try {
            $pdo = $this->getPdo();
            $format = strtolower((string)$request->query('format', 'pdf'));
            $dateRange = $this->resolveDateRange($request, 'week');
            $summary = $this->getSummaryData($pdo, $request, $dateRange);

            // Thu thập chi tiết các vi phạm trong kỳ (tối đa 200 bản ghi mới nhất để đưa vào báo cáo)
            $where = [];
            $params = [];
            if (!empty($dateRange['date_from'])) {
                $where[] = 'cv.NGAY_VIPHAM >= :df';
                $params['df'] = $dateRange['date_from'];
            }
            if (!empty($dateRange['date_to'])) {
                $where[] = 'cv.NGAY_VIPHAM <= :dt';
                $params['dt'] = $dateRange['date_to'];
            }
            $typeId = filter_var($request->query('type_id'), FILTER_VALIDATE_INT);
            if ($typeId) {
                $where[] = 'cv.ID_LOI = :type_id';
                $params['type_id'] = $typeId;
            }
            $areaId = filter_var($request->query('area_id'), FILTER_VALIDATE_INT);
            if ($areaId) {
                $where[] = 'cv.KHU_VUC_ID = :area_id';
                $params['area_id'] = $areaId;
            }
            $filterSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT TOP 200 
                        cv.ID,
                        cv.NGAY_VIPHAM,
                        cv.THOI_DIEM,
                        cv.KHU_VUC_ID,
                        k.TEN_KHU_VUC,
                        CONVERT(VARCHAR(MAX), CAST(k.TEN_KHU_VUC AS VARBINARY(MAX)), 2) as area_name_hex,
                        lv.TEN_LOI,
                        CONVERT(VARCHAR(MAX), CAST(lv.TEN_LOI AS VARBINARY(MAX)), 2) as type_name_hex,
                        sv.MA_SV,
                        sv.HOTEN,
                        CONVERT(VARCHAR(MAX), CAST(sv.HOTEN AS VARBINARY(MAX)), 2) as sv_name_hex,
                        l.MA_LOP
                    FROM CT_VIPHAM cv
                    LEFT JOIN LOI_VIPHAM lv ON cv.ID_LOI = lv.ID
                    LEFT JOIN SINHVIEN sv ON cv.ID_SV = sv.ID
                    LEFT JOIN LOP l ON sv.LOP_ID = l.ID
                    LEFT JOIN KHU_VUC k ON cv.KHU_VUC_ID = k.ID
                    " . $filterSql . "
                    ORDER BY COALESCE(cv.THOI_DIEM, CAST(cv.NGAY_VIPHAM AS DATETIME2)) DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $details = [];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $timeStr = !empty($r['THOI_DIEM']) ? date('Y-m-d H:i:s', strtotime((string)$r['THOI_DIEM'])) : (!empty($r['NGAY_VIPHAM']) ? date('Y-m-d 07:30:00', strtotime((string)$r['NGAY_VIPHAM'])) : date('Y-m-d H:i:s'));
                $areaStr = $this->decodeHexUnicode($r['area_name_hex'] ?? null, $r['TEN_KHU_VUC'] ?? 'Khu vực chung');
                $details[] = [
                    'id' => (int)$r['ID'],
                    'occurred_at' => $timeStr,
                    'type_name' => $this->decodeHexUnicode($r['type_name_hex'] ?? null, $r['TEN_LOI'] ?? 'Vi phạm tác phong'),
                    'student_code' => (string)($r['MA_SV'] ?? '—'),
                    'student_name' => $this->decodeHexUnicode($r['sv_name_hex'] ?? null, $r['HOTEN'] ?? '—'),
                    'class_name' => (string)($r['MA_LOP'] ?? '—'),
                    'location' => $areaStr,
                ];
            }

            // Danh sách bộ lọc áp dụng
            $filtersList = [
                ['label' => 'Kỳ báo cáo', 'value' => $dateRange['label']],
            ];
            if ($typeId) {
                $tStmt = $pdo->prepare("SELECT TEN_LOI, CONVERT(VARCHAR(MAX), CAST(TEN_LOI AS VARBINARY(MAX)), 2) as hex FROM LOI_VIPHAM WHERE ID = :id");
                $tStmt->execute(['id' => $typeId]);
                $tRow = $tStmt->fetch(\PDO::FETCH_ASSOC);
                $filtersList[] = [
                    'label' => 'Loại vi phạm',
                    'value' => $tRow ? $this->decodeHexUnicode($tRow['hex'] ?? null, $tRow['TEN_LOI']) : "Mã #{$typeId}",
                ];
            } else {
                $filtersList[] = ['label' => 'Loại vi phạm', 'value' => 'Tất cả'];
            }
            if ($areaId) {
                $areaNames = [1 => 'Cổng chính', 2 => 'Sân trường', 3 => 'Nhà giữ xe'];
                $filtersList[] = [
                    'label' => 'Khu vực',
                    'value' => $areaNames[$areaId] ?? "Khu vực #{$areaId}",
                ];
            }

            if ($format === 'pdf') {
                $payload = [
                    'title' => 'BÁO CÁO GIÁM SÁT TÁC PHONG',
                    'range_label' => $dateRange['label'],
                    'period' => $dateRange['label'],
                    'date_range' => (!empty($dateRange['date_from']) && !empty($dateRange['date_to'])) ? ('Từ ' . $dateRange['date_from'] . ' đến ' . $dateRange['date_to']) : $dateRange['label'],
                    'author' => (string)($request->session()->get('user.full_name') ?? $request->session()->get('username') ?? 'Phòng Công tác Sinh viên'),
                    'filters' => $filtersList,
                    'summary' => $summary,
                    'details' => $details,
                ];

                $token = $request->header('Authorization');
                if ($token && str_starts_with($token, 'Bearer ')) {
                    $token = substr($token, 7);
                }

                @set_time_limit(180);
                $res = $this->apiClient->request('POST', '/api/v1/reports/ai-pdf', [], $payload, $token, 150);


                if ($res['status'] === 200 && !empty($res['body'])) {
                    $stamp = (!empty($dateRange['date_from']) && !empty($dateRange['date_to']))
                        ? (date('d_m', strtotime((string)$dateRange['date_from'])) . ' - ' . date('d_m_Y', strtotime((string)$dateRange['date_to'])))
                        : date('d_m_Y_His');
                    $filename = "Bao cao Giam sat Tac phong Sinh vien ({$stamp}).pdf";

                    return response($res['body'], 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        'Cache-Control' => 'no-cache, private',
                    ]);
                }

                $errMsg = 'Máy chủ AI sinh PDF trả về lỗi: ' . ($res['body'] ?? 'Không nhận được phản hồi');
                $decodedErr = json_decode((string)$res['body'], true);
                if (is_array($decodedErr) && !empty($decodedErr['detail'])) {
                    $errMsg = 'Lỗi AI PDF: ' . (is_string($decodedErr['detail']) ? $decodedErr['detail'] : json_encode($decodedErr['detail'], JSON_UNESCAPED_UNICODE));
                }

                return response()->json([
                    'error' => $errMsg,
                    'status' => $res['status']
                ], $res['status'] >= 400 ? $res['status'] : 500);
            }

            // Fallback xuất CSV khi format là csv / xlsx
            $filename = 'BaoCao_ViPham_' . date('Ymd_His') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($details, $summary, $dateRange) {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
                fputcsv($handle, ['BÁO CÁO VI PHẠM TÁC PHONG SINH VIÊN']);
                fputcsv($handle, ['Kỳ báo cáo:', $dateRange['label']]);
                fputcsv($handle, ['Tổng số vi phạm:', $summary['total']]);
                fputcsv($handle, []);
                fputcsv($handle, ['STT', 'Thời gian', 'Mã SV', 'Họ tên', 'Lớp', 'Loại vi phạm', 'Vị trí / Phòng học']);
                foreach ($details as $idx => $d) {
                    fputcsv($handle, [
                        $idx + 1,
                        $d['occurred_at'],
                        $d['student_code'],
                        $d['student_name'],
                        $d['class_name'],
                        $d['type_name'],
                        $d['location'],
                    ]);
                }
                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Lỗi xuất báo cáo: ' . $e->getMessage()], 500);
        }
    }
}

