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

                    case 'violations':
                        $studentId = (int)$request->query('student_id', 0);
                        if ($studentId <= 0) {
                            return response()->json(['error' => 'Mã hồ sơ sinh viên không hợp lệ.'], 422);
                        }
                        return response()->json([
                            'violations' => $this->studentService->getViolations($studentId),
                            'types' => $this->studentService->getViolationTypes(),
                        ]);

                    case 'template':
                        $dataset = strtoupper((string)$request->query('dataset', 'SINHVIEN'));
                        $columns = $this->studentService->importColumns()[$dataset] ?? ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'];
                        $xlsx = $this->studentService->createXlsx($columns, []);
                        return response($xlsx, 200, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'Content-Disposition' => 'attachment; filename="' . strtolower($dataset) . '-template.xlsx"',
                        ]);

                    case 'export':
                        $list = $this->studentService->listStudents(array_merge($request->all(), [
                            'page' => 1,
                            'page_size' => 10000,
                        ]));
                        $exportRows = [];
                        foreach ($list['rows'] as $row) {
                            $exportRows[] = [
                                $row['MA_SV'],
                                $row['HOTEN'],
                                $row['NIEN_KHOA'],
                                $row['MA_LOP'],
                                $row['MA_NGANH'],
                                $row['MA_KHOA'],
                                $row['TEN_KHOA'],
                                $row['SO_VI_PHAM'],
                            ];
                        }
                        $xlsx = $this->studentService->createXlsx(
                            ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP', 'MA_NGANH', 'MA_KHOA', 'TEN_KHOA', 'SO_VI_PHAM'],
                            $exportRows
                        );
                        return response($xlsx, 200, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'Content-Disposition' => 'attachment; filename="danh-sach-sinh-vien.xlsx"',
                        ]);

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
                if ($action === 'save') {
                    $res = $this->studentService->upsertStudent($body);
                    return response()->json(['success' => true, 'id' => $res['id'], 'ma_sv' => $res['ma_sv']], 201);
                }

                if ($action === 'add_violation') {
                    $res = $this->studentService->addViolation($body);
                    if (!empty($res['duplicate'])) {
                        return response()->json([
                            'error' => $res['message'],
                            'code' => $res['code'],
                        ], 409);
                    }
                    return response()->json(['success' => true, 'id' => $res['id']], 201);
                }

                if ($action === 'delete_violation') {
                    $violationId = (int)($body['violation_id'] ?? 0);
                    $studentId = (int)($body['student_id'] ?? 0);
                    $this->studentService->deleteViolation($violationId, $studentId);
                    return response()->json(['success' => true]);
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
