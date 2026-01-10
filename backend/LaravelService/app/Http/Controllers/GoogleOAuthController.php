<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class GoogleOAuthController extends Controller
{
    /**
     * Save Google OAuth tokens
     * POST /api/users/{userId}/google-tokens
     */
    public function saveTokens(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        
        $user->google_access_token = $request->input('accessToken');
        $user->google_refresh_token = $request->input('refreshToken');
        $user->google_token_expiry = $request->input('expiresAt');
        $user->google_connected = true;
        $user->google_email = $request->input('email');
        $user->save();
        
        return response()->json(['message' => 'Tokens saved']);
    }

    /**
     * Get Google OAuth tokens
     * GET /api/users/{userId}/google-tokens
     */
    public function getTokens($userId)
    {
        $user = User::findOrFail($userId);
        
        return response()->json([
            'accessToken' => $user->google_access_token,
            'refreshToken' => $user->google_refresh_token,
            'expiresAt' => $user->google_token_expiry,
            'email' => $user->google_email,
            'connected' => $user->google_connected ?? false,
        ]);
    }

    /**
     * Delete Google OAuth tokens
     * DELETE /api/users/{userId}/google-tokens
     */
    public function deleteTokens($userId)
    {
        $user = User::findOrFail($userId);
        
        $user->google_access_token = null;
        $user->google_refresh_token = null;
        $user->google_token_expiry = null;
        $user->google_connected = false;
        $user->google_email = null;
        $user->save();
        
        return response()->json(['message' => 'Tokens deleted']);
    }

    /**
     * Get connection status
     * GET /api/users/{userId}/google-status
     */
    public function getStatus($userId)
    {
        $user = User::findOrFail($userId);
        
        return response()->json([
            'connected' => $user->google_connected ?? false,
            'email' => $user->google_email,
        ]);
    }
}
