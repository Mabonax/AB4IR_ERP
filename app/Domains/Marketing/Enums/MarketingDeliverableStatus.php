<?php

namespace App\Domains\Marketing\Enums;

enum MarketingDeliverableStatus: string
{
    case Queued = 'queued';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case InternalReview = 'internal_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Published = 'published';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
