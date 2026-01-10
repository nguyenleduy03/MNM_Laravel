# Start Fullstack Application with Laravel Backend
# Version: 4.0.0 - Laravel Only (Spring Boot removed)

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " Starting Full Stack Application with Laravel" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Check PHP
Write-Host "[1/5] Checking PHP..." -ForegroundColor Yellow
$phpPath = "C:\php\php.exe"
if (-not (Test-Path $phpPath)) {
    Write-Host "   [ERROR] PHP not found at C:\php!" -ForegroundColor Red
    exit 1
}
Write-Host "   [OK] PHP found" -ForegroundColor Green

# Check Python
Write-Host "[2/5] Checking Python..." -ForegroundColor Yellow
$pythonExists = Get-Command python -ErrorAction SilentlyContinue
if (-not $pythonExists) {
    Write-Host "   [ERROR] Python not found!" -ForegroundColor Red
    exit 1
}
Write-Host "   [OK] Python found" -ForegroundColor Green

# Check Node.js
Write-Host "[3/5] Checking Node.js..." -ForegroundColor Yellow
$nodeExists = Get-Command node -ErrorAction SilentlyContinue
if (-not $nodeExists) {
    Write-Host "   [ERROR] Node.js not found!" -ForegroundColor Red
    exit 1
}
Write-Host "   [OK] Node.js found" -ForegroundColor Green

# Check .env file
Write-Host "[4/5] Checking .env configuration..." -ForegroundColor Yellow
$envPath = "$PSScriptRoot\backend\PythonService\.env"
if (-not (Test-Path $envPath)) {
    Write-Host "   [WARNING] .env file not found! OAuth may not work." -ForegroundColor Yellow
} else {
    Write-Host "   [OK] .env file found" -ForegroundColor Green
}

Write-Host ""
Write-Host "[5/5] Starting all services..." -ForegroundColor Yellow
Write-Host ""

# Start Laravel Backend
Write-Host "[*] Starting Laravel Backend (Port 8001)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\backend\LaravelService'; Write-Host '[Laravel Server - Port 8001]' -ForegroundColor Green; C:\php\php.exe artisan serve --port=8001 --no-ansi 2>&1"

# Wait a bit
Start-Sleep -Seconds 3

# Start OAuth Service
Write-Host "[*] Starting OAuth Service (Port 8003)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\backend\PythonService'; Write-Host '[OAuth Service]' -ForegroundColor Green; py google_oauth_service.py"

# Wait a bit
Start-Sleep -Seconds 2

# Start Google Cloud Service
Write-Host "[*] Starting Google Cloud Service (Port 8004)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\backend\PythonService'; Write-Host '[Google Cloud Service]' -ForegroundColor Green; py google_cloud_service_oauth.py"

# Wait a bit
Start-Sleep -Seconds 2

# Start FastAPI AI Service
Write-Host "[*] Starting AI Service (Port 8000)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\backend\PythonService'; Write-Host '[AI Service]' -ForegroundColor Green; py main.py"

# Wait a bit
Start-Sleep -Seconds 2

# Start Frontend
Write-Host "[*] Starting Frontend (Port 5173)..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot\fronend_web'; Write-Host '[Frontend Server]' -ForegroundColor Green; npm run dev"

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " All Services Started Successfully!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Service URLs:" -ForegroundColor Yellow
Write-Host ""
Write-Host "   Frontend:           http://localhost:5173" -ForegroundColor White
Write-Host "   Laravel Backend:    http://localhost:8001" -ForegroundColor White
Write-Host "     - Health:         http://localhost:8001/api/health" -ForegroundColor Gray
Write-Host "     - Swagger:        http://localhost:8001/swagger.html" -ForegroundColor Gray
Write-Host "   AI Service:         http://localhost:8000" -ForegroundColor White
Write-Host "     - Docs:           http://localhost:8000/docs" -ForegroundColor Gray
Write-Host "   OAuth Service:      http://localhost:8003" -ForegroundColor White
Write-Host "     - Docs:           http://localhost:8003/docs" -ForegroundColor Gray
Write-Host "   Google Cloud:       http://localhost:8004" -ForegroundColor White
Write-Host "     - Docs:           http://localhost:8004/docs" -ForegroundColor Gray
Write-Host ""
Write-Host "Note: Using Laravel (PHP) as backend" -ForegroundColor Magenta
Write-Host "      Spring Boot has been removed from this project" -ForegroundColor Magenta
Write-Host ""

Read-Host "Press Enter to exit"
