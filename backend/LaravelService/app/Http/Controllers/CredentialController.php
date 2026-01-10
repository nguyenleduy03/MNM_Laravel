<?php

namespace App\Http\Controllers;

use App\Models\UserCredential;
use App\Models\CredentialUsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CredentialController extends Controller
{
    /**
     * Get credentials by user ID (internal use - no auth)
     */
    public function getByUserId($userId)
    {
        $credentials = UserCredential::where('user_id', $userId)->get();
        
        return response()->json($credentials->map(function ($c) {
            return $this->formatCredential($c, true);
        }));
    }

    /**
     * Get decrypted credential (internal use - no auth)
     */
    public function getDecrypted($id)
    {
        $credential = UserCredential::find($id);
        
        if (!$credential) {
            return response()->json(['error' => 'Not found'], 404);
        }
        
        return response()->json($this->formatCredential($credential, true));
    }

    /**
     * Get all credentials for current user
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $category = $request->query('category');
        $active = $request->query('active', 'true') === 'true';
        
        $query = UserCredential::where('user_id', $user->id);
        
        if ($category) {
            $query->where('category', strtoupper($category));
        } elseif ($active) {
            // Include NULL as active (default behavior)
            $query->where(function($q) {
                $q->where('is_active', true)
                  ->orWhereNull('is_active');
            });
        }
        
        $credentials = $query->get();
        
        return response()->json($credentials->map(function ($c) {
            return $this->formatCredential($c, false);
        }));
    }

    /**
     * Create new credential
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $credential = new UserCredential();
        $credential->user_id = $user->id;
        $credential->service_name = $request->input('serviceName');
        $credential->service_url = $request->input('serviceUrl');
        $credential->service_type = $request->input('serviceType', 'WEB');
        $credential->username = $request->input('username');
        $credential->password = $request->input('password');
        $credential->purpose = $request->input('purpose');
        $credential->description = $request->input('description');
        $credential->category = $request->input('category', 'OTHER');
        $credential->label = $request->input('label');
        $credential->is_active = true; // Set default active
        
        if ($request->has('tags')) {
            $credential->setTagsFromArray($request->input('tags'));
        }
        
        $credential->save();
        
        // Sync to vector DB
        $this->syncToVectorDB($credential, $user->id);
        
        return response()->json($this->formatCredential($credential, false));
    }


    /**
     * Get credential by ID
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $decrypt = $request->query('decrypt', 'false') === 'true';
        
        $credential = UserCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$credential) {
            return response()->json(['error' => 'Not found'], 404);
        }
        
        return response()->json($this->formatCredential($credential, $decrypt));
    }

    /**
     * Update credential
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        
        $credential = UserCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        if (!$credential) {
            return response()->json(['error' => 'Not found'], 404);
        }
        
        if ($request->has('serviceName')) $credential->service_name = $request->input('serviceName');
        if ($request->has('serviceUrl')) $credential->service_url = $request->input('serviceUrl');
        if ($request->has('serviceType')) $credential->service_type = $request->input('serviceType');
        if ($request->has('username')) $credential->username = $request->input('username');
        if ($request->has('password')) $credential->password = $request->input('password');
        if ($request->has('purpose')) $credential->purpose = $request->input('purpose');
        if ($request->has('description')) $credential->description = $request->input('description');
        if ($request->has('category')) $credential->category = $request->input('category');
        if ($request->has('label')) $credential->label = $request->input('label');
        if ($request->has('tags')) $credential->setTagsFromArray($request->input('tags'));
        
        $credential->save();
        
        return response()->json($this->formatCredential($credential, false));
    }

    /**
     * Delete credential
     */
    public function destroy($id)
    {
        $user = auth()->user();
        
        $deleted = UserCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->delete();
        
        if ($deleted) {
            return response()->json(['message' => 'Deleted']);
        }
        
        return response()->json(['error' => 'Not found'], 404);
    }

    /**
     * Search credentials (AI semantic search)
     */
    public function search(Request $request)
    {
        $user = auth()->user();
        $query = $request->input('query');
        
        // Simple search by service name, purpose, description
        $credentials = UserCredential::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('service_name', 'like', "%{$query}%")
                  ->orWhere('purpose', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('label', 'like', "%{$query}%");
            })
            ->get();
        
        return response()->json($credentials->map(function ($c) {
            return $this->formatCredential($c, false);
        }));
    }

    /**
     * Log credential usage
     */
    public function logUsage(Request $request, $id)
    {
        $user = auth()->user();
        
        CredentialUsageLog::create([
            'credential_id' => $id,
            'user_id' => $user->id,
            'action' => $request->input('action'),
            'context' => $request->input('context'),
            'success' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // Update credential usage count
        $credential = UserCredential::find($id);
        if ($credential) {
            $credential->incrementUsage();
        }
        
        return response()->json(['message' => 'Logged']);
    }

    /**
     * Get usage logs
     */
    public function getLogs($id)
    {
        $logs = CredentialUsageLog::where('credential_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($logs);
    }

    /**
     * Get inactive credentials
     */
    public function getInactive(Request $request)
    {
        $user = auth()->user();
        $days = $request->query('days', 90);
        
        $credentials = UserCredential::where('user_id', $user->id)
            ->where(function ($q) use ($days) {
                $q->whereNull('last_used_at')
                  ->orWhere('last_used_at', '<', now()->subDays($days));
            })
            ->get();
        
        return response()->json($credentials->map(function ($c) {
            return $this->formatCredential($c, false);
        }));
    }

    /**
     * Get most used credentials
     */
    public function getMostUsed()
    {
        $user = auth()->user();
        
        $credentials = UserCredential::where('user_id', $user->id)
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();
        
        return response()->json($credentials->map(function ($c) {
            return $this->formatCredential($c, false);
        }));
    }

    /**
     * Sync to vector DB
     */
    private function syncToVectorDB($credential, $userId)
    {
        try {
            Http::post(env('FASTAPI_URL', 'http://localhost:8000') . '/api/credentials/vector/add', [
                'credential_id' => $credential->id,
                'user_id' => $userId,
                'service_name' => $credential->service_name,
                'purpose' => $credential->purpose,
                'description' => $credential->description,
                'category' => $credential->category,
                'tags' => $credential->tags_list,
            ]);
        } catch (\Exception $e) {
            // Log but don't fail
            \Log::warning('Failed to sync to vector DB: ' . $e->getMessage());
        }
    }

    /**
     * Format credential for response
     */
    private function formatCredential($credential, $decrypt = false)
    {
        $data = [
            'id' => $credential->id,
            'userId' => $credential->user_id,
            'serviceName' => $credential->service_name,
            'serviceUrl' => $credential->service_url,
            'serviceType' => $credential->service_type,
            'purpose' => $credential->purpose,
            'description' => $credential->description,
            'category' => $credential->category,
            'tags' => $credential->tags_list,
            'label' => $credential->label,
            'isActive' => $credential->is_active,
            'isShared' => $credential->is_shared,
            'lastUsedAt' => $credential->last_used_at,
            'usageCount' => $credential->usage_count,
            'lastSuccess' => $credential->last_success,
            'createdAt' => $credential->created_at,
            'updatedAt' => $credential->updated_at,
        ];
        
        if ($decrypt) {
            $data['username'] = $credential->getDecryptedUsername();
            $data['password'] = $credential->getDecryptedPassword();
        } else {
            $username = $credential->getDecryptedUsername();
            $data['username'] = $username ? substr($username, 0, min(10, strlen($username))) . '...' : null;
            $data['password'] = '****';
        }
        
        return $data;
    }
}
