# Watch Laravel Log in realtime
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " LARAVEL LOG VIEWER" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$logPath = "$PSScriptRoot\backend\LaravelService\storage\logs\laravel.log"

if (-not (Test-Path $logPath)) {
    Write-Host "Log file not found. Laravel might not have started yet." -ForegroundColor Yellow
    Write-Host "Creating empty log file..." -ForegroundColor Gray
    New-Item -Path $logPath -ItemType File -Force | Out-Null
}

Write-Host "Watching: $logPath" -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop" -ForegroundColor Gray
Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

Get-Content $logPath -Wait -Tail 50
