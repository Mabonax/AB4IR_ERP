<?php

namespace App\Domains\Intelligence\Contracts;

use App\Domains\Intelligence\DTOs\StreamChunk;

interface StreamResponseContract
{
    /**
     * @return array<int, StreamChunk>
     */
    public function stream(string $content, array $metadata = []): array;
}
