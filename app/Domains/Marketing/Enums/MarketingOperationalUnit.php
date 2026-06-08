<?php

namespace App\Domains\Marketing\Enums;

enum MarketingOperationalUnit: string
{
    case Graphics = 'graphics';
    case Communications = 'communications';
    case Digital = 'digital';
    case EventsSupport = 'events_support';
    case Content = 'content';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
