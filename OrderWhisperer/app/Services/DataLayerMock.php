<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * DataLayerMock provides a mocked response payload matching the expected
 * data layer shape for the hackathon. It can return either an array or
 * a JSON string.
 */
class DataLayerMock
{
    /**
     * Fetch remote mock payload JSON and cache it briefly.
     * Controlled via env:
     *  - MOCK_DATA_URL (string, e.g. https://andymoir.com/Hackathon/public/mock-data.json)
     *  - MOCK_DATA_TTL (int seconds, default 300)
     *
     * @return array<string,mixed>|null
     */
    private function getRemotePayload(): ?array
    {
        $url = 'https://andymoir.com/Hackathon/public/mock-data.json';
        if ($url === '') {
            return null;
        }

        $ttl = (int) env('MOCK_DATA_TTL', 300);
        $cacheKey = 'mock_data_payload:'.md5($url);

        return Cache::remember($cacheKey, $ttl, function () use ($url) {
            try {
                $resp = Http::timeout(10)->acceptJson()->get($url);
                if (!$resp->successful()) {
                    return null;
                }
                $data = $resp->json();
                return is_array($data) ? $data : null;
            } catch (\Throwable $e) {
                return null;
            }
        });
    }

    /**
     * Build the sample payload as an associative array.
     *
     * @param array<string,mixed> $overrides Optional recursive overrides for any keys
     * @return array<string,mixed>
     */
    public function buildSamplePayloadArray(array $overrides = []): array
    {
        return $this->getRemotePayload();
    }

    /**
     * Build the sample payload as a JSON string.
     *
     * @param array<string,mixed> $overrides Optional recursive overrides
     */
    public function buildSamplePayloadJson(array $overrides = []): string
    {
        $array = $this->buildSamplePayloadArray($overrides);
        return (string) json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively merge arrays, replacing scalar values from overrides.
     *
     * @param array<mixed> $base
     * @param array<mixed> $overrides
     * @return array<mixed>
     */
    private function mergeRecursiveDistinct(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeRecursiveDistinct($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}


