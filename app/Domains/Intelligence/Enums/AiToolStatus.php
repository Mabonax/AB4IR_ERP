<?php

namespace App\Domains\Intelligence\Enums;

enum AiToolStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Disabled = 'disabled';
    case Archived = 'archived';
}
