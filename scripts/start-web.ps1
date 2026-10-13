<#
.SYNOPSIS  Chạy Web bằng PHP built-in server (phù hợp chạy thử/nội bộ; môi trường chính thức nên dùng IIS hoặc Apache).
.EXAMPLE   powershell -ExecutionPolicy Bypass -File scripts\start-web.ps1 -Port 8080
#>
param([string]$BindHost = "127.0.0.1", [int]$Port = 8080)
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "common.ps1")

$php = Get-CimPhp
$phpArgs = @()
$localIni = Join-Path (Split-Path -Parent $php) "php.ini"
if (Test-Path $localIni) { $phpArgs += @("-c", $localIni) }

& $php @phpArgs (Join-Path $script:RepoRoot "Website\tools\check_web.php")
if ($LASTEXITCODE -ne 0) {
    Write-Host "Có mục [LỖI] ở trên; Web vẫn khởi động nhưng một số chức năng sẽ báo lỗi." -ForegroundColor Yellow
}
Write-Host "Web: http://${BindHost}:$Port/login.php" -ForegroundColor Green
& $php @phpArgs -S "${BindHost}:$Port" -t (Join-Path $script:RepoRoot "Website\ui")
