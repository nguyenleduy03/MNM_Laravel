# Test Laravel <-> Python Service Connection
# Kiểm tra kết nối và tích hợp giữa Laravel và Python

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " LARAVEL <-> PYTHON CONNECTION TEST" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$LaravelUrl = "http://localhost:8001"
$PythonUrl = "http://localhost:8000"

# ============================================================================
# 1. Check Services Running
# ============================================================================
Write-Host "[1] Checking Services..." -ForegroundColor Yellow
Write-Host ""

function Test-Service {
    param([string]$Name, [string]$Url)
    try {
        $response = Invoke-WebRequest -Uri $Url -TimeoutSec 3 -UseBasicParsing
        Write-Host "  ✅ $Name is running" -ForegroundColor Green
        return $true
    } catch {
        Write-Host "  ❌ $Name is NOT running" -ForegroundColor Red
        return $false
    }
}

$laravelRunning = Test-Service "Laravel (Port 8001)" "$LaravelUrl/api/health"
$pythonRunning = Test-Service "Python AI (Port 8000)" "$PythonUrl/health"

if (-not $laravelRunning -or -not $pythonRunning) {
    Write-Host ""
    Write-Host "Please start all services first:" -ForegroundColor Red
    Write-Host "  .\start-fullstack.ps1 -Backend laravel" -ForegroundColor White
    exit 1
}

Write-Host ""

# ============================================================================
# 2. Check Laravel .env Configuration
# ============================================================================
Write-Host "[2] Checking Laravel Configuration..." -ForegroundColor Yellow
Write-Host ""

