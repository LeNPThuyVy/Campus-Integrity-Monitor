<#
.SYNOPSIS  Chạy ứng dụng Desktop AI (Tkinter). Cấu hình API nhập trong giao diện hoặc qua CIM_DESKTOP_* trong .env.
#>
$ErrorActionPreference = "Stop"
. (Join-Path $PSScriptRoot "common.ps1")

$python = Get-CimPython
Push-Location $script:FinalSystem
try {
    & $python main.py
} finally {
    Pop-Location
}
