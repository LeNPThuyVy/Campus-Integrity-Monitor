<?php

declare(strict_types=1);

namespace App\Services;

class CimApiClient
{
    protected string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?: (string)env('CIM_API_BASE_URL', 'http://127.0.0.1:8000'), '/');
    }

    /**
     * Minimal HTTP client for the FastAPI backend (Web -> API -> SQL Server).
     * Uses PHP streams, so no extra extensions required.
     *
     * @return array{status:int, headers:array<string,string>, body:string}
     */
    public function request(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        ?string $token = null,
        int $timeout = 30
    ): array {
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $url = $this->baseUrl . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');

        $headers = ['Accept: application/json, image/jpeg, application/pdf, */*'];
        if ($token !== null && $token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $content = null;
        if ($json !== null) {
            $content = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($content);
        }
        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => $timeout,
                'follow_location' => 0,
            ],
        ];
        if ($content !== null) {
            $options['http']['content'] = $content;
        }

        $http_response_header = [];
        $body = @file_get_contents($url, false, stream_context_create($options));
        if ($body === false && empty($http_response_header)) {
            return [
                'status' => 503,
                'headers' => ['content-type' => 'application/json'],
                'body' => json_encode(['error' => [
                    'code' => 'api_unreachable',
                    'message' => 'Không kết nối được tới máy chủ API. Kiểm tra API đã chạy và CIM_API_BASE_URL.',
                ]], JSON_UNESCAPED_UNICODE),
            ];
        }

        $status = 502;
        $responseHeaders = [];
        foreach ($http_response_header as $line) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $match) === 1) {
                $status = (int)$match[1];
                $responseHeaders = [];
                continue;
            }
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }
        return ['status' => $status, 'headers' => $responseHeaders, 'body' => (string)$body];
    }

    /**
     * Send request and decode JSON response: [status, decoded array|null]
     *
     * @return array{0: int, 1: ?array}
     */
    public function json(
        string $method,
        string $path,
        array $query = [],
        ?array $json = null,
        ?string $token = null
    ): array {
        $response = $this->request($method, $path, $query, $json, $token);
        $decoded = json_decode($response['body'], true);
        return [$response['status'], is_array($decoded) ? $decoded : null];
    }

    public function errorMessage(?array $decoded, string $fallback = 'Đã xảy ra lỗi không xác định.'): string
    {
        $message = $decoded['error']['message'] ?? ($decoded['message'] ?? ($decoded['detail'] ?? null));
        return is_string($message) && $message !== '' ? $message : $fallback;
    }
}
