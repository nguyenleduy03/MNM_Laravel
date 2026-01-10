<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get all chat sessions for user
     * GET /api/chat/sessions
     */
    public function sessions(Request $request)
    {
        $user = $request->user();

        $sessions = ChatSession::where('user_id', $user->id)
            ->with('lastMessage')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($sessions->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->title,
            'lastMessage' => $s->lastMessage?->message,
            'createdAt' => $s->created_at->toISOString(),
        ]));
    }

    /**
     * Create new chat session
     * POST /api/chat/sessions
     */
    public function createSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $session = ChatSession::create([
            'user_id' => $user->id,
            'title' => $request->title ?? 'New Chat',
        ]);

        return response()->json([
            'id' => $session->id,
            'title' => $session->title,
            'createdAt' => $session->created_at->toISOString(),
        ], 201);
    }

    /**
     * Get messages in a session
     * GET /api/chat/sessions/{sessionId}/messages
     */
    public function messages(Request $request, $sessionId)
    {
        $user = $request->user();

        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $messages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('timestamp')
            ->get();

        return response()->json($messages->map(fn($m) => [
            'id' => $m->id,
            'sender' => $m->sender,
            'message' => $m->message,
            'timestamp' => $m->timestamp->toISOString(),
        ]));
    }

    /**
     * Add message to session (same as Spring Boot)
     * POST /api/chat/sessions/{sessionId}/messages
     * Body: { "sender": "USER"|"AI", "message": "..." }
     */
    public function sendMessage(Request $request, $sessionId)
    {
        $validator = Validator::make($request->all(), [
            'sender' => 'required|string|in:USER,AI',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Save message (same as Spring Boot - just save, don't call AI)
        $chatMessage = ChatMessage::create([
            'session_id' => $sessionId,
            'sender' => $request->sender,
            'message' => $request->message,
        ]);

        // Update session title if first user message
        if ($request->sender === 'USER' && $session->title === 'New Chat') {
            $session->title = mb_substr($request->message, 0, 50);
            $session->save();
        }

        return response()->json([
            'id' => $chatMessage->id,
            'sender' => $chatMessage->sender,
            'message' => $chatMessage->message,
            'timestamp' => $chatMessage->timestamp->toISOString(),
        ]);
    }

    /**
     * Delete chat session
     * DELETE /api/chat/sessions/{sessionId}
     */
    public function deleteSession(Request $request, $sessionId)
    {
        $user = $request->user();

        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Delete all messages first
        ChatMessage::where('session_id', $sessionId)->delete();
        
        $session->delete();

        return response()->json(['message' => 'Đã xóa phiên chat']);
    }

    /**
     * Quick chat without session (for simple queries)
     * POST /api/chat/quick
     */
    public function quickChat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();
        $fastApiUrl = env('FASTAPI_URL', 'http://localhost:8000');

        try {
            $response = Http::timeout(60)->post("{$fastApiUrl}/api/chat", [
                'message' => $request->message,
                'user_id' => (string) $user->id,
                'use_rag' => true,
            ]);

            if (!$response->successful()) {
                return $this->error('AI service error', 500);
            }

            return response()->json([
                'response' => $response->json('response') ?? $response->json('message'),
            ]);

        } catch (\Exception $e) {
            return $this->error('Failed to connect to AI service', 500);
        }
    }

    /**
     * Internal API - Get messages without auth (for Python service)
     * GET /api/chat/internal/sessions/{sessionId}/messages
     */
    public function messagesInternal(Request $request, $sessionId)
    {
        $messages = ChatMessage::where('session_id', $sessionId)
            ->orderBy('timestamp')
            ->get();

        return response()->json($messages->map(fn($m) => [
            'id' => $m->id,
            'sender' => $m->sender,
            'message' => $m->message,
            'timestamp' => $m->timestamp->toISOString(),
        ]));
    }
}
