<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebPortalTest extends TestCase
{
    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Hệ thống quản lý tác phong sinh viên');
        $response->assertSee('Đăng nhập');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login?next=home');

        $responseViolations = $this->get('/list-vipham');
        $responseViolations->assertRedirect('/login?next=list-vipham');
    }

    public function test_legacy_php_urls_redirect(): void
    {
        $this->get('/login.php')->assertRedirect('/login');
        $this->get('/login.php?next=list-vipham')->assertRedirect('/login?next=list-vipham');
        $this->get('/home.php')->assertRedirect('/home');
        $this->get('/list_vipham.php')->assertRedirect('/list-vipham');
        $this->get('/ql_sinhvien.php')->assertRedirect('/ql-sinhvien');
    }

    public function test_logout_get_redirects_to_dashboard(): void
    {
        $this->get('/logout')->assertRedirect('/home');
        $this->get('/logout.php')->assertRedirect('/home');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->withSession([
            'authenticated' => true,
            'role' => 'CTSV',
            'username' => 'giàmthị1',
            'display_name' => 'Nguyễn Văn A',
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
            'account_checked_at' => time(), // phiên vừa xác thực, middleware chưa cần tra lại CSDL
        ])->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Tổng quan vi phạm tác phong');
        $this->assertStringContainsString('no-store', (string)$response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string)$response->headers->get('Cache-Control'));
    }

    public function test_unauthenticated_api_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/cim.php?r=health');
        $response->assertStatus(401);
        $response->assertJsonStructure(['error']);
    }

    public function test_api_state_change_without_csrf_returns_403_json(): void
    {
        $response = $this->withSession([
            'authenticated' => true,
            'role' => 'ADMIN',
            'username' => 'admin',
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
        ])->postJson('/api/cim.php?r=areas', ['code' => 'TEST', 'name' => 'Khu Test']);

        $response->assertStatus(403);
        $response->assertJsonStructure(['error']);
    }

    public function test_password_strength_validator(): void
    {
        $short = \App\Http\Controllers\AuthController::validatePasswordStrength('Test1');
        $this->assertNotEmpty($short);

        $noUpper = \App\Http\Controllers\AuthController::validatePasswordStrength('testpass123');
        $this->assertNotEmpty($noUpper);

        $noNumber = \App\Http\Controllers\AuthController::validatePasswordStrength('TestPassWord');
        $this->assertNotEmpty($noNumber);

        $valid = \App\Http\Controllers\AuthController::validatePasswordStrength('TestPass123');
        $this->assertEmpty($valid);
    }

    public function test_auth_controller_safe_next(): void
    {
        $controller = app(\App\Http\Controllers\AuthController::class);
        $this->assertEquals(url('list-vipham'), $controller->safeNext('list-vipham'));
        $this->assertEquals(url('list_vipham.php'), $controller->safeNext('list_vipham.php'));
        $this->assertEquals(route('dashboard'), $controller->safeNext('home'));
        $this->assertEquals(route('dashboard'), $controller->safeNext('//evil.com'));
        $this->assertEquals(route('dashboard'), $controller->safeNext('javascript:alert(1)'));
    }

    public function test_admin_cannot_self_deactivate(): void
    {
        $response = $this->withSession([
            'authenticated' => true,
            'role' => 'ADMIN',
            'user_id' => 1,
            'username' => 'admin',
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
            '_token' => 'mock_csrf',
        ])->putJson('/api/cim.php?r=account&id=1', [
            'full_name' => 'Admin Name',
            'role' => 'ADMIN',
            'active' => false,
        ], ['X-CSRF-Token' => 'mock_csrf']);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Không thể tự vô hiệu hóa tài khoản đang đăng nhập.']);
    }

    public function test_admin_cannot_self_demote(): void
    {
        $response = $this->withSession([
            'authenticated' => true,
            'role' => 'ADMIN',
            'user_id' => 1,
            'username' => 'admin',
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
            '_token' => 'mock_csrf',
        ])->putJson('/api/cim.php?r=account&id=1', [
            'full_name' => 'Admin Name',
            'role' => 'CTSV',
            'active' => true,
        ], ['X-CSRF-Token' => 'mock_csrf']);

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Không thể tự hạ quyền tài khoản đang đăng nhập.']);
    }
}
