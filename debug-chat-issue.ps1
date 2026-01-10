# Debug Chat Issue
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " DEBUG CHAT ISSUE" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$LaravelUrl = "http://localhost:8001"

# Login
Write-Host "[1] Logging in..." -ForegroundColor Yellow
try {
    $loginBody = @{
        username = "testuser"
        password = "123456"
    } | ConvertTo-Json
    
    $loginResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    $token = $loginResponse.token
    $userId = $loginResponse.user.id
    Write-Host "  ✅ Login successful" -ForegroundColor Green
    Write-Host "  User ID: $userId" -ForegroundColor Cyan
    Write-Host "  Token: $($token.Substring(0,30))..." -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ Login failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

$headers = @{
    "Authorization" = "Bearer $token"
}

Write-Host ""

# Get sessions
Write-Host "[2] Getting chat sessions..." -ForegroundColor Yellow
try {
    $sessions = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions" -Headers $headers
    Write-Host "  ✅ Found $($sessions.Count) sessions" -ForegroundColor Green
    
    if ($sessions.Count -gt 0) {
        $sessionId = $sessions[0].id
        Write-Host "  Using session ID: $sessionId" -ForegroundColor Cyan
    } else {
        Write-Host "  Creating new session..." -ForegroundColor Gray
        $newSession = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions" -Method POST -Body (@{title="Debug Session"} | ConvertTo-Json) -ContentType "application/json" -Headers $headers
        $sessionId = $newSession.id
        Write-Host "  ✅ Created session ID: $sessionId" -ForegroundColor Green
    }
} catch {
    Write-Host "  ❌ Failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "  Response: $($_.ErrorDetails.Message)" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Test sending message
Write-Host "[3] Testing send message..." -ForegroundColor Yellow
try {
    $messageBody = @{
        sender = "USER"
        message = "Test message from debug script"
    } | ConvertTo-Json
    
    Write-Host "  Request URL: $LaravelUrl/api/chat/sessions/$sessionId/messages" -ForegroundColor Gray
    Write-Host "  Request Body: $messageBody" -ForegroundColor Gray
    
    $response = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Method POST -Body $messageBody -ContentType "application/json" -Headers $headers
    
    Write-Host "  ✅ Message sent successfully!" -ForegroundColor Green
    Write-Host "  Message ID: $($response.id)" -ForegroundColor Cyan
    Write-Host "  Sender: $($response.sender)" -ForegroundColor Cyan
    Write-Host "  Message: $($response.message)" -ForegroundColor Cyan
} catch {
    Write-Host "  ❌ Failed to send message" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails) {
        Write-Host "  Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
    exit 1
}

Write-Host ""

# Get messages
Write-Host "[4] Getting messages..." -ForegroundColor Yellow
try {
    $messages = Invoke-RestMethod -Uri "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Headers $headers
    Write-Host "  ✅ Found $($messages.Count) messages" -ForegroundColor Green
    
    foreach ($msg in $messages) {
        Write-Host "  - [$($msg.sender)] $($msg.message)" -ForegroundColor Cyan
    }
} catch {
    Write-Host "  ❌ Failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host " DEBUG COMPLETE" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

Read-Host "Press Enter to exit"
