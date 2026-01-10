<?php

namespace App\Http\Controllers;

use App\Models\LessonProgress;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgressController extends Controller
{
    /**
     * Update lesson progress
     * POST /api/progress/lesson
     */
    public function updateLessonProgress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lessonId' => 'required|integer|exists:lessons,id',
            'completed' => 'nullable|boolean',
            'progressPercent' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $request->lessonId],
            [
                'completed' => $request->completed ?? false,
                'progress_percent' => $request->progressPercent ?? 0,
                'last_accessed_at' => now(),
            ]
        );

        return response()->json($this->formatLessonProgressResponse($progress));
    }

    /**
     * Get lesson progress
     * GET /api/progress/lesson/{lessonId}
     */
    public function getLessonProgress(Request $request, $lessonId)
    {
        $user = $request->user();

        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->first();

        if (!$progress) {
            return response()->json([
                'lessonId' => (int) $lessonId,
                'completed' => false,
                'progressPercent' => 0,
                'lastAccessedAt' => null,
            ]);
        }

        return response()->json($this->formatLessonProgressResponse($progress));
    }

    /**
     * Get course progress
     * GET /api/progress/course/{courseId}
     */
    public function getCourseProgress(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::with('lessons')->findOrFail($courseId);

        $totalLessons = $course->lessons->count();
        $completedLessons = 0;
        $totalProgress = 0;

        foreach ($course->lessons as $lesson) {
            $progress = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if ($progress) {
                if ($progress->completed) {
                    $completedLessons++;
                }
                $totalProgress += $progress->progress_percent ?? 0;
            }
        }

        $overallProgress = $totalLessons > 0 ? round($totalProgress / $totalLessons) : 0;

        return response()->json([
            'courseId' => $course->id,
            'courseTitle' => $course->title,
            'totalLessons' => $totalLessons,
            'completedLessons' => $completedLessons,
            'overallProgress' => $overallProgress,
            'isCompleted' => $completedLessons === $totalLessons && $totalLessons > 0,
        ]);
    }

    /**
     * Get all my courses progress
     * GET /api/progress/my-courses
     */
    public function getMyAllCourseProgress(Request $request)
    {
        $user = $request->user();

        // Get enrolled courses
        $enrollments = CourseEnrollment::where('user_id', $user->id)
            ->with('course.lessons')
            ->get();

        $progressList = [];

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            $totalLessons = $course->lessons->count();
            $completedLessons = 0;
            $totalProgress = 0;

            foreach ($course->lessons as $lesson) {
                $progress = LessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                if ($progress) {
                    if ($progress->completed) {
                        $completedLessons++;
                    }
                    $totalProgress += $progress->progress_percent ?? 0;
                }
            }

            $overallProgress = $totalLessons > 0 ? round($totalProgress / $totalLessons) : 0;

            $progressList[] = [
                'courseId' => $course->id,
                'courseTitle' => $course->title,
                'totalLessons' => $totalLessons,
                'completedLessons' => $completedLessons,
                'overallProgress' => $overallProgress,
                'isCompleted' => $completedLessons === $totalLessons && $totalLessons > 0,
                'enrolledAt' => $enrollment->enrolled_at?->toISOString(),
            ];
        }

        return response()->json($progressList);
    }

    private function formatLessonProgressResponse(LessonProgress $progress): array
    {
        return [
            'id' => $progress->id,
            'lessonId' => $progress->lesson_id,
            'completed' => $progress->completed,
            'progressPercent' => $progress->progress_percent,
            'lastAccessedAt' => $progress->last_accessed_at?->toISOString(),
        ];
    }
}
