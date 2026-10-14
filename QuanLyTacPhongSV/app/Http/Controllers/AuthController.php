<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CimApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected CimApiClient $apiClient
    ) {}

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

        [$status, $body] = $this->apiClient->json('POST', '/api/v1/auth/login', [], [
            'username' => $username,
            'password' => $password,
        ]);

        if ($status !== 200 || !isset($body['data']['access_token'], $body['data']['user'])) {
            $errorMsg = $this->apiClient->errorMessage($body, 'Không thể đăng nhập lúc này. Vui lòng thử lại.');
            return view('auth.login', [
                'next' => $next,
                'notice' => null,
                'error' => $errorMsg,
                'username' => $username,
            ]);
        }

        $user = $body['data']['user'];
        $role = $user['role'] ?? null;
        if (!in_array($role, ['ADMIN', 'CTSV'], true)) {
            return view('auth.login', [
                'next' => $next,
                'notice' => null,
                'error' => 'Tài khoản không có vai trò hợp lệ để đăng nhập hệ thống.',
                'username' => $username,
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'authenticated' => true,
            'user_id' => (int)$user['id'],
            'username' => (string)$user['username'],
            'display_name' => (string)($user['full_name'] ?? $user['username']),
            'role' => $role,
            'api_token' => (string)$body['data']['access_token'],
            'token_expires_at' => (int)$body['data']['expires_at'],
            'last_activity' => time(),
        ]);

        return redirect()->intended($next);
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

        $token = (string)$request->session()->get('api_token', '');
        [$status, $body] = $this->apiClient->json('POST', '/api/v1/auth/change-password', [], [
            'current_password' => $current,
            'new_password' => $new,
        ], $token);

        if ($status === 200) {
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login', ['password_changed' => 1]);
        }

        if ($status === 401) {
            $request->session()->flush();
            return redirect()->route('login');
        }

        $errorMsg = $this->apiClient->errorMessage($body, 'Không thể đổi mật khẩu.');
        if (!empty($body['error']['details']) && is_array($body['error']['details'])) {
            $errorMsg .= ' ' . implode(' ', array_filter($body['error']['details'], 'is_string'));
        }

        return view('auth.change_password', [
            'error' => $errorMsg,
            'pageTitle' => 'Đổi mật khẩu',
        ]);
    }

    protected function safeNext(?string $next): string
    {
        if (!is_string($next) || $next === '' || $next === 'login' || $next === 'login.php') {
            return route('dashboard');
        }
        if (preg_match('/^[a-zA-Z0-9_\-\.\/]+(\?[A-Za-z0-9_=&%-]*)?$/', $next) !== 1 || str_contains($next, '//') || str_contains($next, ':')) {
            return route('dashboard');
        }
        return url($next);
    }
}
