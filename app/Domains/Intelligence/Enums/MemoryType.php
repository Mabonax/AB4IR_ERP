<?php

namespace App\Domains\Intelligence\Enums;

enum MemoryType: string
{
    case Preference = 'preference';
    case Fact = 'fact';
    case Instruction = 'instruction';
    case Relationship = 'relationship';
    case ProjectContext = 'project_context';
    case Risk = 'risk';
    case Decision = 'decision';
    case Note = 'note';
}
