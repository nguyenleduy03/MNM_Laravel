<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * Register a new user
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'fullName' => 'nullable|string|max:100',
            'role' => 'nullable|string|in:USER,STUDENT,TEACHER,ADMIN',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'full_name' => $request->fullName,
            'role' => $request->role ?? 'USER',
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'user' => $this->formatUserResponse($user),
        ], 201);
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        // Find user by username
        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid username or password', 401);
        }

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return $this->error('Could not create token', 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $this->formatUserResponse($user),
        ]);
    }

    /**
     * Get current user profile
     * GET /api/auth/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json($this->formatUserResponse($user));
    }

    /**
     * Update user profile
     * PUT /api/auth/update-profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'fullName' => 'nullable|string|max:100',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'avatarUrl' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if ($request->has('fullName')) {
            $user->full_name = $request->fullName;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('avatarUrl')) {
            $user->avatar_url = $request->avatarUrl;
        }

        $user->save();

        return response()->json($this->formatUserResponse($user));
    }

    /**
     * Change password
     * POST /api/auth/change-password
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required|string',
            'newPassword' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        if (!Hash::check($request->currentPassword, $user->password)) {
            return $this->error('Current password is incorrect', 400);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }

    /**
     * Logout (invalidate token)
     * POST /api/auth/logout
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return $this->error('Failed to logout', 500);
        }
    }

    /**
     * Refresh token
     * POST /api/auth/refresh
     */
    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json(['token' => $token]);
        } catch (JWTException $e) {
            return $this->error('Could not refresh token', 401);
        }
    }

    /**
     * Format user response (same as Spring Boot UserResponse)
     */
    private function formatUserResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'fullName' => $user->full_name,
            'role' => $user->role,
            'avatarUrl' => $user->avatar_url,
            'googleConnected' => $user->google_connected ?? false,
            'googleEmail' => $user->google_email,
            'createdAt' => $user->created_at?->toISOString(),
        ];
    }
}
