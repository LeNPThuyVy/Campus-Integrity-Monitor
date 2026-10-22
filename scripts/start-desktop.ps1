<#
.SYNOPSIS  Chạy ứng dụng Desktop AI (Tkinter). Cấu hình API nhập trong giao diện hoặc qua CIM_DESKTOP_* trong .env.
#>
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "common.ps1")

$python = Get-CimPython
Push-Location $script:FinalSystem
try {
    Write-Host "Khởi chạy ứng dụng Desktop AI (Tự động kích hoạt ngầm FastAPI Port 8001 nếu chưa chạy)..." -ForegroundColor Cyan
    & $python main.py
} finally {
    Pop-Location
}
