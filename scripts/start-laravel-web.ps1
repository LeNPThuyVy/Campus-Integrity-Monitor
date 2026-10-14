<#
.SYNOPSIS  Chạy Web Portal Laravel (QuanLyTacPhongSV) bằng php artisan serve.
.EXAMPLE   powershell -ExecutionPolicy Bypass -File scripts\start-laravel-web.ps1 -Port 8080
#>
param([string]$BindHost = "127.0.0.1", [int]$Port = 8080)
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "common.ps1")

$php = Get-CimPhp
$laravelRoot = Join-Path $script:RepoRoot "QuanLyTacPhongSV"

Write-Host "Laravel Web Portal: http://${BindHost}:$Port/login" -ForegroundColor Green
Set-Location $laravelRoot
& $php artisan serve --host=$BindHost --port=$Port