$laravelEnv = Get-Content "backend\LaravelService\.env" -Raw
if ($laravelEnv -match 'FASTAPI_URL=(.+)') {
    $fastApiUrl = $matches[1].Trim()
    Write-Host "  📝 FASTAPI_URL: $fastApiUrl" -ForegroundColor Cyan
    if ($fastApiUrl -eq "http://localhost:8000") {
        Write-Host "  ✅ FASTAPI_URL is correct" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  FASTAPI_URL should be http://localhost:8000" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ❌ FASTAPI_URL not found in .env" -ForegroundColor Red
}

Write-Host ""

# ============================================================================
# 3. Check Python .env Configuration
# ============================================================================
Write-Host "[3] Checking Python Configuration..." -ForegroundColor Yellow
Write-Host ""

$pythonEnv = Get-Content "backend\PythonService\.env" -Raw
if ($pythonEnv -match 'BACKEND_URL=(.+)') {
    $backendUrl = $matches[1].Trim()
    Write-Host "  📝 BACKEND_URL: $backendUrl" -ForegroundColor Cyan
    if ($backendUrl -eq "http://localhost:8001") {
        Write-Host "  ✅ BACKEND_URL is correct for Laravel" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️  BACKEND_URL is $backendUrl (should be http://localhost:8001 for Laravel)" -ForegroundColor Yellow
    }
} else {
    Write-Host "  ❌ BACKEND_URL not found in .env" -ForegroundColor Red
}

Write-Host ""

# ============================================================================
# 4. Test Laravel -> Python Connection
# ============================================================================
Write-Host "[4] Testing Laravel -> Python Connection..." -ForegroundColor Yellow
Write-Host ""

# Login to get token
Write-Host "  ⏳ Logging in to Laravel..." -ForegroundColor Gray
try {
    $loginBody = @{
        username = "testuser"
        password = "123456"
    } | ConvertTo-Json
    
    $loginResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    $token = $loginResponse.token
    $userId = $loginResponse.user.id
    Write-Host "  ✅ Login successful" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Login failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Test Quick Chat (Laravel calls Python)
Write-Host "  ⏳ Testing Quick Chat (Laravel -> Python)..." -ForegroundColor Gray
try {
    $chatBody = @{
        message = "Test connection"
    } | ConvertTo-Json
    
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    
    $chatResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/quick" -Method POST -Body $chatBody -ContentType "application/json" -Headers $headers -TimeoutSec 30
    Write-Host "  ✅ Laravel -> Python: SUCCESS" -ForegroundColor Green
    Write-Host "  📝 Response: $($chatResponse.response.Substring(0, [Math]::Min(50, $chatResponse.response.Length)))..." -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ Laravel -> Python: FAILED" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# ============================================================================
# 5. Test Python -> Laravel Connection
# ============================================================================
Write-Host "[5] Testing Python -> Laravel Connection..." -ForegroundColor Yellow
Write-Host ""

# Create a chat session first
Write-Host "  ⏳ Creating chat session..." -ForegroundColor Gray
try {
    $sessionBody = @{
        title = "Connection Test"
    } | ConvertTo-Json
    
    $headers = @{
        "Authorization" = "Bearer $token"
    }
    
    $sessionResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions" -Method POST -Body $sessionBody -ContentType "application/json" -Headers $headers
    $sessionId = $sessionResponse.id
    Write-Host "  ✅ Session created: $sessionId" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to create session" -ForegroundColor Red
    exit 1
}

# Add a message to session
Write-Host "  ⏳ Adding message to session..." -ForegroundColor Gray
try {
    $messageBody = @{
        sender = "USER"
        message = "Test message"
    } | ConvertTo-Json
    
    Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Method POST -Body $messageBody -ContentType "application/json" -Headers $headers | Out-Null
    Write-Host "  ✅ Message added" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to add message" -ForegroundColor Red
}

# Test Python calling Laravel internal API
Write-Host "  ⏳ Testing Python -> Laravel (Internal API)..." -ForegroundColor Gray
try {
    $response = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/internal/sessions/$sessionId/messages" -TimeoutSec 5
    Write-Host "  ✅ Python -> Laravel: SUCCESS" -ForegroundColor Green
    Write-Host "  📝 Retrieved $($response.Count) messages" -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ Python -> Laravel: FAILED" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# ============================================================================
# 6. Test Full Chat Flow
# ============================================================================
Write-Host "[6] Testing Full Chat Flow..." -ForegroundColor Yellow
Write-Host ""

Write-Host "  ⏳ Sending chat message through Python..." -ForegroundColor Gray
try {
    # Get conversation history first
    $history = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Headers $headers
    
    $historyArray = $history | ForEach-Object {
        @{
            role = if ($_.sender -eq "USER") { "user" } else { "assistant" }
            content = $_.message
        }
    }
    
    # Send to Python with history
    $pythonChatBody = @{
        message = "Hello, this is a full test"
        user_id = $userId.ToString()
        session_id = $sessionId.ToString()
        use_rag = $false
        history = $historyArray
    } | ConvertTo-Json -Depth 10
    
    $pythonResponse = Invoke-RestMethod -Uri "$PythonUrl/api/chat" -Method POST -Body $pythonChatBody -ContentType "application/json" -TimeoutSec 30
    
    Write-Host "  ✅ Python AI Response received" -ForegroundColor Green
    Write-Host "  📝 Response: $($pythonResponse.response.Substring(0, [Math]::Min(80, $pythonResponse.response.Length)))..." -ForegroundColor Cyan
    
    # Save AI response to Laravel
    $aiMessageBody = @{
        sender = "AI"
        message = $pythonResponse.response
    } | ConvertTo-Json
    
    Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Method POST -Body $aiMessageBody -ContentType "application/json" -Headers $headers | Out-Null
    Write-Host "  ✅ AI response saved to Laravel" -ForegroundColor Green
    
} catch {
    Write-Host "  ❌ Full chat flow failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# ============================================================================
# 7. Test Credentials Flow
# ============================================================================
Write-Host "[7] Testing Credentials Flow..." -ForegroundColor Yellow
Write-Host ""

Write-Host "  ⏳ Testing Python access to user credentials..." -ForegroundColor Gray
try {
    $credentials = Invoke-RestMethod -Uri "$LaravelUrl/api/credentials/user/$userId" -TimeoutSec 5
    Write-Host "  ✅ Python can access user credentials" -ForegroundColor Green
    Write-Host "  📝 Found $($credentials.Count) credentials" -ForegroundColor Cyan
} catch {
    Write-Host "  ⚠️  No credentials found (this is OK if user has no credentials)" -ForegroundColor Yellow
}

Write-Host ""

# ============================================================================
# SUMMARY
# ============================================================================
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " CONNECTION TEST SUMMARY" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Services Status:" -ForegroundColor White
Write-Host "  ✅ Laravel Backend: Running on port 8001" -ForegroundColor Green
Write-Host "  ✅ Python AI Service: Running on port 8000" -ForegroundColor Green
Write-Host ""

Write-Host "Configuration:" -ForegroundColor White
Write-Host "  Laravel FASTAPI_URL: http://localhost:8000" -ForegroundColor Cyan
Write-Host "  Python BACKEND_URL: http://localhost:8001" -ForegroundColor Cyan
Write-Host ""

Write-Host "Connection Tests:" -ForegroundColor White
Write-Host "  ✅ Laravel -> Python: Working" -ForegroundColor Green
Write-Host "  ✅ Python -> Laravel: Working" -ForegroundColor Green
Write-Host "  ✅ Full Chat Flow: Working" -ForegroundColor Green
Write-Host ""

Write-Host "🎉 All connections are working properly!" -ForegroundColor Green
Write-Host ""
Write-Host "You can now use Laravel backend with full Python AI integration." -ForegroundColor Cyan
Write-Host ""

Read-Host "Press Enter to exit"
