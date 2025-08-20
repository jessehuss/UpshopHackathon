<?php

namespace App\Services;

// use OpenAI; // Facade provided by openai-php/laravel
use OpenAI\Laravel\Facades\OpenAI;


/**
 * OpenAIService provides a thin wrapper around the OpenAI client.
 *
 * This allows swapping prompting/response strategies later without
 * changing controllers or routes. Keep state out; all inputs will
 * be provided per-request as JSON.
 */
class OpenAIService
{
    /**
     * Simple text generation using the Chat Completions API.
     *
     * @param array{model:string,messages:array<int,array<string,mixed>>,temperature?:float,max_tokens?:int,top_p?:float} $payload
     * @return array<string,mixed>
     */
    public function generateChat(array $payload): array
    {
        if (!isset($payload['messages'])) {
            throw new \InvalidArgumentException('The payload must include messages.');
        }

        $configDefaults = [
            'model' => config('openai.default_model'),
            'temperature' => config('openai.default_temperature'),
            'max_tokens' => config('openai.default_max_tokens'),
            'top_p' => config('openai.default_top_p'),
        ];

        // Prepend configurable system messages for consistent context.
        $overrideSystemMessages = $payload['system_messages_override'] ?? null;
        $baseSystemMessages = is_array($overrideSystemMessages)
            ? $overrideSystemMessages
            : (array) config('openai.system_messages', []);

        $systemPrimers = array_map(
            fn (string $text) => ['role' => 'system', 'content' => $text],
            $baseSystemMessages
        );

        $messages = array_values(array_merge($systemPrimers, $payload['messages']));

        $body = [
            'model' => $payload['model'] ?? $configDefaults['model'],
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? $configDefaults['temperature'],
            'max_tokens' => $payload['max_tokens'] ?? $configDefaults['max_tokens'],
            'top_p' => $payload['top_p'] ?? $configDefaults['top_p'],
        ];

        $response = OpenAI::chat()->create($body);

        return $response->toArray();
    }
}


