<?php

namespace App\Http\Controllers;

use App\Models\SchoolCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class SchoolCredentialController extends Controller
{
    /**
     * Save school credentials
     * POST /api/school-credentials
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolName' => 'required|string|max:100',
            'schoolUrl' => 'required|string|max:500',
            'username' => 'required|string|max:100',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();

        // Check if already exists
        $credential = SchoolCredential::where('user_id', $user->id)->first();

        if ($credential) {
            // Update existing
            $credential->school_name = $request->input('schoolName');
            $credential->school_url = $request->input('schoolUrl');
            $credential->encrypted_username = Crypt::encryptString($request->input('username'));
            $credential->encrypted_password = Crypt::encryptString($request->input('password'));
            $credential->save();
        } else {
            // Create new
            $credential = SchoolCredential::create([
                'user_id' => $user->id,
                'school_name' => $request->input('schoolName'),
                'school_url' => $request->input('schoolUrl'),
                'encrypted_username' => Crypt::encryptString($request->input('username')),
                'encrypted_password' => Crypt::encryptString($request->input('password')),
            ]);
        }

        return response()->json([
            'message' => 'Đã lưu tài khoản trường thành công!',
            'data' => $this->formatResponse($credential),
        ]);
    }

    /**
     * Get school credentials
     * GET /api/school-credentials
     */
    public function show()
    {
        $user = auth()->user();
        $credential = SchoolCredential::where('user_id', $user->id)->first();

        if (!$credential) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($this->formatResponse($credential));
    }

    /**
     * Update last synced time
     * PUT /api/school-credentials/sync
     */
    public function updateSync()
    {
        $user = auth()->user();
        $credential = SchoolCredential::where('user_id', $user->id)->first();

        if ($credential) {
            $credential->last_synced_at = now();
            $credential->save();
        }

        return response()->json(['message' => 'Updated']);
    }

    /**
     * Delete school credentials
     * DELETE /api/school-credentials
     */
    public function destroy()
    {
        $user = auth()->user();
        SchoolCredential::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Đã xóa tài khoản trường']);
    }

    private function formatResponse($credential)
    {
        return [
            'id' => $credential->id,
            'schoolName' => $credential->school_name,
            'schoolUrl' => $credential->school_url,
            'username' => '****',
            'hasPassword' => true,
            'lastSyncedAt' => $credential->last_synced_at,
            'createdAt' => $credential->created_at,
        ];
    }
}
