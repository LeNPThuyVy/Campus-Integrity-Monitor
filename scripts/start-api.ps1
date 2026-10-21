<#
.SYNOPSIS  Chạy API FastAPI (đọc cấu hình từ .env ở thư mục gốc).
.EXAMPLE   powershell -ExecutionPolicy Bypass -File scripts\start-api.ps1 -Port 8000
#>
param([string]$BindHost = "127.0.0.1", [int]$Port = 8001)
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "common.ps1")

$python = Get-CimPython
Push-Location $script:FinalSystem
try {
    & $python -m api.cli check-config
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Cấu hình chưa đầy đủ (xem các dòng [LỖI] ở trên). API vẫn khởi động để bạn kiểm tra /api/v1/health." -ForegroundColor Yellow
    }
    Write-Host "API: http://${BindHost}:$Port  (tài liệu: /docs, sức khỏe: /api/v1/health)" -ForegroundColor Green
    & $python -m uvicorn api.main:app --host $BindHost --port $Port
} finally {
    Pop-Location
}
