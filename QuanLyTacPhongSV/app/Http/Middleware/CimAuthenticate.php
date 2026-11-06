<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\CatalogService;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CimAuthenticate
{
    private const ACCOUNT_RECHECK_SECONDS = 60;

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $authenticated = $session->get('authenticated') === true;
        $role = $session->get('role');
        $expiresAt = (int)$session->get('token_expires_at', 0);
        $lastActivity = (int)$session->get('last_activity', 0);

        $idleMinutes = (int)config('cim.session_idle_minutes');
        $idleSeconds = max(1, $idleMinutes) * 60;
        $now = time();

        $validRoles = ['ADMIN', 'CTSV'];

        $valid = $authenticated && in_array($role, $validRoles, true) && $expiresAt > $now
            && ($lastActivity + $idleSeconds) >= $now && $this->accountStillValid($session, $now);

        if (!$valid) {
            $session->flush();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Phiên đăng nhập đã hết hạn hoặc chưa đăng nhập.',
                ], 401);
            }

            $currentUrl = $request->getRequestUri();
            $nextParam = ltrim($currentUrl, '/');
            return redirect()->guest(route('login', ['next' => $nextParam]));
        }

        // Không làm mới last_activity đối với các request polling chạy nền
        if (!$request->headers->has('X-CIM-Background')) {
            $session->put('last_activity', $now);
        }

        $response = $next($request);

        // Chống lưu cache trình duyệt cho các trang nội bộ (AU-11: nhấn Back sau khi Logout không xem lại được trang cũ)
        if (!$request->is('api/*') && !$request->expectsJson()) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        }

        return $response;
    }

    /**
     * Kiểm tra tức thì trạng thái tài khoản trên mỗi request: bị vô hiệu hóa (AC-06), đổi quyền
     * hoặc đặt lại mật khẩu (TOKEN_VERSION tăng) thì phiên hiện tại bị thu hồi ngay ở request kế tiếp.
     */
    protected function accountStillValid(Session $session, int $now): bool
    {
        $userId = (int)$session->get('user_id', 0);
        if ($userId <= 0) {
            return true;
        }
        try {
            $stmt = app(CatalogService::class)->getPdo()
                ->prepare('SELECT ROLE, ACTIVE, TOKEN_VERSION FROM TAIKHOAN WHERE ID = :id');
            $stmt->execute(['id' => $userId]);
            $account = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // CSDL tạm mất kết nối: giữ phiên, các trang cần dữ liệu sẽ tự báo lỗi.
            report($e);
            return true;
        }
        if (!$account || !$account['ACTIVE'] || (string)$account['ROLE'] !== (string)$session->get('role')) {
            return false;
        }
        $version = (int)$account['TOKEN_VERSION'];
        if ($session->has('token_version') && (int)$session->get('token_version') !== $version) {
            return false;
        }
        $session->put(['token_version' => $version, 'account_checked_at' => $now]);
        return true;
    }
}
