<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Contracts\StreamResponseContract;
use App\Domains\Intelligence\DTOs\StreamChunk;

class NullStreamingProvider implements StreamResponseContract
{
    public function stream(string $content, array $metadata = []): array
    {
        return [new StreamChunk('message', $content, $metadata)];
    }
}
