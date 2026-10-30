<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\StudentService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportExportTest extends TestCase
{
    protected function authenticatedSession(): array
    {
        return [
            'authenticated' => true,
            'role' => 'CTSV',
            'username' => 'ctsv_test',
            'display_name' => 'Cán bộ CTSV',
            'token_expires_at' => time() + 3600,
            'last_activity' => time(),
            'account_checked_at' => time(), // phiên vừa xác thực, middleware chưa cần tra lại CSDL
        ];
    }

    public function test_template_download_returns_xlsx_file(): void
    {
        $response = $this->withSession($this->authenticatedSession())
            ->get('/api/students.php?action=template&dataset=SINHVIEN');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="mau-nhap-sinh-vien.xlsx"');
        $this->assertNotEmpty($response->getContent());

        // Template NGANH
        $resNganh = $this->withSession($this->authenticatedSession())
            ->get('/api/students.php?action=template&dataset=NGANH');
        $resNganh->assertStatus(200);
        $resNganh->assertHeader('Content-Disposition', 'attachment; filename="mau-nhap-nganh.xlsx"');

        // Template LOP
        $resLop = $this->withSession($this->authenticatedSession())
            ->get('/api/students.php?action=template&dataset=LOP');
        $resLop->assertStatus(200);
        $resLop->assertHeader('Content-Disposition', 'attachment; filename="mau-nhap-lop.xlsx"');
    }

    public function test_get_export_is_forbidden_without_security_password(): void
    {
        $response = $this->withSession($this->authenticatedSession())
            ->get('/api/students.php?action=export');

        $response->assertStatus(403);
        $response->assertJsonStructure(['error']);
    }

    public function test_post_export_rejects_wrong_password(): void
    {
        $response = $this->withSession($this->authenticatedSession())
            ->postJson('/api/students.php', [
                'action' => 'export',
                'export_password' => 'WrongPassword',
            ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Mật khẩu bảo mật xuất dữ liệu không chính xác.']);
    }

    public function test_post_export_succeeds_with_correct_password(): void
    {
        $response = $this->withSession($this->authenticatedSession())
            ->postJson('/api/students.php', [
                'action' => 'export',
                'export_password' => 'Admin@Export2026',
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('Content-Disposition', 'attachment; filename="danh-sach-sinh-vien.xlsx"');
        $content = $response->getContent();
        $this->assertNotEmpty($content);

        // Kiểm tra file export có đúng 8 cột và có Tên ngành, không có Mã khoa
        $tempFile = tempnam(sys_get_temp_dir(), 'exp_test_') . '.xlsx';
        file_put_contents($tempFile, $content);
        $service = app(StudentService::class);
        $rows = $service->parseXlsxRows($tempFile);
        @unlink($tempFile);

        $this->assertNotEmpty($rows);
        $header = $rows[0];
        $this->assertCount(8, $header);
        $this->assertSame('STT', $header[0]);
        $this->assertSame('Mã sinh viên', $header[1]);
        $this->assertSame('Họ và tên', $header[2]);
        $this->assertSame('Niên khóa', $header[3]);
        $this->assertSame('Mã lớp', $header[4]);
        $this->assertSame('Tên ngành', $header[5]);
        $this->assertSame('Tên khoa', $header[6]);
        $this->assertSame('Số lượt vi phạm', $header[7]);
    }

    public function test_upload_excel_normalizes_and_returns_preview(): void
    {
        $service = app(StudentService::class);
        $headers = ['Mã SV', 'Họ và tên', 'Khóa học', 'Mã lớp'];
        $rows = [
            [' 2111 9999 ', '  nguyễn  thanh tùng  ', 'Khóa 14', ' 14dhth 01 '],
        ];
        $xlsxContent = $service->createXlsx($headers, $rows);

        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload_') . '.xlsx';
        file_put_contents($tempFile, $xlsxContent);

        $uploadedFile = new UploadedFile(
            $tempFile,
            'sinh-vien-test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->withSession($this->authenticatedSession())
            ->post('/api/students.php', [
                'action' => 'import-preview',
                'dataset' => 'SINHVIEN',
                'file' => $uploadedFile,
            ]);

        @unlink($tempFile);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'dataset',
            'preview_token',
            'counts' => ['valid', 'duplicate', 'error', 'normalized'],
            'can_commit',
            'rows',
        ]);

        $json = $response->json();
        $this->assertSame('SINHVIEN', $json['dataset']);
        $this->assertGreaterThanOrEqual(1, $json['counts']['normalized']);
        $this->assertSame('21119999', $json['rows'][0]['values']['MA_SV']);
        $this->assertSame('Nguyễn Thanh Tùng', $json['rows'][0]['values']['HOTEN']);
        $this->assertSame('14', $json['rows'][0]['values']['NIEN_KHOA']);
        $this->assertSame('14DHTH01', $json['rows'][0]['values']['MA_LOP']);

        // Test download normalized preview
        $token = $json['preview_token'];
        $dlResponse = $this->withSession($this->authenticatedSession())
            ->get('/api/students.php?action=download-normalized&preview_token=' . $token);

        $dlResponse->assertStatus(200);
        $dlResponse->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
