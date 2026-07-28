<?php

namespace App\Domains\Intelligence\DTOs;

class ToolExecutionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly array $output,
        public readonly ?string $message = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'output' => $this->output,
            'message' => $this->message,
        ];
    }
}
