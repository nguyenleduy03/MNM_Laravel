<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Get all system logs (ADMIN only)
     * GET /api/logs
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Check admin role
        if (!$user->isAdmin()) {
            return $this->error('Forbidden - Admin only', 403);
        }

        $logs = SystemLog::with('user:id,username,email')
            ->orderBy('timestamp', 'desc')
            ->limit(1000)
            ->get();

        return response()->json($logs->map(fn($log) => [
            'id' => $log->id,
            'userId' => $log->user_id,
            'username' => $log->user?->username,
            'action' => $log->action,
            'detail' => $log->detail,
            'timestamp' => $log->timestamp?->toISOString(),
        ]));
    }

    /**
     * Get logs for specific user (ADMIN only)
     * GET /api/logs/user/{id}
     */
    public function userLogs(Request $request, $id)
    {
        $user = auth()->user();
        
        // Check admin role
        if (!$user->isAdmin()) {
            return $this->error('Forbidden - Admin only', 403);
        }

        $logs = SystemLog::where('user_id', $id)
            ->orderBy('timestamp', 'desc')
            ->limit(500)
            ->get();

        return response()->json($logs->map(fn($log) => [
            'id' => $log->id,
            'userId' => $log->user_id,
            'action' => $log->action,
            'detail' => $log->detail,
            'timestamp' => $log->timestamp?->toISOString(),
        ]));
    }

    /**
     * Create a log entry (internal use)
     */
    public static function log($userId, $action, $detail = null)
    {
        SystemLog::create([
            'user_id' => $userId,
            'action' => $action,
            'detail' => $detail,
        ]);
    }
}
