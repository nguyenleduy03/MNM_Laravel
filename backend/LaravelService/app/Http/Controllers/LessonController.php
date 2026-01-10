<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * Get lessons by course
     * GET /api/courses/{courseId}/lessons
     */
    public function index(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        
        $lessons = Lesson::where('course_id', $courseId)
            ->orderBy('order_index')
            ->get();

        $user = $request->user();

        return response()->json($lessons->map(fn($lesson) => $this->formatLessonResponse($lesson, $user)));
    }

    /**
     * Get lesson by ID
     * GET /api/lessons/{id}
     */
    public function show(Request $request, $id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        $user = $request->user();

        // Update progress
        $this->updateProgress($user->id, $lesson->id);

        return response()->json($this->formatLessonResponse($lesson, $user));
    }

    /**
     * Create new lesson
     * POST /api/courses/{courseId}/lessons
     */
    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        $user = $request->user();

        // Check ownership
        if ($course->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to add lessons to this course', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'orderIndex' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Auto set order index if not provided
        $orderIndex = $request->orderIndex ?? Lesson::where('course_id', $courseId)->max('order_index') + 1;

        $lesson = Lesson::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'content' => $request->content,
            'order_index' => $orderIndex,
        ]);

        return response()->json($this->formatLessonResponse($lesson, $user), 201);
    }

    /**
     * Update lesson
     * PUT /api/lessons/{id}
     */
    public function update(Request $request, $id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        $user = $request->user();

        // Check ownership
        if ($lesson->course->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to update this lesson', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'orderIndex' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if ($request->has('title')) {
            $lesson->title = $request->title;
        }
        if ($request->has('content')) {
            $lesson->content = $request->content;
        }
        if ($request->has('orderIndex')) {
            $lesson->order_index = $request->orderIndex;
        }

        $lesson->save();

        return response()->json($this->formatLessonResponse($lesson, $user));
    }

    /**
     * Delete lesson
     * DELETE /api/lessons/{id}
     */
    public function destroy(Request $request, $id)
    {
        $lesson = Lesson::with('course')->findOrFail($id);
        $user = $request->user();

        // Check ownership
        if ($lesson->course->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to delete this lesson', 403);
        }

        $lesson->delete();

        return response()->json(['message' => 'Xóa bài học thành công']);
    }

    /**
     * Mark lesson as completed
     * POST /api/lessons/{id}/complete
     */
    public function complete(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $user = $request->user();

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $id],
            [
                'completed' => true,
                'progress_percent' => 100,
                'last_accessed_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Đã hoàn thành bài học',
            'progress' => [
                'completed' => $progress->completed,
                'progressPercent' => $progress->progress_percent,
            ],
        ]);
    }

    /**
     * Update lesson progress
     */
    private function updateProgress($userId, $lessonId)
    {
        LessonProgress::updateOrCreate(
            ['user_id' => $userId, 'lesson_id' => $lessonId],
            ['last_accessed_at' => now()]
        );
    }

    /**
     * Format lesson response
     */
    private function formatLessonResponse(Lesson $lesson, $user): array
    {
        $progress = LessonProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        return [
            'id' => $lesson->id,
            'courseId' => $lesson->course_id,
            'title' => $lesson->title,
            'content' => $lesson->content,
            'orderIndex' => $lesson->order_index,
            'completed' => $progress?->completed ?? false,
            'progressPercent' => $progress?->progress_percent ?? 0,
            'createdAt' => $lesson->created_at?->toISOString(),
        ];
    }
}
