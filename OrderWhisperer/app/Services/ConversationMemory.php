<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ConversationMemory
{
    private string $keyPrefix = 'conversation:';

    /**
     * Retrieve prior messages for a conversation.
     *
     * @return array<int, array{role:string,content:string}>
     */
    public function get(string $conversationId): array
    {
        $key = $this->keyPrefix.$conversationId;
        $store = (string) config('openai.conversation_cache_store', null);
        /** @var array<int, array{role:string,content:string}>|null $messages */
        $messages = Cache::store($store ?: config('cache.default'))->get($key);
        return is_array($messages) ? $messages : [];
    }

    /**
     * Append messages and persist with TTL, trimming to a max length.
     *
     * @param array<int, array{role:string,content:string}> $newMessages
     */
    public function append(string $conversationId, array $newMessages): void
    {
        $key = $this->keyPrefix.$conversationId;
        $ttl = (int) config('openai.conversation_ttl', 7200);
        $max = (int) config('openai.conversation_max_messages', 40);

        $store = (string) config('openai.conversation_cache_store', null);
        $cache = Cache::store($store ?: config('cache.default'));
        $existing = $this->get($conversationId);
        $merged = array_values(array_merge($existing, $newMessages));

        if ($max > 0 && count($merged) > $max) {
            $merged = array_slice($merged, -$max);
        }

        $cache->put($key, $merged, $ttl);
    }
}


