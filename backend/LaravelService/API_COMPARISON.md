# 📊 So sánh API Endpoints: Spring Boot vs Laravel

## ✅ Đã implement đầy đủ

### Auth (`/api/auth`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| POST | /register | ✅ | ✅ |
| POST | /login | ✅ | ✅ |
| GET | /profile | ✅ | ✅ |
| PUT | /update-profile | ✅ | ✅ |
| POST | /change-password | ✅ | ✅ |
| POST | /logout | ✅ | ✅ |
| POST | /refresh | ✅ | ✅ |

### Courses (`/api/courses`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | / | ✅ | ✅ |
| POST | / | ✅ | ✅ |
| GET | /{id} | ✅ | ✅ |
| PUT | /{id} | ✅ | ✅ |
| DELETE | /{id} | ✅ | ✅ |
| POST | /{id}/enroll | ✅ | ✅ |
| DELETE | /{id}/unenroll | ✅ | ✅ |
| GET | /my-enrollments | ✅ | ✅ |
| GET | /my-courses | ✅ | ✅ |

### Lessons (`/api/lessons`, `/api/courses/{id}/lessons`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /courses/{courseId}/lessons | ✅ | ✅ |
| POST | /courses/{courseId}/lessons | ✅ | ✅ |
| GET | /lessons/{id} | ✅ | ✅ |
| PUT | /lessons/{id} | ✅ | ✅ |
| DELETE | /lessons/{id} | ✅ | ✅ |
| POST | /lessons/{id}/complete | ✅ | ✅ |

### Materials (`/api/materials`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /courses/{courseId}/materials | ✅ | ✅ |
| GET | /courses/{courseId}/materials/general | ✅ | ✅ |
| GET | /lessons/{lessonId}/materials | ✅ | ✅ |
| POST | /materials/upload | ✅ | ✅ |
| DELETE | /materials/{id} | ✅ | ✅ |

### Quiz (`/api/quiz`, `/api/quizzes`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /quiz/ | ✅ | ✅ |
| POST | /quiz/generate | ✅ | ✅ |
| POST | /quiz/create | ✅ | ✅ |
| GET | /quiz/lesson/{lessonId} | ✅ | ✅ |
| GET | /quiz/{id} | ✅ | ✅ |
| POST | /quiz/{id}/submit | ✅ | ✅ |
| GET | /quizzes/{id}/results | ✅ | ✅ |
| DELETE | /quizzes/{id} | ✅ | ✅ |

### Chat (`/api/chat`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /sessions | ✅ | ✅ |
| POST | /sessions | ✅ | ✅ |
| GET | /sessions/{id}/messages | ✅ | ✅ |
| POST | /sessions/{id}/messages | ✅ | ✅ |
| DELETE | /sessions/{id} | ✅ | ✅ |
| POST | /quick | ✅ | ✅ |
| GET | /internal/sessions/{id}/messages | ✅ | ✅ |

### Flashcards (`/api/flashcards`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /decks | ✅ | ✅ |
| POST | /decks | ✅ | ✅ |
| GET | /decks/{id} | ✅ | ✅ |
| PUT | /decks/{id} | ✅ | ✅ |
| DELETE | /decks/{id} | ✅ | ✅ |
| GET | /decks/{id}/cards | ✅ | ✅ |
| POST | /decks/{id}/cards | ✅ | ✅ |
| GET | /decks/{id}/study | ✅ | ✅ |
| GET | /cards/{id} | ✅ | ✅ |
| PUT | /cards/{id} | ✅ | ✅ |
| DELETE | /cards/{id} | ✅ | ✅ |
| GET | /study/due | ✅ | ✅ |
| GET | /study/new | ✅ | ✅ |
| POST | /study/review | ✅ | ✅ |
| GET | /stats/deck/{id} | ✅ | ✅ |
| GET | /stats/overview | ✅ | ✅ |
| POST | /generate | ✅ | ✅ |

### Schedules (`/api/schedules`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | / | ✅ | ✅ |
| GET | /all | ✅ | ✅ |
| GET | /today | ✅ | ✅ |
| GET | /day/{dayOfWeek} | ✅ | ✅ |
| POST | / | ✅ | ✅ |
| POST | /bulk | ✅ | ✅ |
| PUT | /{id} | ✅ | ✅ |
| DELETE | /{id} | ✅ | ✅ |
| DELETE | /all | ✅ | ✅ |

### Progress (`/api/progress`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| POST | /lesson | ✅ | ✅ |
| GET | /lesson/{lessonId} | ✅ | ✅ |
| GET | /course/{courseId} | ✅ | ✅ |
| GET | /my-courses | ✅ | ✅ |

### Teacher (`/api/teacher`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /courses/{id}/students | ✅ | ✅ |
| DELETE | /courses/{id}/students/{studentId} | ✅ | ✅ |
| GET | /my-courses | ✅ | ✅ |
| GET | /courses/{id}/students/{studentId}/detail | ✅ | ✅ |
| GET | /courses/{id}/analytics | ✅ | ✅ |

### Admin (`/api/admin`)
| Method | Endpoint | Spring Boot | Laravel |
|--------|----------|-------------|---------|
| GET | /stats | ✅ | ✅ |
| GET | /stats/users-by-role | ✅ | ✅ |
| GET | /stats/activity | ✅ | ✅ |
| GET | /users | ✅ | ✅ |
| GET | /users/search | ✅ | ✅ |
| GET | /users/filter | ✅ | ✅ |
| GET | /users/recent | ✅ | ✅ |
| GET | /users/{id} | ✅ | ✅ |
| PUT | /users/{id}/role | ✅ | ✅ |
| DELETE | /users/{id} | ✅ | ✅ |

## ⚠️ Chưa implement (Optional)

### Google OAuth (`/api/oauth/google`)
| Method | Endpoint | Spring Boot | Laravel | Note |
|--------|----------|-------------|---------|------|
| GET | /auth-url | ✅ | ❌ | Optional |
| GET | /callback | ✅ | ❌ | Optional |
| POST | /token | ✅ | ❌ | Optional |
| POST | /disconnect | ✅ | ❌ | Optional |

### School Credentials (`/api/credentials`)
| Method | Endpoint | Spring Boot | Laravel | Note |
|--------|----------|-------------|---------|------|
| POST | /school | ✅ | ❌ | Optional |
| GET | /school | ✅ | ❌ | Optional |
| DELETE | /school | ✅ | ❌ | Optional |

### System Logs (`/api/logs`)
| Method | Endpoint | Spring Boot | Laravel | Note |
|--------|----------|-------------|---------|------|
| GET | / | ✅ | ❌ | Admin only |

---

## 📈 Tổng kết

| Category | Spring Boot | Laravel | Coverage |
|----------|-------------|---------|----------|
| Auth | 7 | 7 | 100% |
| Courses | 9 | 9 | 100% |
| Lessons | 6 | 6 | 100% |
| Materials | 5 | 5 | 100% |
| Quiz | 8 | 8 | 100% |
| Chat | 7 | 7 | 100% |
| Flashcards | 17 | 17 | 100% |
| Schedules | 9 | 9 | 100% |
| Progress | 4 | 4 | 100% |
| Teacher | 5 | 5 | 100% |
| Admin | 10 | 10 | 100% |
| **Total Core** | **87** | **87** | **100%** |

**Kết luận**: Laravel đã implement đầy đủ 100% các API endpoints cốt lõi của Spring Boot. Frontend có thể chuyển đổi mà không cần sửa code.
