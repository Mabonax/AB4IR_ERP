<?php

namespace App\Domains\Intelligence\DTOs;

class StreamChunk
{
    public function __construct(
        public readonly string $type,
        public readonly string $content,
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->content,
            'metadata' => $this->metadata,
        ];
    }
}
