# Script to add TVU credential via API
param(
    [Parameter(Mandatory=$true)]
    [string]$Token,
    
    [Parameter(Mandatory=$true)]
    [string]$TvuUsername,
    
    [Parameter(Mandatory=$true)]
    [string]$TvuPassword
)

$body = @{
    serviceName = "TVU Portal"
    serviceUrl = "https://ttsv.tvu.edu.vn"
    username = $TvuUsername
    password = $TvuPassword
    category = "EDUCATION"
    purpose = "Thời khóa biểu"
    description = "Tài khoản TVU để xem thời khóa biểu"
    serviceType = "WEB"
    tags = @("school", "tvu", "schedule")
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $Token"
    "Content-Type" = "application/json"
}

Write-Host "Adding TVU credential..." -ForegroundColor Yellow

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8001/api/credentials" -Method Post -Body $body -Headers $headers
    Write-Host "✅ Credential added successfully!" -ForegroundColor Green
    Write-Host "Credential ID: $($response.id)" -ForegroundColor Cyan
    Write-Host "Service: $($response.serviceName)" -ForegroundColor Cyan
    Write-Host "Username: $($response.username)" -ForegroundColor Cyan
} catch {
    Write-Host "❌ Error: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.ErrorDetails.Message) {
        Write-Host "Details: $($_.ErrorDetails.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Usage example:" -ForegroundColor Yellow
Write-Host '.\add-tvu-credential.ps1 -Token "your_jwt_token" -TvuUsername "110122061" -TvuPassword "160221011"'
