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
        // Guard required fields
        if (!isset($payload['model']) || !isset($payload['messages'])) {
            throw new \InvalidArgumentException('The payload must include model and messages.');
        }

        $response = OpenAI::chat()->create([
            'model' => $payload['model'],
            'messages' => $payload['messages'],
            'temperature' => $payload['temperature'] ?? null,
            'max_tokens' => $payload['max_tokens'] ?? null,
            'top_p' => $payload['top_p'] ?? null,
        ]);

        return $response->toArray();
    }
}


