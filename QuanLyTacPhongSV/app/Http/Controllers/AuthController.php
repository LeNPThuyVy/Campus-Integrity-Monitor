<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CimApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PDO;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected CimApiClient $apiClient
    ) {}

    protected function getPdo(): PDO
    {
        if (extension_loaded('pdo_sqlsrv')) {
            try {
                return DB::connection('sqlsrv')->getPdo();
            } catch (Throwable) {
                // Tiếp tục thử kết nối trực tiếp
            }
        }

        $host = (string)env('DB_SERVER', env('DB_HOST', 'localhost\\SQLEXPRESS'));
        $database = (string)env('DB_NAME', env('DB_DATABASE', 'CampusIntegrityMonitor'));
        $trusted = filter_var(env('DB_TRUSTED_CONNECTION', true), FILTER_VALIDATE_BOOLEAN);
        $username = (string)env('DB_USER', env('DB_USERNAME', 'sa'));
        $password = (string)env('DB_PASSWORD', '12345');

        if (extension_loaded('pdo_odbc')) {
            $odbcDrivers = ['ODBC Driver 17 for SQL Server', 'ODBC Driver 18 for SQL Server', 'SQL Server'];
            foreach ($odbcDrivers as $drv) {
                try {
                    if ($trusted) {
                        $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;Trusted_Connection=yes;', $drv, $host, $database);
                        return new PDO($dsn, null, null, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                        ]);
                    }

                    $dsn = sprintf('odbc:Driver={%s};Server=%s;Database=%s;', $drv, $host, $database);
                    return new PDO($dsn, $username, $password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        if (extension_loaded('pdo_sqlsrv')) {
            $encrypt = env('DB_ENCRYPT', 'yes');
            $trustCert = env('DB_TRUST_SERVER_CERTIFICATE', 'yes');
            $dsn = sprintf('sqlsrv:Server=%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s', $host, $database, $encrypt, $trustCert);
            return new PDO($dsn, $trusted ? null : $username, $trusted ? null : $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        throw new \RuntimeException('Không tìm thấy driver PDO tương thích (pdo_odbc hoặc pdo_sqlsrv) để kết nối SQL Server.');
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('authenticated') === true) {
            $next = $this->safeNext($request->query('next'));
            return redirect($next);
        }

        $notice = null;
        if ($request->has('logged_out')) {
            $notice = 'Bạn đã đăng xuất.';
        } elseif ($request->has('password_changed')) {
            $notice = 'Đã đổi mật khẩu. Vui lòng đăng nhập lại bằng mật khẩu mới.';
        }

        return view('auth.login', [
            'next' => $request->query('next', 'home'),
            'notice' => $notice,
            'error' => null,
        ]);
    }

    public function login(Request $request): View|RedirectResponse
    {
        $username = trim((string)$request->input('username', ''));
        $password = (string)$request->input('password', '');
        $next = $this->safeNext($request->input('next'));

        if ($username === '' || $password === '') {
            return view('auth.login', [
                'next' => $next,
                'notice' => null,
                'error' => 'Vui lòng nhập tên đăng nhập và mật khẩu.',
                'username' => $username,
            ]);
        }

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare('SELECT ID, USERNAME, PASSWORD_HASH, FULL_NAME, CONVERT(VARCHAR(MAX), CAST(FULL_NAME AS VARBINARY(MAX)), 2) AS FULL_NAME_HEX, ROLE, ACTIVE, LOCKED_UNTIL FROM TAIKHOAN WHERE USERNAME = :username');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if (!$user) {
                return view('auth.login', [
                    'next' => $next,
                    'notice' => null,
                    'error' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
                    'username' => $username,
                ]);
            }

            if (!$user['ACTIVE']) {
                return view('auth.login', [
                    'next' => $next,
                    'notice' => null,
                    'error' => 'Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.',
                    'username' => $username,
                ]);
            }

            if (!empty($user['LOCKED_UNTIL']) && strtotime((string)$user['LOCKED_UNTIL']) > time()) {
                return view('auth.login', [
                    'next' => $next,
                    'notice' => null,
                    'error' => 'Tài khoản đang bị tạm khóa. Vui lòng thử lại sau.',
                    'username' => $username,
                ]);
            }

            $pwdHash = (string)$user['PASSWORD_HASH'];
            $valid = password_verify($password, $pwdHash) || $password === $pwdHash;
            if (!$valid) {
                return view('auth.login', [
                    'next' => $next,
                    'notice' => null,
                    'error' => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
                    'username' => $username,
                ]);
            }

            // Cập nhật lại hash bcrypt nếu cần
            if ($password === $pwdHash || password_needs_rehash($pwdHash, PASSWORD_BCRYPT)) {
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $upd = $pdo->prepare('UPDATE TAIKHOAN SET PASSWORD_HASH = :hash WHERE ID = :id');
                $upd->execute(['hash' => $newHash, 'id' => $user['ID']]);
            }

            // Cập nhật lần đăng nhập gần nhất
            $updLogin = $pdo->prepare('UPDATE TAIKHOAN SET LAST_LOGIN_AT = GETDATE() WHERE ID = :id');
            $updLogin->execute(['id' => $user['ID']]);

            $role = (string)$user['ROLE'];
            if (!in_array($role, ['ADMIN', 'CTSV'], true)) {
                return view('auth.login', [
                    'next' => $next,
                    'notice' => null,
                    'error' => 'Tài khoản không có vai trò hợp lệ để đăng nhập hệ thống.',
                    'username' => $username,
                ]);
            }

            $fullNameDecoded = $this->decodeHexUnicode((string)($user['FULL_NAME_HEX'] ?? ''));
            $displayName = $fullNameDecoded !== '' ? $fullNameDecoded : (string)($user['FULL_NAME'] ?: $user['USERNAME']);

            $request->session()->regenerate();
            $request->session()->put([
                'authenticated' => true,
                'user_id' => (int)$user['ID'],
                'username' => (string)$user['USERNAME'],
                'display_name' => $displayName,
                'role' => $role,
                'api_token' => bin2hex(random_bytes(32)),
                'token_expires_at' => time() + (24 * 3600),
                'last_activity' => time(),
            ]);

            $request->session()->forget('url.intended');

            $target = ($next && $next !== 'home' && $next !== 'dashboard') ? $this->safeNext($next) : route('dashboard');
            return redirect()->to($target);
        } catch (Throwable $e) {
            return view('auth.login', [
                'next' => $next,
                'notice' => null,
                'error' => 'Lỗi kết nối CSDL: ' . $e->getMessage(),
                'username' => $username,
            ]);
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['logged_out' => 1]);
    }

    public function showChangePassword(): View
    {
        return view('auth.change_password', [
            'error' => null,
            'pageTitle' => 'Đổi mật khẩu',
        ]);
    }

    public function changePassword(Request $request): View|RedirectResponse
    {
        $current = (string)$request->input('current_password', '');
        $new = (string)$request->input('new_password', '');
        $confirm = (string)$request->input('confirm_password', '');

        if ($new === '' || strlen($new) < 8) {
            return view('auth.change_password', [
                'error' => 'Mật khẩu mới tối thiểu 8 ký tự.',
                'pageTitle' => 'Đổi mật khẩu',
            ]);
        }

        if ($new !== $confirm) {
            return view('auth.change_password', [
                'error' => 'Mật khẩu xác nhận không khớp.',
                'pageTitle' => 'Đổi mật khẩu',
            ]);
        }

        $userId = (int)$request->session()->get('user_id', 0);
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare('SELECT ID, PASSWORD_HASH FROM TAIKHOAN WHERE ID = :id');
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            if (!$user || (!password_verify($current, (string)$user['PASSWORD_HASH']) && $current !== $user['PASSWORD_HASH'])) {
                return view('auth.change_password', [
                    'error' => 'Mật khẩu hiện tại không đúng.',
                    'pageTitle' => 'Đổi mật khẩu',
                ]);
            }

            $newHash = password_hash($new, PASSWORD_BCRYPT);
            $upd = $pdo->prepare('UPDATE TAIKHOAN SET PASSWORD_HASH = :hash WHERE ID = :id');
            $upd->execute(['hash' => $newHash, 'id' => $userId]);

            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['password_changed' => 1]);
        } catch (Throwable $e) {
            return view('auth.change_password', [
                'error' => 'Lỗi khi đổi mật khẩu: ' . $e->getMessage(),
                'pageTitle' => 'Đổi mật khẩu',
            ]);
        }
    }

    protected function decodeHexUnicode(?string $hex): string
    {
        if (!$hex) {
            return '';
        }
        if (ctype_xdigit($hex) && strlen($hex) % 4 === 0) {
            $bin = @hex2bin($hex);
            if ($bin !== false) {
                $utf8 = @mb_convert_encoding($bin, 'UTF-8', 'UTF-16LE');
                if ($utf8 !== false && mb_check_encoding($utf8, 'UTF-8')) {
                    return $utf8;
                }
            }
        }
        return mb_check_encoding($hex, 'UTF-8') ? $hex : mb_convert_encoding($hex, 'UTF-8', 'Windows-1252');
    }

    protected function safeNext(?string $next): string
    {
        if (!is_string($next) || $next === '' || $next === 'home' || $next === 'dashboard' || $next === 'login' || $next === 'login.php') {
            return route('dashboard');
        }
        if (preg_match('/^[a-zA-Z0-9_\-\.\/]+(\?[A-Za-z0-9_=&%-]*)?$/', $next) !== 1 || str_contains($next, '//') || str_contains($next, ':')) {
            return route('dashboard');
        }
        return url($next);
    }
}
