<#
.SYNOPSIS  Kiểm tra toàn bộ cấu hình: Python/ODBC/SQL Server (API) và PHP/pdo_sqlsrv/API (Web).
           Không in mật khẩu hay khóa bí mật. Trả về mã lỗi 1 nếu có mục [LỖI].
.EXAMPLE   powershell -ExecutionPolicy Bypass -File scripts\check-system.ps1
#>
$ErrorActionPreference = "Continue"
. (Join-Path $PSScriptRoot "common.ps1")
$failed = $false

Write-Host "=== API (Python) ===" -ForegroundColor Cyan
Push-Location $script:FinalSystem
try {
    & (Get-CimPython) -m api.cli check-config
    if ($LASTEXITCODE -ne 0) { $failed = $true }
} finally {
    Pop-Location
}

Write-Host ""
Write-Host "=== SQL Server service ===" -ForegroundColor Cyan
$services = Get-Service -ErrorAction SilentlyContinue | Where-Object { $_.Name -like 'MSSQL*' }
if ($services) { $services | Format-Table Name, Status -AutoSize | Out-String | Write-Host }
else { Write-Host "Không thấy dịch vụ SQL Server trên máy này (có thể SQL Server ở máy khác)." }

Write-Host "=== Web (PHP) ===" -ForegroundColor Cyan
try {
    $php = Get-CimPhp
    $phpArgs = @()
    $localIni = Join-Path (Split-Path -Parent $php) "php.ini"
    if (Test-Path $localIni) { $phpArgs += @("-c", $localIni) }
    & $php @phpArgs (Join-Path $script:RepoRoot "Website\tools\check_web.php")
    if ($LASTEXITCODE -ne 0) { $failed = $true }
} catch {
    Write-Host "[LỖI] $($_.Exception.Message)" -ForegroundColor Red
    $failed = $true
}

if ($failed) { exit 1 } else { exit 0 }
