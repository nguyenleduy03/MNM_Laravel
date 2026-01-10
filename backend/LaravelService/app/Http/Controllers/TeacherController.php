<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Models\QuizResult;
use App\Models\LessonProgress;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    /**
     * Get students in a course
     * GET /api/teacher/courses/{courseId}/students
     */
    public function getCourseStudents(Request $request, $courseId)
    {
        $teacher = $request->user();
        $course = Course::findOrFail($courseId);

        // Check ownership
        if ($course->created_by !== $teacher->id && !$teacher->isAdmin()) {
            return $this->error('You do not have permission to view this course', 403);
        }

        $enrollments = CourseEnrollment::where('course_id', $courseId)
            ->with('user:id,username,email,full_name,avatar_url')
            ->get();

        $students = $enrollments->map(function ($enrollment) use ($courseId) {
            $user = $enrollment->user;
            
            // Get quiz results for this student in this course
            $quizResults = QuizResult::whereHas('quiz', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            })->where('user_id', $user->id)->get();

            $avgScore = $quizResults->count() > 0 ? $quizResults->avg('score') : 0;

            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'fullName' => $user->full_name,
                'avatarUrl' => $user->avatar_url,
                'enrolledAt' => $enrollment->enrolled_at?->toISOString(),
                'quizzesTaken' => $quizResults->count(),
                'averageScore' => round($avgScore, 2),
            ];
        });

        return response()->json([
            'courseId' => $course->id,
            'courseTitle' => $course->title,
            'totalStudents' => $students->count(),
            'students' => $students,
        ]);
    }

    /**
     * Remove student from course
     * DELETE /api/teacher/courses/{courseId}/students/{studentId}
     */
    public function removeStudent(Request $request, $courseId, $studentId)
    {
        $teacher = $request->user();
        $course = Course::findOrFail($courseId);

        // Check ownership
        if ($course->created_by !== $teacher->id && !$teacher->isAdmin()) {
            return $this->error('You do not have permission', 403);
        }

        $enrollment = CourseEnrollment::where('course_id', $courseId)
            ->where('user_id', $studentId)
            ->first();

        if (!$enrollment) {
            return $this->error('Student not found in this course', 404);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Đã xóa sinh viên khỏi khóa học']);
    }

    /**
     * Get all my courses as teacher with students
     * GET /api/teacher/my-courses
     */
    public function myCourses(Request $request)
    {
        $teacher = $request->user();

        $courses = Course::where('created_by', $teacher->id)
            ->withCount('enrolledUsers')
            ->get();

        $result = $courses->map(function ($course) {
            return [
                'courseId' => $course->id,
                'courseTitle' => $course->title,
                'totalStudents' => $course->enrolled_users_count,
                'createdAt' => $course->created_at?->toISOString(),
            ];
        });

        return response()->json($result);
    }

    /**
     * Get student detail with quiz history
     * GET /api/teacher/courses/{courseId}/students/{studentId}/detail
     */
    public function getStudentDetail(Request $request, $courseId, $studentId)
    {
        $teacher = $request->user();
        $course = Course::with('lessons')->findOrFail($courseId);

        // Check ownership
        if ($course->created_by !== $teacher->id && !$teacher->isAdmin()) {
            return $this->error('You do not have permission', 403);
        }

        $student = User::findOrFail($studentId);

        // Get quiz results
        $quizResults = QuizResult::whereHas('quiz', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        })
        ->where('user_id', $studentId)
        ->with('quiz:id,title,difficulty')
        ->orderBy('created_at', 'desc')
        ->get();

        // Get lesson progress
        $lessonProgress = [];
        foreach ($course->lessons as $lesson) {
            $progress = LessonProgress::where('user_id', $studentId)
                ->where('lesson_id', $lesson->id)
                ->first();

            $lessonProgress[] = [
                'lessonId' => $lesson->id,
                'lessonTitle' => $lesson->title,
                'completed' => $progress?->completed ?? false,
                'progressPercent' => $progress?->progress_percent ?? 0,
                'lastAccessedAt' => $progress?->last_accessed_at?->toISOString(),
            ];
        }

        return response()->json([
            'student' => [
                'id' => $student->id,
                'username' => $student->username,
                'email' => $student->email,
                'fullName' => $student->full_name,
                'avatarUrl' => $student->avatar_url,
            ],
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'quizResults' => $quizResults->map(fn($r) => [
                'id' => $r->id,
                'quizId' => $r->quiz_id,
                'quizTitle' => $r->quiz?->title,
                'score' => $r->score,
                'totalQuestions' => $r->total_questions,
                'correctAnswers' => $r->correct_answers,
                'createdAt' => $r->created_at?->toISOString(),
            ]),
            'lessonProgress' => $lessonProgress,
            'summary' => [
                'totalQuizzes' => $quizResults->count(),
                'averageScore' => $quizResults->count() > 0 ? round($quizResults->avg('score'), 2) : 0,
                'completedLessons' => collect($lessonProgress)->where('completed', true)->count(),
                'totalLessons' => count($lessonProgress),
            ],
        ]);
    }

    /**
     * Get course analytics
     * GET /api/teacher/courses/{courseId}/analytics
     */
    public function getCourseAnalytics(Request $request, $courseId)
    {
        $teacher = $request->user();
        $course = Course::with(['lessons', 'quizzes'])->findOrFail($courseId);

        // Check ownership
        if ($course->created_by !== $teacher->id && !$teacher->isAdmin()) {
            return $this->error('You do not have permission', 403);
        }

        $enrollmentCount = CourseEnrollment::where('course_id', $courseId)->count();

        // Quiz statistics
        $quizStats = [];
        foreach ($course->quizzes as $quiz) {
            $results = QuizResult::where('quiz_id', $quiz->id)->get();
            $quizStats[] = [
                'quizId' => $quiz->id,
                'quizTitle' => $quiz->title,
                'totalAttempts' => $results->count(),
                'averageScore' => $results->count() > 0 ? round($results->avg('score'), 2) : 0,
                'highestScore' => $results->max('score') ?? 0,
                'lowestScore' => $results->min('score') ?? 0,
            ];
        }

        return response()->json([
            'courseId' => $course->id,
            'courseTitle' => $course->title,
            'totalStudents' => $enrollmentCount,
            'totalLessons' => $course->lessons->count(),
            'totalQuizzes' => $course->quizzes->count(),
            'quizStatistics' => $quizStats,
        ]);
    }
}
