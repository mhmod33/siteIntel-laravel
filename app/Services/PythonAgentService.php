<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

class PythonAgentService
{
    public function ask(string $question, array $history = []): string
    {
        $response = Http::timeout(60)->post('http://127.0.0.1:8000/ask', [
            'question' => $question,
            'history'  => $history,
        ]);

        return $response->json()['answer'];
    }
}