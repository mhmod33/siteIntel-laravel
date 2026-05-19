<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use App\Services\PythonAgentService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * List all chat sessions for the authenticated user.
     */
    public function index(Request $request)
    {
        $sessions = $request->user()
            ->chatSessions()
            ->withCount('messages')
            ->get();

        return response()->json([
            'sessions' => $sessions
        ]);
    }

    /**
     * Create a new chat session.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $session = $request->user()->chatSessions()->create([
            'title' => $request->input('title', 'New Chat'),
        ]);

        return response()->json([
            'message' => 'Chat session created',
            'session' => $session
        ], 201);
    }

    /**
     * Get a chat session with its full message history.
     */
    public function show(Request $request, int $id)
    {
        $session = $request->user()->chatSessions()->with('messages')->find($id);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        return response()->json(['session' => $session]);
    }

    /**
     * Send a message within a session and get an agent response.
     */
    public function ask(Request $request, int $id)
    {
        $request->validate([
            'question' => 'required|string',
        ]);

        $session = $request->user()->chatSessions()->with('messages')->find($id);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $history = $session->messages->map(fn($m) => [
            'role'    => $m->role,
            'content' => $m->content,
        ])->toArray();

        $service = new PythonAgentService();
        $answer = $service->ask($request->question, $history);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role'            => 'user',
            'content'         => $request->question,
        ]);

        ChatMessage::create([
            'chat_session_id' => $session->id,
            'role'            => 'assistant',
            'content'         => $answer,
        ]);

        if ($session->messages->isEmpty()) {
            $title = mb_strimwidth($request->question, 0, 60, '...');
            $session->update(['title' => $title]);
        }

        return response()->json([
            'question' => $request->question,
            'answer'   => $answer,
        ]);
    }

    /**
     * Delete a chat session and all its messages.
     */
    public function destroy(Request $request, int $id)
    {
        $session = $request->user()->chatSessions()->find($id);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->delete();

        return response()->json(['message' => 'Chat session deleted']);
    }
}
