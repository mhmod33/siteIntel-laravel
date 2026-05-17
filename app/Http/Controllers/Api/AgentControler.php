<?php

namespace App\Http\Controllers\Api;
use App\Services\PythonAgentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentControler extends Controller
{
    function ask(Request $request){
        $request->validate([
            'question' => 'required|string'
        ]);
        $service = new PythonAgentService();
        $answer = $service->ask($request->question);

        return response()->json([
            'answer' => $answer
        ]);

    }
}
