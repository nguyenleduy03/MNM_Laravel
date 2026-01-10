# Test Laravel Backend - Full Feature Check
# Kiểm tra đầy đủ chức năng Laravel và kết nối Python

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " LARAVEL BACKEND - FULL FEATURE TEST" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$LaravelUrl = "http://localhost:8001"
$PythonUrl = "http://localhost:8000"
$token = $null
$userId = $null
$testResults = @()

function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Url,
        [string]$Method = "GET",
        [object]$Body = $null,
        [hashtable]$Headers = @{}
    )
    
    try {
        $params = @{
            Uri = $Url
            Method = $Method
            Headers = $Headers
            TimeoutSec = 10
            UseBasicParsing = $true
        }
        
        if ($Body) {
            $params.Body = ($Body | ConvertTo-Json -Depth 10)
            $params.ContentType = "application/json"
        }
        
        $response = Invoke-WebRequest @params
        $statusCode = $response.StatusCode
        
        if ($statusCode -ge 200 -and $statusCode -lt 300) {
            Write-Host "  ✅ $Name" -ForegroundColor Green
            $script:testResults += @{Name=$Name; Status="PASS"; Code=$statusCode}
            return $response
        } else {
            Write-Host "  ❌ $Name (Status: $statusCode)" -ForegroundColor Red
            $script:testResults += @{Name=$Name; Status="FAIL"; Code=$statusCode}
            return $null
        }
    } catch {
        Write-Host "  ❌ $Name - Error: $($_.Exception.Message)" -ForegroundColor Red
        $script:testResults += @{Name=$Name; Status="ERROR"; Code=0}
        return $null
    }
}

# ============================================================================
# 1. HEALTH CHECKS
# ============================================================================
Write-Host "[1] HEALTH CHECKS" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "Laravel Health" "$LaravelUrl/api/health"
Test-Endpoint "Python AI Health" "$PythonUrl/health"
Test-Endpoint "Python AI Docs" "$PythonUrl/docs"

Write-Host ""

# ============================================================================
# 2. AUTHENTICATION
# ============================================================================
Write-Host "[2] AUTHENTICATION" -ForegroundColor Yellow
Write-Host ""

# Register (optional - might fail if user exists)
$registerBody = @{
    username = "testuser_laravel"
    email = "testlaravel@test.com"
    password = "123456"
    fullName = "Test Laravel User"
    role = "STUDENT"
}

Write-Host "  ⏳ Registering test user..." -ForegroundColor Gray
try {
    $regResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/auth/register" -Method POST -Body ($registerBody | ConvertTo-Json) -ContentType "application/json" -ErrorAction SilentlyContinue
    Write-Host "  ✅ Register Success" -ForegroundColor Green
} catch {
    Write-Host "  ⚠️  Register Failed (user might exist)" -ForegroundColor Yellow
}

# Login
$loginBody = @{
    username = "testuser"
    password = "123456"
}

Write-Host "  ⏳ Logging in..." -ForegroundColor Gray
try {
    $loginResponse = Invoke-RestMethod -Uri "$LaravelUrl/api/auth/login" -Method POST -Body ($loginBody | ConvertTo-Json) -ContentType "application/json"
    $script:token = $loginResponse.token
    $script:userId = $loginResponse.user.id
    Write-Host "  ✅ Login Success - Token: $($token.Substring(0,20))..." -ForegroundColor Green
    Write-Host "  ✅ User ID: $userId" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Login Failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please create test user first:" -ForegroundColor Yellow
    Write-Host "  Username: testuser" -ForegroundColor White
    Write-Host "  Password: 123456" -ForegroundColor White
    exit 1
}

$authHeaders = @{
    "Authorization" = "Bearer $token"
}

Test-Endpoint "Get Profile" "$LaravelUrl/api/auth/profile" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 3. COURSES
# ============================================================================
Write-Host "[3] COURSES" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Courses" "$LaravelUrl/api/courses" -Headers $authHeaders
Test-Endpoint "My Courses" "$LaravelUrl/api/courses/my-courses" -Headers $authHeaders
Test-Endpoint "My Enrollments" "$LaravelUrl/api/courses/my-enrollments" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 4. CHAT SESSIONS
# ============================================================================
Write-Host "[4] CHAT SESSIONS" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Chat Sessions" "$LaravelUrl/api/chat/sessions" -Headers $authHeaders

# Create session
$createSessionBody = @{
    title = "Test Session Laravel"
}
$sessionResponse = Test-Endpoint "Create Chat Session" "$LaravelUrl/api/chat/sessions" -Method POST -Body $createSessionBody -Headers $authHeaders

if ($sessionResponse) {
    $sessionData = $sessionResponse.Content | ConvertFrom-Json
    $sessionId = $sessionData.id
    Write-Host "  📝 Session ID: $sessionId" -ForegroundColor Cyan
    
    # Get messages
    Test-Endpoint "Get Session Messages" "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Headers $authHeaders
    
    # Add message
    $messageBody = @{
        sender = "USER"
        message = "Test message from Laravel"
    }
    Test-Endpoint "Add Message" "$LaravelUrl/api/chat/sessions/$sessionId/messages" -Method POST -Body $messageBody -Headers $authHeaders
}

Write-Host ""

