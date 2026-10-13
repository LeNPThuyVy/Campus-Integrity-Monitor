# Shared helpers for the start/check scripts.
$script:RepoRoot = Split-Path -Parent $PSScriptRoot
$script:FinalSystem = Join-Path $script:RepoRoot "Final_System"

function Get-CimPhp {
    $local = Join-Path $script:RepoRoot "tools\php\php.exe"
    if (Test-Path $local) { return $local }
    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    throw "Không tìm thấy PHP. Chạy: powershell -ExecutionPolicy Bypass -File scripts\setup-php-windows.ps1"
}

function Get-CimPython {
    $command = Get-Command python -ErrorAction SilentlyContinue
    if (-not $command) { throw "Không tìm thấy Python 3.10+ trong PATH." }
    return $command.Source
}

