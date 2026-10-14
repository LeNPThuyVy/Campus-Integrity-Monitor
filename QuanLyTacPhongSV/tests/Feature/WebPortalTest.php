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
        $this->get('/home.php')->assertRedirect('/home');
        $this->get('/list_vipham.php')->assertRedirect('/list-vipham');
        $this->get('/ql_sinhvien.php')->assertRedirect('/ql-sinhvien');
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
        ])->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Tổng quan vi phạm tác phong');
    }

    public function test_unauthenticated_api_request_returns_401_json(): void
    {
        $response = $this->getJson('/api/cim.php?r=health');
        $response->assertStatus(401);
        $response->assertJsonStructure(['error']);
    }
}
