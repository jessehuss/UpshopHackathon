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
     * Build the OpenAI Chat request body by applying defaults and system messages.
     *
     * @param array{messages:array<int,array<string,mixed>>,model?:string,temperature?:float,max_tokens?:int,top_p?:float,system_messages_override?:array<int,string>} $payload
     * @return array<string,mixed>
     */
    private function buildRequestBody(array $payload): array
    {
        $configDefaults = [
            'model' => config('openai.default_model'),
            'temperature' => config('openai.default_temperature'),
            'max_tokens' => config('openai.default_max_tokens'),
            'top_p' => config('openai.default_top_p'),
        ];

        $overrideSystemMessages = $payload['system_messages_override'] ?? null;
        $baseSystemMessages = is_array($overrideSystemMessages)
            ? $overrideSystemMessages
            : (array) config('openai.system_messages', []);

        $systemPrimers = array_map(
            fn (string $text) => ['role' => 'system', 'content' => $text],
            $baseSystemMessages
        );

        $messages = array_values(array_merge($systemPrimers, $payload['messages']));

        return [
            'model' => $payload['model'] ?? $configDefaults['model'],
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? $configDefaults['temperature'],
            'max_tokens' => $payload['max_tokens'] ?? $configDefaults['max_tokens'],
            'top_p' => $payload['top_p'] ?? $configDefaults['top_p'],
        ];
    }
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

        $body = $this->buildRequestBody($payload);

        $response = OpenAI::chat()->create($body);

        return $response->toArray();
    }

    /**
     * Stream text generation using the Chat Completions API.
     *
     * @param array{messages:array<int,array<string,mixed>>,model?:string,temperature?:float,max_tokens?:int,top_p?:float,system_messages_override?:array<int,string>} $payload
     * @return \Traversable<int, mixed>
     */
    public function streamChat(array $payload): \Traversable
    {
        if (!isset($payload['messages'])) {
            throw new \InvalidArgumentException('The payload must include messages.');
        }

        $body = $this->buildRequestBody($payload);

        $stream = OpenAI::chat()->createStreamed($body);
        foreach ($stream as $response) {
            yield $response;
        }
    }
}


