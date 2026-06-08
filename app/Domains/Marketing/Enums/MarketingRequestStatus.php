<?php

namespace App\Domains\Marketing\Enums;

enum MarketingRequestStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Planned = 'planned';
    case InProduction = 'in_production';
    case InReview = 'in_review';
    case PartiallyApproved = 'partially_approved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