# ============================================================================
# 5. QUIZ
# ============================================================================
Write-Host "[5] QUIZ" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Quizzes" "$LaravelUrl/api/quiz" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 6. FLASHCARDS
# ============================================================================
Write-Host "[6] FLASHCARDS" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Flashcard Decks" "$LaravelUrl/api/flashcards/decks" -Headers $authHeaders
Test-Endpoint "Flashcard Stats" "$LaravelUrl/api/flashcards/stats/overview" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 7. SCHEDULES
# ============================================================================
Write-Host "[7] SCHEDULES" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Schedules" "$LaravelUrl/api/schedules" -Headers $authHeaders
Test-Endpoint "Today's Schedule" "$LaravelUrl/api/schedules/today" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 8. CREDENTIALS
# ============================================================================
Write-Host "[8] CREDENTIALS" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "List Credentials" "$LaravelUrl/api/credentials?active=true" -Headers $authHeaders
Test-Endpoint "Inactive Credentials" "$LaravelUrl/api/credentials/inactive" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 9. SCHOOL CREDENTIALS
# ============================================================================
Write-Host "[9] SCHOOL CREDENTIALS" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "Get School Credentials" "$LaravelUrl/api/school-credentials" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 10. PROGRESS
# ============================================================================
Write-Host "[10] PROGRESS TRACKING" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "My Course Progress" "$LaravelUrl/api/progress/my-courses" -Headers $authHeaders

Write-Host ""

# ============================================================================
# 11. PYTHON SERVICE INTEGRATION
# ============================================================================
Write-Host "[11] PYTHON SERVICE INTEGRATION" -ForegroundColor Yellow
Write-Host ""

# Test AI Chat
$chatBody = @{
    message = "Hello from Laravel test"
    user_id = $userId.ToString()
    use_rag = $false
}

Write-Host "  ⏳ Testing AI Chat..." -ForegroundColor Gray
try {
    $chatResponse = Invoke-RestMethod -Uri "$PythonUrl/api/chat" -Method POST -Body ($chatBody | ConvertTo-Json) -ContentType "application/json" -TimeoutSec 30
    Write-Host "  ✅ AI Chat Response: $($chatResponse.response.Substring(0, [Math]::Min(50, $chatResponse.response.Length)))..." -ForegroundColor Green
    $script:testResults += @{Name="Python AI Chat"; Status="PASS"; Code=200}
} catch {
    Write-Host "  ❌ AI Chat Failed: $($_.Exception.Message)" -ForegroundColor Red
    $script:testResults += @{Name="Python AI Chat"; Status="ERROR"; Code=0}
}

# Test Models API
Test-Endpoint "Get AI Models" "$PythonUrl/api/models"
Test-Endpoint "Get Groq Models" "$PythonUrl/api/models/groq"

Write-Host ""

# ============================================================================
# 12. INTERNAL APIs (for Python service)
# ============================================================================
Write-Host "[12] INTERNAL APIs (No Auth)" -ForegroundColor Yellow
Write-Host ""

if ($sessionId) {
    Test-Endpoint "Internal: Get Messages" "$LaravelUrl/api/chat/internal/sessions/$sessionId/messages"
}

if ($userId) {
    Test-Endpoint "Internal: Get User Credentials" "$LaravelUrl/api/credentials/user/$userId"
    Test-Endpoint "Internal: Get Google Tokens" "$LaravelUrl/api/users/$userId/google-tokens"
    Test-Endpoint "Internal: Get Google Status" "$LaravelUrl/api/users/$userId/google-status"
}

Write-Host ""

# ============================================================================
# 13. ADMIN APIs (if user is admin)
# ============================================================================
Write-Host "[13] ADMIN APIs" -ForegroundColor Yellow
Write-Host ""

Test-Endpoint "Admin Stats" "$LaravelUrl/api/admin/stats" -Headers $authHeaders
Test-Endpoint "Admin Users" "$LaravelUrl/api/admin/users" -Headers $authHeaders

Write-Host ""

# ============================================================================
# SUMMARY
# ============================================================================
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host " TEST SUMMARY" -ForegroundColor Yellow
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$passCount = ($testResults | Where-Object { $_.Status -eq "PASS" }).Count
$failCount = ($testResults | Where-Object { $_.Status -eq "FAIL" }).Count
$errorCount = ($testResults | Where-Object { $_.Status -eq "ERROR" }).Count
$totalCount = $testResults.Count

Write-Host "Total Tests: $totalCount" -ForegroundColor White
Write-Host "  ✅ Passed: $passCount" -ForegroundColor Green
Write-Host "  ❌ Failed: $failCount" -ForegroundColor Red
Write-Host "  ⚠️  Errors: $errorCount" -ForegroundColor Yellow
Write-Host ""

if ($failCount -gt 0 -or $errorCount -gt 0) {
    Write-Host "Failed/Error Tests:" -ForegroundColor Red
    $testResults | Where-Object { $_.Status -ne "PASS" } | ForEach-Object {
        Write-Host "  - $($_.Name) [$($_.Status)]" -ForegroundColor Red
    }
    Write-Host ""
}

$successRate = [math]::Round(($passCount / $totalCount) * 100, 2)
Write-Host "Success Rate: $successRate%" -ForegroundColor $(if ($successRate -ge 80) { "Green" } elseif ($successRate -ge 50) { "Yellow" } else { "Red" })
Write-Host ""

if ($successRate -ge 80) {
    Write-Host "🎉 Laravel Backend is working well!" -ForegroundColor Green
} elseif ($successRate -ge 50) {
    Write-Host "⚠️  Laravel Backend has some issues" -ForegroundColor Yellow
} else {
    Write-Host "❌ Laravel Backend has major issues" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

Read-Host "Press Enter to exit"
