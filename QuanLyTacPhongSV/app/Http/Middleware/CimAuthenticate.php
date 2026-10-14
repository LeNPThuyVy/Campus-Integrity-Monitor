<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CimAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();
        $authenticated = $session->get('authenticated') === true;
        $role = $session->get('role');
        $expiresAt = (int)$session->get('token_expires_at', 0);
        $lastActivity = (int)$session->get('last_activity', 0);

        $idleMinutes = (int)env('CIM_WEB_SESSION_IDLE_MINUTES', 30);
        $idleSeconds = max(5, $idleMinutes) * 60;
        $now = time();

        $validRoles = ['ADMIN', 'CTSV'];

        if (!$authenticated || !in_array($role, $validRoles, true) || $expiresAt <= $now || ($lastActivity + $idleSeconds) < $now) {
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
}
