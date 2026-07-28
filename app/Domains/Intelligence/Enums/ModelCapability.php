<?php

namespace App\Domains\Intelligence\Enums;

enum ModelCapability: string
{
    case Chat = 'chat';
    case Reasoning = 'reasoning';
    case Coding = 'coding';
    case Summarization = 'summarization';
    case Embedding = 'embedding';
    case Vision = 'vision';
    case ToolUse = 'tool_use';
}
