<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /**
     * Get all courses
     * GET /api/courses
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get public courses + user's own courses
        $courses = Course::where('is_public', true)
            ->orWhere('created_by', $user->id)
            ->with('creator:id,username,full_name')
            ->withCount('lessons')
            ->withCount('enrolledUsers')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($courses->map(fn($course) => $this->formatCourseResponse($course, $user)));
    }

    /**
     * Get course by ID
     * GET /api/courses/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::with(['creator:id,username,full_name', 'lessons'])
            ->withCount('enrolledUsers')
            ->findOrFail($id);

        // Check access
        if (!$course->is_public && $course->created_by !== $user->id) {
            $isEnrolled = CourseEnrollment::where('user_id', $user->id)
                ->where('course_id', $id)
                ->exists();
            
            if (!$isEnrolled) {
                return $this->error('You do not have access to this course', 403);
            }
        }

        return response()->json($this->formatCourseResponse($course, $user));
    }

    /**
     * Create new course
     * POST /api/courses
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'isPublic' => 'nullable|boolean',
            'accessPassword' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $course = Course::create([
            'title' => $request->title,
            'description' => $request->description,
            'created_by' => $user->id,
            'is_public' => $request->isPublic ?? true,
            'access_password' => $request->accessPassword,
        ]);

        $course->load('creator:id,username,full_name');

        return response()->json($this->formatCourseResponse($course, $user), 201);
    }

    /**
     * Update course
     * PUT /api/courses/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        // Check ownership
        if ($course->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to update this course', 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'isPublic' => 'nullable|boolean',
            'accessPassword' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if ($request->has('title')) {
            $course->title = $request->title;
        }
        if ($request->has('description')) {
            $course->description = $request->description;
        }
        if ($request->has('isPublic')) {
            $course->is_public = $request->isPublic;
        }
        if ($request->has('accessPassword')) {
            $course->access_password = $request->accessPassword;
        }

        $course->save();
        $course->load('creator:id,username,full_name');

        return response()->json($this->formatCourseResponse($course, $user));
    }

    /**
     * Delete course
     * DELETE /api/courses/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        // Check ownership
        if ($course->created_by !== $user->id && !$user->isAdmin()) {
            return $this->error('You do not have permission to delete this course', 403);
        }

        $course->delete();

        return response()->json(['message' => 'Xóa khóa học thành công']);
    }

    /**
     * Enroll in course
     * POST /api/courses/{id}/enroll
     */
    public function enroll(Request $request, $id)
    {
        $user = $request->user();
        $course = Course::findOrFail($id);

        // Check if already enrolled
        $existing = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $id)
            ->first();

        if ($existing) {
            return $this->error('You are already enrolled in this course', 400);
        }

        // Check password for private courses
        if (!$course->is_public && $course->access_password) {
            $password = $request->input('password');
            if ($password !== $course->access_password) {
                return $this->error('Invalid access password', 403);
            }
        }

        CourseEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $id,
        ]);

        $course->load('creator:id,username,full_name');

        return response()->json($this->formatCourseResponse($course, $user));
    }

    /**
     * Unenroll from course
     * DELETE /api/courses/{id}/unenroll
     */
    public function unenroll(Request $request, $id)
    {
        $user = $request->user();

        $enrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $id)
            ->first();

        if (!$enrollment) {
            return $this->error('You are not enrolled in this course', 400);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Đã hủy đăng ký khóa học']);
    }

    /**
     * Get my enrolled courses
     * GET /api/courses/my-enrollments
     */
    public function myEnrollments(Request $request)
    {
        $user = $request->user();

        $courses = Course::whereHas('enrolledUsers', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with('creator:id,username,full_name')
        ->withCount('lessons')
        ->get();

        return response()->json($courses->map(fn($course) => $this->formatCourseResponse($course, $user)));
    }

    /**
     * Get my courses (created or enrolled based on role)
     * GET /api/courses/my-courses
     */
    public function myCourses(Request $request)
    {
        $user = $request->user();

        if ($user->isTeacher() || $user->isAdmin()) {
            // Teachers see courses they created
            $courses = Course::where('created_by', $user->id)
                ->with('creator:id,username,full_name')
                ->withCount('lessons')
                ->withCount('enrolledUsers')
                ->get();
        } else {
            // Students see enrolled courses
            $courses = Course::whereHas('enrolledUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('creator:id,username,full_name')
            ->withCount('lessons')
            ->get();
        }

        return response()->json($courses->map(fn($course) => $this->formatCourseResponse($course, $user)));
    }

    /**
     * Format course response (same as Spring Boot CourseResponse)
     */
    private function formatCourseResponse(Course $course, $user): array
    {
        $isEnrolled = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        return [
            'id' => $course->id,
            'title' => $course->title,
            'description' => $course->description,
            'createdBy' => $course->created_by,
            'creatorName' => $course->creator?->full_name ?? $course->creator?->username,
            'isPublic' => $course->is_public,
            'hasPassword' => !empty($course->access_password),
            'lessonCount' => $course->lessons_count ?? $course->lessons()->count(),
            'enrolledCount' => $course->enrolled_users_count ?? $course->enrolledUsers()->count(),
            'isEnrolled' => $isEnrolled,
            'isOwner' => $course->created_by === $user->id,
            'createdAt' => $course->created_at?->toISOString(),
            'updatedAt' => $course->updated_at?->toISOString(),
        ];
    }
}
