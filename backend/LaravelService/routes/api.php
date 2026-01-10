<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FlashcardController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\SchoolCredentialController;
use App\Http\Controllers\LogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes are prefixed with /api
| Same endpoints as Spring Boot for frontend compatibility
*/

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'Laravel API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// ============================================================================
// AUTH ROUTES (Public)
// ============================================================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected auth routes
    Route::middleware('jwt.auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

// ============================================================================
// INTERNAL API (No auth - for Python service)
// ============================================================================
Route::prefix('chat/internal')->group(function () {
    Route::get('/sessions/{sessionId}/messages', [ChatController::class, 'messagesInternal']);
});

// Credentials internal API (no auth - for Python service)
Route::prefix('credentials')->group(function () {
    Route::get('/user/{userId}', [CredentialController::class, 'getByUserId']);
    Route::get('/{id}/decrypt', [CredentialController::class, 'getDecrypted']);
});

// Google OAuth internal API (no auth - for Python service)
Route::prefix('users')->group(function () {
    Route::post('/{userId}/google-tokens', [GoogleOAuthController::class, 'saveTokens']);
    Route::get('/{userId}/google-tokens', [GoogleOAuthController::class, 'getTokens']);
    Route::delete('/{userId}/google-tokens', [GoogleOAuthController::class, 'deleteTokens']);
    Route::get('/{userId}/google-status', [GoogleOAuthController::class, 'getStatus']);
});

// ============================================================================
// PROTECTED ROUTES (Require JWT)
// ============================================================================
Route::middleware('jwt.auth')->group(function () {
    
    // ========================================================================
    // COURSES
    // ========================================================================
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::post('/', [CourseController::class, 'store']);
        Route::get('/my-enrollments', [CourseController::class, 'myEnrollments']);
        Route::get('/my-courses', [CourseController::class, 'myCourses']);
        Route::get('/{id}', [CourseController::class, 'show']);
        Route::put('/{id}', [CourseController::class, 'update']);
        Route::delete('/{id}', [CourseController::class, 'destroy']);
        Route::post('/{id}/enroll', [CourseController::class, 'enroll']);
        Route::delete('/{id}/unenroll', [CourseController::class, 'unenroll']);
        
        // Lessons under course
        Route::get('/{courseId}/lessons', [LessonController::class, 'index']);
        Route::post('/{courseId}/lessons', [LessonController::class, 'store']);
    });

    // ========================================================================
    // LESSONS
    // ========================================================================
    Route::prefix('lessons')->group(function () {
        Route::get('/{id}', [LessonController::class, 'show']);
        Route::put('/{id}', [LessonController::class, 'update']);
        Route::delete('/{id}', [LessonController::class, 'destroy']);
        Route::post('/{id}/complete', [LessonController::class, 'complete']);
    });

    // ========================================================================
    // QUIZZES
    // ========================================================================
    Route::prefix('quizzes')->group(function () {
        Route::get('/', [QuizController::class, 'index']);
        Route::post('/', [QuizController::class, 'store']);
        Route::post('/generate', [QuizController::class, 'generate']);
        Route::get('/{id}', [QuizController::class, 'show']);
        Route::delete('/{id}', [QuizController::class, 'destroy']);
        Route::post('/{id}/submit', [QuizController::class, 'submit']);
        Route::get('/{id}/results', [QuizController::class, 'results']);
    });

    // Alias for quiz (Spring Boot compatibility)
    Route::prefix('quiz')->group(function () {
        Route::get('/', [QuizController::class, 'index']);
        Route::post('/generate', [QuizController::class, 'generate']);
        Route::post('/create', [QuizController::class, 'store']);
        Route::get('/lesson/{lessonId}', [QuizController::class, 'byLesson']);
        Route::get('/{id}', [QuizController::class, 'show']);
        Route::post('/{id}/submit', [QuizController::class, 'submit']);
    });

    // ========================================================================
    // CHAT
    // ========================================================================
    Route::prefix('chat')->group(function () {
        Route::get('/sessions', [ChatController::class, 'sessions']);
        Route::post('/sessions', [ChatController::class, 'createSession']);
        Route::get('/sessions/{sessionId}/messages', [ChatController::class, 'messages']);
        Route::post('/sessions/{sessionId}/messages', [ChatController::class, 'sendMessage']);
        Route::delete('/sessions/{sessionId}', [ChatController::class, 'deleteSession']);
        Route::post('/quick', [ChatController::class, 'quickChat']);
        
        // Internal API for Python service (no auth required - handled separately)
    });

    // ========================================================================
    // FLASHCARDS
    // ========================================================================
    Route::prefix('flashcards')->group(function () {
        // Decks
        Route::get('/decks', [FlashcardController::class, 'decks']);
        Route::post('/decks', [FlashcardController::class, 'createDeck']);
        Route::get('/decks/{id}', [FlashcardController::class, 'getDeck']);
        Route::put('/decks/{id}', [FlashcardController::class, 'updateDeck']);
        Route::delete('/decks/{id}', [FlashcardController::class, 'deleteDeck']);
        
        // Cards in deck
        Route::get('/decks/{deckId}/cards', [FlashcardController::class, 'getCardsInDeck']);
        Route::post('/decks/{deckId}/cards', [FlashcardController::class, 'addCard']);
        Route::get('/decks/{deckId}/study', [FlashcardController::class, 'getStudyCards']);
        
        // Individual cards
        Route::get('/cards/{id}', [FlashcardController::class, 'getCard']);
        Route::put('/cards/{id}', [FlashcardController::class, 'updateCard']);
        Route::delete('/cards/{id}', [FlashcardController::class, 'deleteCard']);
        
        // Study mode
        Route::get('/study/due', [FlashcardController::class, 'getDueCards']);
        Route::get('/study/new', [FlashcardController::class, 'getNewCards']);
        Route::post('/study/review', [FlashcardController::class, 'submitReview']);
        
        // Stats
        Route::get('/stats/deck/{deckId}', [FlashcardController::class, 'getDeckStats']);
        Route::get('/stats/overview', [FlashcardController::class, 'getOverviewStats']);
        
        // Generate
        Route::post('/generate', [FlashcardController::class, 'generate']);
    });

    // ========================================================================
    // SCHEDULES
    // ========================================================================
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::get('/all', [ScheduleController::class, 'index']); // Alias
        Route::get('/today', [ScheduleController::class, 'today']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::post('/bulk', [ScheduleController::class, 'bulk']);
        Route::get('/day/{dayOfWeek}', [ScheduleController::class, 'byDay']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        Route::delete('/all', [ScheduleController::class, 'deleteAll']);
    });

    // ========================================================================
    // MATERIALS
    // ========================================================================
    Route::get('/courses/{courseId}/materials', [MaterialController::class, 'byCourse']);
    Route::get('/courses/{courseId}/materials/general', [MaterialController::class, 'generalMaterials']);
    Route::get('/lessons/{lessonId}/materials', [MaterialController::class, 'byLesson']);
    Route::post('/materials/upload', [MaterialController::class, 'upload']);
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy']);

    // ========================================================================
    // PROGRESS
    // ========================================================================
    Route::prefix('progress')->group(function () {
        Route::post('/lesson', [ProgressController::class, 'updateLessonProgress']);
        Route::get('/lesson/{lessonId}', [ProgressController::class, 'getLessonProgress']);
        Route::get('/course/{courseId}', [ProgressController::class, 'getCourseProgress']);
        Route::get('/my-courses', [ProgressController::class, 'getMyAllCourseProgress']);
    });

    // ========================================================================
    // TEACHER
    // ========================================================================
    Route::prefix('teacher')->group(function () {
        Route::get('/courses/{courseId}/students', [TeacherController::class, 'getCourseStudents']);
        Route::delete('/courses/{courseId}/students/{studentId}', [TeacherController::class, 'removeStudent']);
        Route::get('/my-courses', [TeacherController::class, 'myCourses']);
        Route::get('/courses/{courseId}/students/{studentId}/detail', [TeacherController::class, 'getStudentDetail']);
        Route::get('/courses/{courseId}/analytics', [TeacherController::class, 'getCourseAnalytics']);
    });

    // ========================================================================
    // ADMIN (requires ADMIN role)
    // ========================================================================
    Route::prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/stats/users-by-role', [AdminController::class, 'usersByRole']);
        Route::get('/stats/activity', [AdminController::class, 'recentActivity']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/search', [AdminController::class, 'searchUsers']);
        Route::get('/users/filter', [AdminController::class, 'filterByRole']);
        Route::get('/users/recent', [AdminController::class, 'recentUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUser']);
        Route::put('/users/{id}/role', [AdminController::class, 'changeRole']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
    });

    // ========================================================================
    // CREDENTIALS
    // ========================================================================
    Route::prefix('credentials')->group(function () {
        Route::get('/', [CredentialController::class, 'index']);
        Route::post('/', [CredentialController::class, 'store']);
        Route::get('/inactive', [CredentialController::class, 'getInactive']);
        Route::get('/most-used', [CredentialController::class, 'getMostUsed']);
        Route::post('/search', [CredentialController::class, 'search']);
        Route::get('/{id}', [CredentialController::class, 'show']);
        Route::put('/{id}', [CredentialController::class, 'update']);
        Route::delete('/{id}', [CredentialController::class, 'destroy']);
        Route::post('/{id}/use', [CredentialController::class, 'logUsage']);
        Route::get('/{id}/logs', [CredentialController::class, 'getLogs']);
    });

    // ========================================================================
    // SCHOOL CREDENTIALS
    // ========================================================================
    Route::prefix('school-credentials')->group(function () {
        Route::get('/', [SchoolCredentialController::class, 'show']);
        Route::post('/', [SchoolCredentialController::class, 'store']);
        Route::put('/sync', [SchoolCredentialController::class, 'updateSync']);
        Route::delete('/', [SchoolCredentialController::class, 'destroy']);
    });

    // ========================================================================
    // LOGS (ADMIN only)
    // ========================================================================
    Route::prefix('logs')->group(function () {
        Route::get('/', [LogController::class, 'index']);
        Route::get('/user/{id}', [LogController::class, 'userLogs']);
    });
});
