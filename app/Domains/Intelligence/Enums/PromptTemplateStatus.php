<?php

namespace App\Domains\Intelligence\Enums;

enum PromptTemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';
}
