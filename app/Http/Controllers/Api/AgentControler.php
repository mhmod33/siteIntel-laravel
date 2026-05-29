<?php

namespace App\Http\Controllers\Api;

use App\Services\PythonAgentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentControler extends Controller
{
    function ask(Request $request){
        $request->validate([
            'question' => 'required|string',
            'session_id' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
        ]);

        $session = $request->filled('session_id')
            ? $request->user()->chatSessions()->with('messages')->find($request->session_id)
            : $request->user()->chatSessions()->create([
                'title' => $request->input(
                    'title',
                    mb_strimwidth($request->question, 0, 60, '...')
                ),
            ]);

        if (!$session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $session->loadMissing('messages');

        $history = $session->messages->map(fn ($message) => [
            'role' => $message->role,
            'content' => $message->content,
        ])->toArray();

        $service = new PythonAgentService();
        $answer = $service->ask($request->question, $history);

        $session->messages()->create([
            'role' => 'user',
            'content' => $request->question,
        ]);

        $session->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
        ]);

        return response()->json([
            'question' => $request->question,
            'session' => $session->fresh('messages'),
            'answer' => $answer
        ]);

    }
}
