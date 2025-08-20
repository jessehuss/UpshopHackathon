<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OpenAIService;

class OpenAIController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, OpenAIService $openAIService): JsonResponse
    {
        // Basic validation for required OpenAI inputs. Extend as needed.
        $validated = $request->validate([
            'model' => ['required', 'string'],
            'messages' => ['required', 'array'],
            'temperature' => ['nullable', 'numeric'],
            'max_tokens' => ['nullable', 'integer'],
            'top_p' => ['nullable', 'numeric'],
        ]);

        try {
            $result = $openAIService->generateChat($validated);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'openai_request_failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
