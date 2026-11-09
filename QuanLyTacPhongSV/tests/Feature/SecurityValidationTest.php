<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityValidationTest extends TestCase
{
    private function getAuthSession(string $role = 'ADMIN'): array
    {
        return [
            'authenticated' => true,
            'role' => $role,
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
            'user_id' => 0, // Bypass DB account recheck in tests
            'user' => [
                'username' => strtolower($role),
                'ho_ten' => $role === 'ADMIN' ? 'Administrator' : 'Cán bộ CTSV',
                'vai_tro' => $role,
            ],
            'api_token' => 'test-server-side-fastapi-token-xyz',
        ];
    }

    /**
     * NF-03: Session cookies must be HttpOnly and sensitive secrets must not leak in responses/sessions.
     */
    public function test_nf_03_token_privacy_and_session_security(): void
    {
        // 1. Verify HttpOnly cookie configuration
        $this->assertTrue(config('session.http_only'), 'Session cookie must have http_only set to true.');
        $this->assertNotEmpty(config('session.same_site'), 'Session cookie must have SameSite configured.');

        // 2. Make request to violations page with authenticated session
        $response = $this->withSession($this->getAuthSession('ADMIN'))->get('/list-vipham');
        $response->assertStatus(200);

        // Verify that server secrets and API tokens are not rendered in the response HTML
        $content = $response->getContent();
        $this->assertStringNotContainsString('test-server-side-fastapi-token-xyz', $content);
        $this->assertStringNotContainsString('GEMINI_API_KEY', $content);
        $this->assertStringNotContainsString('AIzaSy', $content); // Google API key signature prefix
    }

    /**
     * NF-04: Payload > 64KB to api/cim.php must be rejected immediately with HTTP 413.
     */
    public function test_nf_04_payload_size_rejection_over_64kb(): void
    {
        $session = $this->getAuthSession('ADMIN');

        // 65KB payload (exceeds 64KB / 65536 bytes)
        $oversizedData = [
            'action' => 'test',
            'blob' => str_repeat('A', 66000),
        ];

        $response = $this->withSession($session)->postJson('/api/cim.php?r=violations', $oversizedData);
        $response->assertStatus(413);
        $response->assertJson([
            'error' => 'Kích thước payload vượt quá giới hạn cho phép (tối đa 64KB).',
        ]);

        // Also test PUT
        $responsePut = $this->withSession($session)->putJson('/api/cim.php?r=violations', $oversizedData);
        $responsePut->assertStatus(413);
        $responsePut->assertJson([
            'error' => 'Kích thước payload vượt quá giới hạn cho phép (tối đa 64KB).',
        ]);
    }

    /**
     * NF-05: Query parameter > 100 characters must be rejected immediately with HTTP 422.
     */
    public function test_nf_05_query_param_length_rejection_over_100_chars(): void
    {
        $session = $this->getAuthSession('ADMIN');

        // 1. Query value > 100 chars
        $longValue = str_repeat('x', 105);
        $response1 = $this->withSession($session)->get('/api/cim.php?r=violations&filter=' . $longValue);
        $response1->assertStatus(422);
        $response1->assertJson([
            'error' => "Tham số 'filter' quá dài (tối đa 100 ký tự).",
        ]);

        // 2. Query key > 100 chars
        $longKey = str_repeat('k', 105);
        $response2 = $this->withSession($session)->get('/api/cim.php?r=violations&' . $longKey . '=val');
        $response2->assertStatus(422);
        $response2->assertJson([
            'error' => "Tên tham số '{$longKey}' quá dài (tối đa 100 ký tự).",
        ]);

        // 3. Array query parameter with value > 100 chars
        $response3 = $this->withSession($session)->get('/api/cim.php?r=violations&filter[]=' . $longValue);
        $response3->assertStatus(422);
        $response3->assertJson([
            'error' => "Tham số 'filter.0' quá dài (tối đa 100 ký tự).",
        ]);
    }

    /**
     * ST-16: Semester violation purge RBAC and password protection.
     */
    public function test_st_16_semester_reset_rbac_and_password_protection(): void
    {
        // 1. Unauthenticated request rejected
        $responseGuest = $this->postJson('/api/students.php', [
            'action' => 'reset-violations',
            'reset_password' => 'test',
        ]);
        $responseGuest->assertStatus(401);

        // 2. CTSV role cannot access reset or semester listing
        $ctsvSession = $this->getAuthSession('CTSV');

        $responseCtsvSemesters = $this->withSession($ctsvSession)->getJson('/api/students.php?action=semesters');
        $responseCtsvSemesters->assertStatus(403);

        $responseCtsvReset = $this->withSession($ctsvSession)->postJson('/api/students.php', [
            'action' => 'reset-violations',
            'reset_password' => 'cim_reset_dev',
        ]);
        $responseCtsvReset->assertStatus(403);

        // 3. ADMIN with wrong password rejected
        $adminSession = $this->getAuthSession('ADMIN');

        $responseAdminWrongPwd = $this->withSession($adminSession)->postJson('/api/students.php', [
            'action' => 'reset-violations',
            'reset_password' => 'wrong_password_123',
        ]);
        $responseAdminWrongPwd->assertStatus(403);
        $responseAdminWrongPwd->assertJson([
            'error' => 'Mật mã bảo mật không chính xác.',
        ]);

        // 4. Verify UI button visibility by RBAC
        $adminPage = $this->withSession($adminSession)->get('/ql-sinhvien');
        $adminPage->assertStatus(200);
        $this->assertStringContainsString('id="open-reset-semester-btn"', $adminPage->getContent());

        $ctsvPage = $this->withSession($ctsvSession)->get('/ql-sinhvien');
        $ctsvPage->assertStatus(200);
        $this->assertStringNotContainsString('id="open-reset-semester-btn"', $ctsvPage->getContent());

        $violationsPage = $this->withSession($adminSession)->get('/list-vipham');
        $violationsPage->assertStatus(200);
        $this->assertStringNotContainsString('id="open-reset-btn"', $violationsPage->getContent());
    }

    /**
     * ST-17: Semester violations listing and count preview.
     */
    public function test_st_17_semester_violation_counts_and_preview(): void
    {
        $adminSession = $this->getAuthSession('ADMIN');

        // 1. Get semester options
        $responseSemesters = $this->withSession($adminSession)->getJson('/api/students.php?action=semesters');
        $responseSemesters->assertStatus(200);
        $responseSemesters->assertJsonStructure([
            'semesters',
        ]);

        $semesters = $responseSemesters->json('semesters');
        if (!empty($semesters)) {
            $first = $semesters[0];
            $responseCount = $this->withSession($adminSession)->getJson(
                '/api/students.php?action=semester-violations&nam_hoc=' . urlencode($first['nam_hoc']) . '&hoc_ky=' . $first['hoc_ky']
            );
            $responseCount->assertStatus(200);
            $responseCount->assertJsonStructure([
                'count',
                'semester',
            ]);
        } else {
            // If DB has no semesters populated yet, query non-existent semester returns 404
            $responseCount = $this->withSession($adminSession)->getJson(
                '/api/students.php?action=semester-violations&nam_hoc=non_existent&hoc_ky=99'
            );
            $responseCount->assertStatus(404);
        }
    }
}
