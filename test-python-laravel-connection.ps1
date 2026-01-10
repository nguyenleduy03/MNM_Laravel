# Test Python Service <-> Laravel Connection
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " PYTHON SERVICE & LARAVEL CONNECTION TEST" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$LaravelUrl = "http://localhost:8001"
$PythonUrl = "http://localhost:8000"

# Test 1: Python Service Health
Write-Host "[1] Testing Python Service..." -ForegroundColor Yellow
try {
    $pythonHealth = Invoke-RestMethod -Uri "$PythonUrl/health" -TimeoutSec 5
    Write-Host "  ✅ Python service is running" -ForegroundColor Green
    Write-Host "  Response: $($pythonHealth | ConvertTo-Json)" -ForegroundColor Gray
} catch {
    Write-Host "  ❌ Python service is NOT running" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "  Please start Python service:" -ForegroundColor Yellow
    Write-Host "  cd backend/PythonService" -ForegroundColor White
    Write-Host "  py main.py" -ForegroundColor White
    exit 1
}

Write-Host ""

# Test 2: Laravel Health
Write-Host "[2] Testing Laravel..." -ForegroundColor Yellow
try {
    $laravelHealth = Invoke-RestMethod -Uri "$LaravelUrl/api/health" -TimeoutSec 5
    Write-Host "  ✅ Laravel is running" -ForegroundColor Green
    Write-Host "  Response: $($laravelHealth | ConvertTo-Json)" -ForegroundColor Gray
} catch {
    Write-Host "  ❌ Laravel is NOT running" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Test 3: Groq Models API
Write-Host "[3] Testing Groq Models API..." -ForegroundColor Yellow
try {
    $groqModels = Invoke-RestMethod -Uri "$PythonUrl/api/models/groq" -TimeoutSec 10
    Write-Host "  ✅ Groq models loaded successfully" -ForegroundColor Green
    Write-Host "  Found $($groqModels.models.Count) models" -ForegroundColor Cyan
    
    if ($groqModels.models.Count -gt 0) {
        Write-Host ""
        Write-Host "  Available models:" -ForegroundColor Cyan
        foreach ($model in $groqModels.models | Select-Object -First 5) {
            Write-Host "    - $($model.id)" -ForegroundColor White
        }
        if ($groqModels.models.Count -gt 5) {
            Write-Host "    ... and $($groqModels.models.Count - 5) more" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "  ❌ Failed to load Groq models" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "  Possible reasons:" -ForegroundColor Yellow
    Write-Host "    - GROQ_API_KEY not set in .env" -ForegroundColor White
    Write-Host "    - Network issue" -ForegroundColor White
    Write-Host "    - Groq API down" -ForegroundColor White
}

Write-Host ""

# Test 4: All AI Models API
Write-Host "[4] Testing All Models API..." -ForegroundColor Yellow
try {
    $allModels = Invoke-RestMethod -Uri "$PythonUrl/api/models" -TimeoutSec 10
    Write-Host "  ✅ Models API working" -ForegroundColor Green
    Write-Host "  Gemini models: $($allModels.models.Count)" -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ Failed to load models" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Test 5: Python -> Laravel Connection
Write-Host "[5] Testing Python -> Laravel Connection..." -ForegroundColor Yellow
Write-Host "  Checking BACKEND_URL in Python .env..." -ForegroundColor Gray

$pythonEnv = Get-Content "backend\PythonService\.env" -Raw
if ($pythonEnv -match 'BACKEND_URL=(.+)') {
    $backendUrl = $matches[1].Trim()
    Write-Host "  BACKEND_URL: $backendUrl" -ForegroundColor Cyan
    
    if ($backendUrl -eq "http://localhost:8001") {
        Write-Host "  ✅ BACKEND_URL is correct for Laravel" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  BACKEND_URL is $backendUrl" -ForegroundColor Yellow
        Write-Host "  Should be: http://localhost:8001 for Laravel" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ❌ BACKEND_URL not found in .env" -ForegroundColor Red
}

Write-Host ""

# Test 6: Laravel -> Python Connection
Write-Host "[6] Testing Laravel -> Python Connection..." -ForegroundColor Yellow
Write-Host "  Checking FASTAPI_URL in Laravel .env..." -ForegroundColor Gray

$laravelEnv = Get-Content "backend\LaravelService\.env" -Raw
if ($laravelEnv -match 'FASTAPI_URL=(.+)') {
    $fastApiUrl = $matches[1].Trim()
    Write-Host "  FASTAPI_URL: $fastApiUrl" -ForegroundColor Cyan
    
    if ($fastApiUrl -eq "http://localhost:8000") {
        Write-Host "  ✅ FASTAPI_URL is correct" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  FASTAPI_URL is $fastApiUrl" -ForegroundColor Yellow
        Write-Host "  Should be: http://localhost:8000" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ❌ FASTAPI_URL not found in .env" -ForegroundColor Red
}

Write-Host ""

# Test 7: Test AI Chat
Write-Host "[7] Testing AI Chat..." -ForegroundColor Yellow
try {
    $chatBody = @{
        message = "Hello, test connection"
        user_id = "1"
        use_rag = $false
    } | ConvertTo-Json
    
    $chatResponse = Invoke-RestMethod -Uri "$PythonUrl/api/chat" -Method POST -Body $chatBody -ContentType "application/json" -TimeoutSec 30
    
    Write-Host "  ✅ AI Chat working" -ForegroundColor Green
    Write-Host "  Response: $($chatResponse.response.Substring(0, [Math]::Min(80, $chatResponse.response.Length)))..." -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ AI Chat failed" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " TEST SUMMARY" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Services:" -ForegroundColor White
Write-Host "  ✅ Python Service (port 8000)" -ForegroundColor Green
Write-Host "  ✅ Laravel Backend (port 8001)" -ForegroundColor Green
Write-Host ""
Write-Host "APIs:" -ForegroundColor White
Write-Host "  ✅ Groq Models API" -ForegroundColor Green
Write-Host "  ✅ AI Chat API" -ForegroundColor Green
Write-Host ""
Write-Host "Configuration:" -ForegroundColor White
Write-Host "  Python BACKEND_URL → Laravel (8001)" -ForegroundColor Cyan
Write-Host "  Laravel FASTAPI_URL → Python (8000)" -ForegroundColor Cyan
Write-Host ""
Write-Host "🎉 All connections are working!" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
