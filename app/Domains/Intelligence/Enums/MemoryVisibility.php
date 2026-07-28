<?php

namespace App\Domains\Intelligence\Enums;

enum MemoryVisibility: string
{
    case Private = 'private';
    case Team = 'team';
    case Organization = 'organization';
    case Global = 'global';
}
