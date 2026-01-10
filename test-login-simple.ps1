# Test Login Simple
Write-Host "Testing Laravel Login..." -ForegroundColor Cyan
Write-Host ""

$LaravelUrl = "http://localhost:8001"

# Test 1: Health check
Write-Host "[1] Health Check..." -ForegroundColor Yellow
try {
    $health = Invoke-RestMethod -Uri "$LaravelUrl/api/health"
    Write-Host "  ✅ Laravel is running" -ForegroundColor Green
    Write-Host "  Response: $($health | ConvertTo-Json)" -ForegroundColor Gray
} catch {
    Write-Host "  ❌ Laravel is NOT running" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Test 2: Login
Write-Host "[2] Testing Login..." -ForegroundColor Yellow
Write-Host "  Username: testuser" -ForegroundColor Gray
Write-Host "  Password: 123456" -ForegroundColor Gray

$loginBody = @{
    username = "testuser"
    password = "123456"
} | ConvertTo-Json

Write-Host "  Request Body: $loginBody" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$LaravelUrl/api/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    
    Write-Host "  ✅ Login SUCCESS!" -ForegroundColor Green
    Write-Host ""
    Write-Host "  Token: $($response.token.Substring(0,50))..." -ForegroundColor Cyan
    Write-Host "  User ID: $($response.user.id)" -ForegroundColor Cyan
    Write-Host "  Username: $($response.user.username)" -ForegroundColor Cyan
    Write-Host "  Email: $($response.user.email)" -ForegroundColor Cyan
    Write-Host "  Role: $($response.user.role)" -ForegroundColor Cyan
    
} catch {
    Write-Host "  ❌ Login FAILED!" -ForegroundColor Red
    Write-Host ""
    Write-Host "  Status Code: $($_.Exception.Response.StatusCode.value__)" -ForegroundColor Red
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
    
    if ($_.ErrorDetails) {
        Write-Host "  Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
    
    Write-Host ""
    Write-Host "Possible reasons:" -ForegroundColor Yellow
    Write-Host "  1. User 'testuser' does not exist" -ForegroundColor White
    Write-Host "  2. Password is incorrect" -ForegroundColor White
    Write-Host "  3. Laravel database connection issue" -ForegroundColor White
    Write-Host ""
    Write-Host "To create test user, run in Laravel terminal:" -ForegroundColor Yellow
    Write-Host "  php artisan tinker" -ForegroundColor White
    Write-Host "  User::create(['username'=>'testuser','email'=>'test@test.com','password'=>Hash::make('123456'),'full_name'=>'Test User','role'=>'STUDENT']);" -ForegroundColor White
}

Write-Host ""
Read-Host "Press Enter to exit"
