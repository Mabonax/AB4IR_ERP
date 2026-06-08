<?php

namespace App\Domains\Marketing\Enums;

enum MarketingPublicationChannel: string
{
    case Facebook = 'Facebook';
    case Instagram = 'Instagram';
    case LinkedIn = 'LinkedIn';
    case X = 'X';
    case Website = 'Website';
    case EmailCampaign = 'Email Campaign';
    case Print = 'Print';
    case Internal = 'Internal';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
