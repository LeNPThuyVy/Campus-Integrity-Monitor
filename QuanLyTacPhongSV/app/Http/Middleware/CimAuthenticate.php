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
        $idleSeconds = max(5, $idleMinutes) * 60;
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

        // Update last activity
        $session->put('last_activity', $now);

        return $next($request);
    }

    /**
     * Mỗi ACCOUNT_RECHECK_SECONDS đọc lại tài khoản: bị vô hiệu, đổi quyền hoặc đặt lại mật khẩu
     * (TOKEN_VERSION tăng) thì phiên hiện tại bị thu hồi thay vì sống tiếp tới 24 giờ.
     */
    protected function accountStillValid(Session $session, int $now): bool
    {
        if ($now - (int)$session->get('account_checked_at', 0) < self::ACCOUNT_RECHECK_SECONDS) {
            return true;
        }
        try {
            $stmt = app(CatalogService::class)->getPdo()
                ->prepare('SELECT ROLE, ACTIVE, TOKEN_VERSION FROM TAIKHOAN WHERE ID = :id');
            $stmt->execute(['id' => (int)$session->get('user_id', 0)]);
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
