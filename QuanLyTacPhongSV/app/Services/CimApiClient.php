<?php

declare(strict_types=1);

namespace App\Services;

class CimApiClient
{
    protected ?string $explicitBaseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->explicitBaseUrl = $baseUrl ? rtrim($baseUrl, '/') : null;
    }

    /**
     * Resolves candidate FastAPI base URLs, automatically filtering out the port
     * that the current Laravel web server is listening on to prevent self-deadlock.
     *
     * @return list<string>
     */
    public function getCandidateBaseUrls(): array
    {
        if ($this->explicitBaseUrl !== null) {
            return [$this->explicitBaseUrl];
        }

        $candidates = [];

        // 1. Read directly from .env file on disk to bypass stale in-memory cache of php artisan serve
        $envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envPath)) {
            $envContent = @file_get_contents($envPath);
            if ($envContent && preg_match('/^\s*CIM_API_BASE_URL\s*=\s*(.+)$/m', $envContent, $m)) {
                $val = trim(trim($m[1]), '"\'');
                if ($val !== '') {
                    $candidates[] = rtrim($val, '/');
                }
            }
        }

        // 2. Read from env helper
        $envVal = (string)env('CIM_API_BASE_URL', '');
        if ($envVal !== '') {
            $candidates[] = rtrim($envVal, '/');
        }

        // 3. Common default ports for FastAPI - prioritize 8001 where FastAPI runs
        $candidates[] = 'http://127.0.0.1:8001';
        $candidates[] = 'http://127.0.0.1:8000';

        $candidates = array_values(array_unique($candidates));

        // 4. Exclude current Web server port to prevent self-calling deadlock on single-threaded php artisan serve
        $currentPort = (int)($_SERVER['SERVER_PORT'] ?? 0);
        if ($currentPort <= 0 && !empty($_SERVER['HTTP_HOST'])) {
            $parsed = parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_PORT);
            if ($parsed) {
                $currentPort = (int)$parsed;
            }
        }
        if ($currentPort <= 0) {
            $currentPort = 8000; // Default port for php artisan serve
        }

        $filtered = [];
        foreach ($candidates as $c) {
            $parsedPort = parse_url($c, PHP_URL_PORT);
            $port = $parsedPort ? (int)$parsedPort : (str_starts_with($c, 'https://') ? 443 : 80);
            if ($port !== $currentPort) {
                $filtered[] = $c;
            }
        }
        if (!empty($filtered)) {
            return $filtered;
        }

        return ['http://127.0.0.1:8001'];
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
        int $timeout = 30,
        bool $followRedirect = false
    ): array {
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

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
                'follow_location' => $followRedirect ? 1 : 0,
            ],
        ];
        if ($content !== null) {
            $options['http']['content'] = $content;
        }

        $queryString = $query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '';
        $baseUrls = $this->getCandidateBaseUrls();
        $lastResult = null;

        foreach ($baseUrls as $baseUrl) {
            $url = $baseUrl . $path . $queryString;
            $http_response_header = [];
            $body = @file_get_contents($url, false, stream_context_create($options));

            if ($body === false && empty($http_response_header)) {
                $lastResult = [
                    'status' => 503,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode(['error' => [
                        'code' => 'api_unreachable',
                        'message' => "Không kết nối được tới máy chủ API tại {$baseUrl}.",
                    ]], JSON_UNESCAPED_UNICODE),
                ];
                continue;
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

            // If candidate returned 404/405, this candidate port is not the right FastAPI service; try next candidate
            if (in_array($status, [404, 405], true) && count($baseUrls) > 1) {
                $lastResult = ['status' => $status, 'headers' => $responseHeaders, 'body' => (string)$body];
                continue;
            }

            return ['status' => $status, 'headers' => $responseHeaders, 'body' => (string)$body];
        }

        return $lastResult ?: [
            'status' => 503,
            'headers' => ['content-type' => 'application/json'],
            'body' => json_encode(['error' => [
                'code' => 'api_unreachable',
                'message' => 'Không kết nối được tới máy chủ API. Kiểm tra FastAPI đã chạy (cổng 8001 hoặc 8000) và CIM_API_BASE_URL.',
            ]], JSON_UNESCAPED_UNICODE),
        ];
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
