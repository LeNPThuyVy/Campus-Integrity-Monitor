<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\CimApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CimProxyController extends Controller
{
    public function __construct(
        protected CimApiClient $apiClient
    ) {}

    public function handle(Request $request): Response
    {
        $role = (string)$request->session()->get('role', '');
        $token = (string)$request->session()->get('api_token', '');
        $method = strtoupper($request->method());
        $route = (string)$request->query('r', '');
        $id = $request->query('id');

        $ADMIN = ['ADMIN'];
        $ALL = ['ADMIN', 'CTSV'];
        $listQuery = ['date_from', 'date_to', 'area_id', 'camera_id', 'type_id', 'status', 'page', 'page_size', 'sort'];
        $reportQuery = ['period', 'anchor', 'month', 'semester_id', 'date_from', 'date_to', 'area_id', 'camera_id', 'type_id'];

        $routes = [
            'health' => ['GET' => ['/api/v1/health', $ALL, [], false]],
            'dashboard' => ['GET' => ['/api/v1/dashboard', $ALL, $reportQuery, false]],
            'violation-types' => ['GET' => ['/api/v1/violation-types', $ALL, [], false]],
            'violations' => ['GET' => ['/api/v1/violations', $ALL, $listQuery, false]],
            'violation' => [
                'GET' => ['/api/v1/violations/{id}', $ALL, [], false],
                'PATCH' => ['/api/v1/violations/{id}', $ALL, [], false],
            ],
            'evidence' => ['GET' => ['/api/v1/violations/{id}/evidence', $ALL, [], true]],
            'report-summary' => ['GET' => ['/api/v1/reports/summary', $ALL, $reportQuery, false]],
            'report-export' => ['GET' => ['/api/v1/reports/export', $ALL, array_merge($reportQuery, ['format']), true]],
            'areas' => [
                'GET' => ['/api/v1/areas', $ALL, ['include_inactive'], false],
                'POST' => ['/api/v1/areas', $ADMIN, [], false],
            ],
            'area' => ['PUT' => ['/api/v1/areas/{id}', $ADMIN, [], false]],
            'cameras' => [
                'GET' => ['/api/v1/cameras', $ALL, ['area_id'], false],
                'POST' => ['/api/v1/cameras', $ADMIN, [], false],
            ],
            'camera' => ['PUT' => ['/api/v1/cameras/{id}', $ADMIN, [], false]],
            'camera-key' => ['POST' => ['/api/v1/cameras/{id}/rotate-key', $ADMIN, [], false]],
            'semesters' => [
                'GET' => ['/api/v1/semesters', $ALL, [], false],
                'POST' => ['/api/v1/semesters', $ADMIN, [], false],
            ],
            'semester' => [
                'PUT' => ['/api/v1/semesters/{id}', $ADMIN, [], false],
                'DELETE' => ['/api/v1/semesters/{id}', $ADMIN, [], false],
            ],
            'accounts' => [
                'GET' => ['/api/v1/accounts', $ADMIN, [], false],
                'POST' => ['/api/v1/accounts', $ADMIN, [], false],
            ],
            'account' => ['PUT' => ['/api/v1/accounts/{id}', $ADMIN, [], false]],
            'account-password' => ['POST' => ['/api/v1/accounts/{id}/reset-password', $ADMIN, [], false]],
        ];

        if (!isset($routes[$route])) {
            return response()->json(['error' => 'Chức năng không tồn tại.'], 404);
        }

        if (!isset($routes[$route][$method])) {
            return response()->json(['error' => 'Phương thức HTTP không được hỗ trợ.'], 405);
        }

        [$template, $allowedRoles, $allowedQuery, $binary] = $routes[$route][$method];
        if (!in_array($role, $allowedRoles, true)) {
            return response()->json(['error' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
        }

        $path = $template;
        if (str_contains($template, '{id}')) {
            $numericId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($numericId === false) {
                return response()->json(['error' => 'Mã bản ghi không hợp lệ.'], 422);
            }
            $path = str_replace('{id}', (string)$numericId, $template);
        }

        $query = [];
        foreach ($allowedQuery as $key) {
            $val = $request->query($key);
            if (is_string($val) && $val !== '') {
                $query[$key] = $val;
            }
        }

        $body = null;
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $body = $request->isJson() ? $request->json()->all() : $request->all();
        }

        $result = $this->apiClient->request($method, $path, $query, $body, $token);

        if ($binary && $result['status'] === 200) {
            $contentType = $result['headers']['content-type'] ?? 'application/octet-stream';
            $disposition = $result['headers']['content-disposition'] ?? null;
            $headers = ['Content-Type' => $contentType];
            if ($disposition) {
                $headers['Content-Disposition'] = $disposition;
            }
            return response($result['body'], 200, $headers);
        }

        $decoded = json_decode($result['body'], true);
        return response()->json($decoded ?: ['error' => $result['body']], $result['status']);
    }
}
