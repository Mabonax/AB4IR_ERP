<?php

namespace App\Domains\Intelligence\DTOs;

class AgentExecutionResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly array $usage,
        public readonly array $tools = [],
        public readonly array $memories = [],
        public readonly array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'provider' => $this->provider,
            'model' => $this->model,
            'usage' => $this->usage,
            'tools' => $this->tools,
            'memories' => $this->memories,
            'metadata' => $this->metadata,
        ];
    }
}
