<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CimRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = (string)$request->session()->get('role', '');

        if (!in_array($userRole, $roles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'Bạn không có quyền thực hiện thao tác này.',
                ], 403);
            }

            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
