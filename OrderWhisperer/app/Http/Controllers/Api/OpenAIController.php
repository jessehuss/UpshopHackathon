<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\OpenAIService;
use App\Services\ConversationMemory;
use App\Services\DataLayerMock;

class OpenAIController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, OpenAIService $openAIService, ConversationMemory $memory, DataLayerMock $mock): JsonResponse
    {
        // Validate only `content`; first-turn context will use mocked data.
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'conversation_id' => ['nullable', 'string'],
            'unhinged_mode' => ['nullable', 'boolean'],
        ]);

        try {
            $userMessage = ['role' => 'user', 'content' => $validated['content']];

            $conversationId = $validated['conversation_id'] ?? null;
            $history = [];
            if (is_string($conversationId) && $conversationId !== '') {
                $history = $memory->get($conversationId);
            }

            // On first turn, include a mocked data-layer payload as a dedicated user message.
            $firstTurnMessages = [];
            if (empty($history)) {
                $json = $mock->buildSamplePayloadJson();
                $firstTurnMessages[] = [
                    'role' => 'user',
                    'content' => "DATA LAYER PAYLOAD (JSON)\n".$json,
                ];
            }

            $payload = [
                'messages' => array_values(array_merge($history, $firstTurnMessages, [$userMessage])),
            ];

            if (!empty($validated['unhinged_mode'])) {
                $payload['system_messages_override'] = (array) config('openai.system_messages_unhinged', []);
            }

            $result = $openAIService->generateChat($payload);

            $content = data_get($result, 'choices.0.message.content');
            if (!is_string($content)) {
                return response()->json([
                    'error' => 'unexpected_response_shape',
                ], 500);
            }

            // Persist new turn (include the first-turn data message if present)
            if (is_string($conversationId) && $conversationId !== '') {
                $assistantMessage = ['role' => 'assistant', 'content' => $content];
                $turn = !empty($firstTurnMessages)
                    ? array_merge($firstTurnMessages, [$userMessage, $assistantMessage])
                    : [$userMessage, $assistantMessage];
                $memory->append($conversationId, $turn);
            }

            return response()->json(['content' => $content]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'openai_request_failed',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
