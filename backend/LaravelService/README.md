# Agent For Edu - Laravel Backend

PHP Backend thay thế cho Spring Boot, sử dụng Laravel 10 + JWT Authentication.

## 📋 So sánh với Spring Boot

### Controllers đã implement (✅ = Hoàn thành, ⚠️ = Chưa có)

| Spring Boot Controller | Laravel Controller | Status |
|----------------------|-------------------|--------|
| AuthController | AuthController | ✅ |
| CourseController | CourseController | ✅ |
| LessonController | LessonController | ✅ |
| QuizController | QuizController | ✅ |
| ChatController | ChatController | ✅ |
| FlashcardController | FlashcardController | ✅ |
| ScheduleController | ScheduleController | ✅ |
| MaterialController | MaterialController | ✅ |
| ProgressController | ProgressController | ✅ |
| TeacherController | TeacherController | ✅ |
| AdminController | AdminController | ✅ |
| UserCredentialController | CredentialController | ✅ |
| GoogleOAuthController | GoogleOAuthController | ✅ |
| SchoolCredentialController | SchoolCredentialController | ✅ |
| LogController | LogController | ✅ |
| TestSchoolCredentialController | - | ⚠️ (Test only - không cần) |

### API Endpoints Coverage: **100%**

## 🔧 Cấu hình chuyển đổi Backend

### Port Configuration
| Service | Port |
|---------|------|
| Laravel | 8001 |
| Spring Boot | 8080 |
| FastAPI (Python AI) | 8000 |
| Frontend | 5173 |

### Files cần thay đổi khi chuyển Backend:

#### 1. Frontend Config
**File:** `fronend_web/src/config/api.ts`
```typescript
export const API_CONFIG = {
  // Đổi sang Laravel: 'http://localhost:8001'
  // Đổi sang Spring Boot: 'http://localhost:8080'
  SPRING_BOOT_URL: 'http://localhost:8001', // <-- THAY ĐỔI Ở ĐÂY
  FASTAPI_URL: 'http://localhost:8000',
  TIMEOUT: 30000,
};
```

#### 2. Python Service Config
**File:** `backend/PythonService/.env`
```env
# Backend URL - Laravel (8001) or Spring Boot (8080)
BACKEND_URL=http://localhost:8001  # <-- THAY ĐỔI Ở ĐÂY
```

#### 3. Python Service Code (đã tự động đọc từ .env)
**File:** `backend/PythonService/main.py`
```python
# Line ~505: Tự động đọc từ .env
BACKEND_URL = os.getenv("BACKEND_URL", "http://localhost:8001")
```

## 🚀 Cách chạy

### Chạy Laravel riêng
```powershell
cd backend/LaravelService
C:\php\php.exe artisan serve --port=8001
```

### Chạy Full Stack với Laravel
```powershell
.\start-fullstack.ps1 -Backend laravel
```

### Chạy Full Stack với Spring Boot (mặc định)
```powershell
.\start-fullstack.ps1
# hoặc
.\start-fullstack.ps1 -Backend springboot
```

## 📁 Cấu trúc thư mục

```
backend/LaravelService/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # 13 Controllers
│   │   └── Middleware/      # JWT, CORS, ForceJson
│   └── Models/              # 14 Eloquent Models
├── config/
│   ├── cors.php            # CORS configuration
│   ├── jwt.php             # JWT settings
│   └── database.php        # MySQL connection
├── routes/
│   └── api.php             # All API routes
├── public/
│   ├── swagger.html        # Swagger UI
│   └── api-docs.json       # OpenAPI spec
└── .env                    # Environment config
```

## 🔐 Authentication

Sử dụng JWT (tymon/jwt-auth) tương tự Spring Boot:
- Login: POST `/api/auth/login` → trả về `{token, user}`
- Register: POST `/api/auth/register` → trả về `{token, user}`
- Profile: GET `/api/auth/profile` (Bearer token required)

## 📊 Database

Sử dụng cùng database với Spring Boot:
- Database: `Agent_Db`
- Tables: Giống hệt Spring Boot entities

## 🧪 Test API

### Swagger UI
```
http://localhost:8001/swagger.html
```

### Health Check
```
http://localhost:8001/api/health
```

### Test với cURL
```bash
# Register
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"test","email":"test@test.com","password":"123456","fullName":"Test"}'

# Login
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"test","password":"123456"}'
```

## ⚠️ Lưu ý quan trọng

1. **Restart services** sau khi thay đổi config
2. **Login lại** trên frontend khi chuyển backend (token khác nhau)
3. **Clear browser cache** nếu gặp lỗi CORS
4. Laravel và Spring Boot dùng **cùng database** nên data được chia sẻ

## 📝 Checklist chuyển đổi Backend

- [ ] Sửa `fronend_web/src/config/api.ts` → đổi port
- [ ] Sửa `backend/PythonService/.env` → đổi BACKEND_URL
- [ ] Restart Python service
- [ ] Restart Frontend
- [ ] Login lại trên frontend
