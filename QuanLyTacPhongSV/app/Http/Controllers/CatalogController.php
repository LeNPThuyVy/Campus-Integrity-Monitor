<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use PDOException;
use Throwable;

class CatalogController extends Controller
{
    public function __construct(
        protected CatalogService $catalogService
    ) {}

    public function index(): View
    {
        return view('catalogs.index', [
            'pageTitle' => 'Danh mục',
        ]);
    }

    public function api(Request $request): JsonResponse
    {
        $role = (string)$request->session()->get('role', '');

        if ($request->isMethod('GET')) {
            try {
                $catalogs = $this->catalogService->getAllCatalogs();
                return response()->json([
                    'csrf_token' => csrf_token(),
                    'role' => $role,
                    'catalogs' => $catalogs,
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'error' => 'Không thể đọc dữ liệu danh mục từ cơ sở dữ liệu: ' . $e->getMessage(),
                ], 503);
            }
        }

        if ($request->isMethod('POST')) {
            $body = $request->json()->all();
            $entity = strtoupper((string)($body['entity'] ?? ''));
            $action = (string)($body['action'] ?? '');

            if ($entity === 'LOI_VIPHAM' && $role !== 'ADMIN') {
                return response()->json([
                    'error' => 'Chỉ Quản trị viên mới được phép thay đổi loại vi phạm.',
                ], 403);
            }

            try {
                if ($action === 'save') {
                    $id = $this->catalogService->save(
                        $entity,
                        $body['id'] ?? null,
                        is_array($body['record'] ?? null) ? $body['record'] : []
                    );
                    return response()->json(['success' => true, 'id' => $id], 201);
                }

                if ($action === 'delete') {
                    $this->catalogService->delete($entity, $body['id'] ?? null);
                    return response()->json(['success' => true]);
                }

                return response()->json(['error' => 'Thao tác danh mục không được hỗ trợ.'], 422);
            } catch (InvalidArgumentException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            } catch (PDOException $e) {
                if (str_starts_with((string)$e->getCode(), '23')) {
                    $msg = $action === 'delete'
                        ? 'Danh mục đang được dữ liệu khác tham chiếu; không thể xóa.'
                        : 'Mã hoặc tên danh mục đã tồn tại trong hệ thống.';
                    return response()->json(['error' => $msg], 409);
                }
                return response()->json(['error' => 'Không thể lưu thay đổi danh mục vào cơ sở dữ liệu.'], 503);
            } catch (Throwable $e) {
                return response()->json(['error' => 'Không thể hoàn tất thao tác danh mục.'], 500);
            }
        }

        return response()->json(['error' => 'Phương thức HTTP không được hỗ trợ.'], 405);
    }
}
