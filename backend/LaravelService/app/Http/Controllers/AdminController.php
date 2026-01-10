<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Get dashboard stats
     * GET /api/admin/stats
     */
    public function stats(Request $request)
    {
        return response()->json([
            'totalUsers' => User::count(),
            'totalCourses' => Course::count(),
            'totalQuizzes' => Quiz::count(),
            'totalChatSessions' => ChatSession::count(),
            'newUsersToday' => User::whereDate('created_at', today())->count(),
            'newUsersThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
            'newUsersThisMonth' => User::where('created_at', '>=', now()->subMonth())->count(),
        ]);
    }

    /**
     * Get users by role stats
     * GET /api/admin/stats/users-by-role
     */
    public function usersByRole(Request $request)
    {
        return response()->json([
            'USER' => User::where('role', 'USER')->count(),
            'STUDENT' => User::where('role', 'STUDENT')->count(),
            'TEACHER' => User::where('role', 'TEACHER')->count(),
            'ADMIN' => User::where('role', 'ADMIN')->count(),
        ]);
    }

    /**
     * Get recent activity
     * GET /api/admin/stats/activity
     */
    public function recentActivity(Request $request)
    {
        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'username', 'email', 'role', 'created_at']);

        $recentCourses = Course::orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'title', 'created_by', 'created_at']);

        return response()->json([
            'recentUsers' => $recentUsers,
            'recentCourses' => $recentCourses,
        ]);
    }

    /**
     * Get all users with pagination
     * GET /api/admin/users
     */
    public function users(Request $request)
    {
        $page = $request->input('page', 0);
        $size = $request->input('size', 10);
        $sortBy = $request->input('sortBy', 'created_at');
        $sortDir = $request->input('sortDir', 'desc');

        $query = User::orderBy($sortBy, $sortDir);

        $total = $query->count();
        $users = $query->skip($page * $size)->take($size)->get();

        return response()->json([
            'users' => $users->map(fn($u) => $this->formatUserResponse($u)),
            'currentPage' => (int) $page,
            'totalItems' => $total,
            'totalPages' => ceil($total / $size),
        ]);
    }

    /**
     * Search users
     * GET /api/admin/users/search
     */
    public function searchUsers(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $page = $request->input('page', 0);
        $size = $request->input('size', 10);

        $query = User::where('username', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('full_name', 'like', "%{$keyword}%")
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $users = $query->skip($page * $size)->take($size)->get();

        return response()->json([
            'users' => $users->map(fn($u) => $this->formatUserResponse($u)),
            'currentPage' => (int) $page,
            'totalItems' => $total,
            'totalPages' => ceil($total / $size),
        ]);
    }

    /**
     * Filter users by role
     * GET /api/admin/users/filter
     */
    public function filterByRole(Request $request)
    {
        $role = strtoupper($request->input('role', 'USER'));
        $page = $request->input('page', 0);
        $size = $request->input('size', 10);

        $query = User::where('role', $role)->orderBy('created_at', 'desc');

        $total = $query->count();
        $users = $query->skip($page * $size)->take($size)->get();

        return response()->json([
            'users' => $users->map(fn($u) => $this->formatUserResponse($u)),
            'currentPage' => (int) $page,
            'totalItems' => $total,
            'totalPages' => ceil($total / $size),
        ]);
    }

    /**
     * Get user by ID
     * GET /api/admin/users/{id}
     */
    public function getUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        return response()->json($this->formatUserResponse($user));
    }

    /**
     * Change user role
     * PUT /api/admin/users/{id}/role
     */
    public function changeRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:USER,STUDENT,TEACHER,ADMIN',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::findOrFail($id);
        $user->role = strtoupper($request->role);
        $user->save();

        return response()->json([
            'message' => 'Đổi role thành công',
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Delete user
     * DELETE /api/admin/users/{id}
     */
    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Don't allow deleting yourself
        if ($user->id === $request->user()->id) {
            return $this->error('Cannot delete yourself', 400);
        }

        $user->delete();

        return response()->json(['message' => 'Xóa người dùng thành công']);
    }

    /**
     * Get recent users
     * GET /api/admin/users/recent
     */
    public function recentUsers(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json($users->map(fn($u) => $this->formatUserResponse($u)));
    }

    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'fullName' => $user->full_name,
            'avatarUrl' => $user->avatar_url,
            'role' => $user->role,
            'googleConnected' => $user->google_connected ?? false,
            'createdAt' => $user->created_at?->toISOString(),
            'updatedAt' => $user->updated_at?->toISOString(),
        ];
    }
}
