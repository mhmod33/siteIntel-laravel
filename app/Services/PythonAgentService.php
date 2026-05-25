<?php

namespace app\Services;

use Illuminate\Support\Facades\Http;

class PythonAgentService
{
    public function ask(string $question, array $history = []): string
    {
        $response = Http::timeout(60)->post('https://mhmodtda13-siteintel.hf.space/ask', [
            'question' => $question,
            'history'  => $history,
        ]);

        return $response->json()['answer'];
    }
}