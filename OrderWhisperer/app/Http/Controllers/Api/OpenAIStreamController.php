<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\OpenAIService;
use App\Services\ConversationMemory;
use App\Services\DataLayerMock;

class OpenAIStreamController extends Controller
{
    public function __invoke(Request $request, OpenAIService $openAIService, ConversationMemory $memory, DataLayerMock $mock): StreamedResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
            'conversation_id' => ['nullable', 'string'],
            'unhinged_mode' => ['nullable', 'boolean'],
        ]);

        $userMessage = ['role' => 'user', 'content' => $validated['content']];

        $conversationId = $validated['conversation_id'] ?? null;
        $history = [];
        if (is_string($conversationId) && $conversationId !== '') {
            $history = $memory->get($conversationId);
        }

        // First turn mocked data injection
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

        $response = new StreamedResponse(function () use ($openAIService, $payload, $memory, $conversationId, $userMessage, $firstTurnMessages) {
            $buffer = '';
            echo "event: start\n";
            echo "data: {\"status\":\"started\"}\n\n";
            @ob_flush(); flush();

            try {
                foreach ($openAIService->streamChat($payload) as $event) {
                    $delta = data_get($event, 'choices.0.delta.content');
                    if (is_string($delta) && $delta !== '') {
                        $buffer .= $delta;
                        echo "event: token\n";
                        echo 'data: '.json_encode(['content' => $delta], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
                        @ob_flush(); flush();
                    }
                }

                // Persist the full assistant message at end of stream
                if (is_string($conversationId) && $conversationId !== '' && $buffer !== '') {
                    $assistantMessage = ['role' => 'assistant', 'content' => $buffer];
                    $turn = !empty($firstTurnMessages)
                        ? array_merge($firstTurnMessages, [$userMessage, $assistantMessage])
                        : [$userMessage, $assistantMessage];
                    $memory->append($conversationId, $turn);
                }

                echo "event: end\n";
                echo 'data: '.json_encode(['status' => 'completed'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
                @ob_flush(); flush();
            } catch (\Throwable $e) {
                echo "event: error\n";
                echo 'data: '.json_encode(['error' => 'openai_request_failed', 'message' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n\n";
                @ob_flush(); flush();
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}


