<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class StudentController extends Controller
{
    public function __construct(
        protected StudentService $studentService
    ) {}

    public function index(): View
    {
        return view('students.index', [
            'pageTitle' => 'Quản lý sinh viên',
        ]);
    }

    public function api(Request $request): JsonResponse|Response
    {
        $action = $request->query('action') ?: $request->input('action');

        if ($request->isMethod('GET')) {
            try {
                switch ($action) {
                    case 'csrf':
                        return response()->json(['csrf_token' => csrf_token()]);

                    case 'init':
                        return response()->json([
                            'csrf_token' => csrf_token(),
                            'statistics' => $this->studentService->getStatistics(),
                            'catalog' => $this->studentService->getCatalog(),
                            'list' => $this->studentService->listStudents($request->all()),
                        ]);

                    case 'list':
                        return response()->json($this->studentService->listStudents($request->all()));

                    case 'catalog':
                        return response()->json($this->studentService->getCatalog());

                    case 'detail':
                        $id = (int)$request->query('id', 0);
                        if ($id <= 0) {
                            return response()->json(['error' => 'Mã hồ sơ không hợp lệ.'], 422);
                        }
                        $detail = $this->studentService->getStudentDetail($id);
                        if (!$detail) {
                            return response()->json(['error' => 'Không tìm thấy hồ sơ sinh viên.'], 404);
                        }
                        return response()->json(['student' => $detail]);

                    case 'lookup-student':
                        $code = trim((string)$request->query('ma_sv', ''));
                        if ($code === '') {
                            return response()->json(['error' => 'Vui lòng cung cấp mã sinh viên.'], 422);
                        }
                        $student = $this->studentService->findStudentByCode($code);
                        if (!$student) {
                            return response()->json(['error' => "Không tìm thấy sinh viên có mã '{$code}'."], 404);
                        }
                        return response()->json(['student' => $student]);

                    case 'violation-form-meta':
                        return response()->json([
                            'types' => $this->studentService->getViolationTypes(),
                            'areas' => $this->studentService->getAreas(),
                            'today' => date('Y-m-d'),
                            'now_time' => date('H:i'),
                        ]);

                    case 'violations':
                        $studentId = (int)($request->query('student_id') ?: $request->query('id', 0));
                        if ($studentId <= 0) {
                            return response()->json(['error' => 'Mã hồ sơ sinh viên không hợp lệ.'], 422);
                        }
                        return response()->json([
                            'violations' => $this->studentService->getViolations($studentId),
                            'types' => $this->studentService->getViolationTypes(),
                            'areas' => $this->studentService->getAreas(),
                            'today' => date('Y-m-d'),
                        ]);

                    case 'template':
                        $dataset = strtoupper((string)$request->query('dataset', 'SINHVIEN'));
                        $headers = $this->studentService->importHeaderLabels($dataset);
                        $samples = $this->studentService->sampleRows($dataset);
                        $xlsx = $this->studentService->createXlsx($headers, $samples);
                        $fileNames = [
                            'KHOA' => 'mau-nhap-khoa.xlsx',
                            'NGANH' => 'mau-nhap-nganh.xlsx',
                            'LOP' => 'mau-nhap-lop.xlsx',
                            'SINHVIEN' => 'mau-nhap-sinh-vien.xlsx',
                            'VIPHAM' => 'mau-nhap-bien-ban-vi-pham.xlsx',
                        ];
                        $filename = $fileNames[$dataset] ?? (strtolower($dataset) . '-template.xlsx');
                        return response($xlsx, 200, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        ]);

                    case 'download-normalized':
                        $token = (string)$request->query('preview_token', '');
                        if ($token === '') {
                            return response()->json(['error' => 'Mã phiên xem trước không hợp lệ.'], 422);
                        }
                        $res = $this->studentService->downloadNormalizedPreview($token);
                        return response($res['content'], 200, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'Content-Disposition' => 'attachment; filename="' . strtolower($res['dataset']) . '-chuan-hoa.xlsx"',
                        ]);

                    case 'export':
                        return response()->json(['error' => 'Vui lòng xác thực mật khẩu bảo mật trước khi xuất danh sách.'], 403);

                    default:
                        return response()->json(['error' => 'Chức năng không tồn tại.'], 404);
                }
            } catch (Throwable $e) {
                return response()->json(['error' => 'Lỗi xử lý yêu cầu sinh viên: ' . $e->getMessage()], 500);
            }
        }

        if ($request->isMethod('POST')) {
            $body = $request->isJson() ? $request->json()->all() : $request->all();
            $action = $body['action'] ?? $action;

            try {
                // Nhận diện request upload xem trước file Excel (FormData)
                if ($request->hasFile('file') || $action === 'preview' || $action === 'import-preview') {
                    $dataset = (string)($request->input('dataset') ?: ($body['dataset'] ?? 'SINHVIEN'));
                    $file = $request->file('file');
                    $result = $this->studentService->previewImport($dataset, $file);
                    return response()->json($result);
                }

                if ($action === 'import-commit') {
                    $token = (string)($body['preview_token'] ?? '');
                    if ($token === '') {
                        return response()->json(['error' => 'Mã phiên xem trước không hợp lệ.'], 422);
                    }
                    $result = $this->studentService->commitImport($token);
                    return response()->json([
                        'success' => true,
                        'inserted' => $result['inserted'],
                        'skipped_duplicates' => $result['skipped_duplicates'],
                    ]);
                }

                if ($action === 'export') {
                    $password = (string)($body['export_password'] ?? '');
                    if (!$this->studentService->verifyExportPassword($password)) {
                        return response()->json(['error' => 'Mật khẩu bảo mật xuất dữ liệu không chính xác.'], 403);
                    }

                    $list = $this->studentService->listStudents(array_merge($body, [
                        'page' => 1,
                        'page_size' => 10000,
                    ]));
                    $exportRows = [];
                    $stt = 1;
                    foreach ($list['rows'] as $row) {
                        $exportRows[] = [
                            $stt++,
                            $row['MA_SV'],
                            $row['HOTEN'],
                            $row['NIEN_KHOA'],
                            $row['MA_LOP'],
                            $row['TEN_NGANH'] ?? '',
                            $row['TEN_KHOA'] ?? '',
                            $row['SO_VI_PHAM'],
                        ];
                    }
                    $exportHeaders = [
                        'STT',
                        'Mã sinh viên',
                        'Họ và tên',
                        'Niên khóa',
                        'Mã lớp',
                        'Tên ngành',
                        'Tên khoa',
                        'Số lượt vi phạm',
                    ];
                    $xlsx = $this->studentService->createXlsx($exportHeaders, $exportRows);
                    return response($xlsx, 200, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'Content-Disposition' => 'attachment; filename="danh-sach-sinh-vien.xlsx"',
                    ]);
                }

                if ($action === 'save') {
                    $studentData = is_array($body['student'] ?? null) ? $body['student'] : $body;
                    $res = $this->studentService->upsertStudent($studentData);
                    return response()->json(['success' => true, 'id' => $res['id'], 'ma_sv' => $res['ma_sv']], 201);
                }

                if ($action === 'add_violation' || $action === 'violation-add') {
                    $payload = is_array($body['violation'] ?? null) ? array_merge($body, $body['violation']) : $body;
                    $res = $this->studentService->addViolation($payload);
                    if (!empty($res['duplicate'])) {
                        return response()->json([
                            'error' => $res['message'],
                            'code' => $res['code'],
                        ], 409);
                    }
                    return response()->json(['success' => true, 'id' => $res['id']], 201);
                }

                if ($action === 'delete_violation' || $action === 'violation-delete') {
                    $violationId = (int)($body['violation_id'] ?? ($body['id'] ?? 0));
                    $studentId = (int)($body['student_id'] ?? 0);
                    $this->studentService->deleteViolation($violationId, $studentId);
                    return response()->json(['success' => true]);
                }

                if ($action === 'reset-violations' || $action === 'reset_violations') {
                    $password = (string)($body['reset_password'] ?? ($body['password'] ?? ''));
                    if (!$this->studentService->verifyResetPassword($password)) {
                        return response()->json(['error' => 'Mật mã bảo mật không chính xác.'], 403);
                    }
                    $res = $this->studentService->resetAllViolations();
                    return response()->json([
                        'success' => true,
                        'message' => 'Đã reset toàn bộ vi phạm về 0 thành công.',
                        'deleted_student_violations' => $res['deleted_student_violations'],
                        'deleted_camera_records' => $res['deleted_camera_records'],
                    ]);
                }

                return response()->json(['error' => 'Thao tác không được hỗ trợ.'], 422);
            } catch (InvalidArgumentException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            } catch (Throwable $e) {
                return response()->json(['error' => 'Không thể hoàn tất thao tác: ' . $e->getMessage()], 500);
            }
        }

        if ($request->isMethod('DELETE')) {
            $body = $request->isJson() ? $request->json()->all() : $request->all();
            $id = (int)($body['id'] ?? 0);
            if ($id <= 0) {
                return response()->json(['error' => 'Mã hồ sơ không hợp lệ.'], 422);
            }
            try {
                $this->studentService->deleteStudent($id);
                return response()->json(['success' => true]);
            } catch (Throwable $e) {
                return response()->json(['error' => 'Không thể xóa sinh viên: ' . $e->getMessage()], 409);
            }
        }

        return response()->json(['error' => 'Phương thức HTTP không được hỗ trợ.'], 405);
    }
}
