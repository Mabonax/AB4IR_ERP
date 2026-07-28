<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\MemoryRecord;
use App\Models\User;
use Illuminate\Support\Carbon;

class MemoryReviewService
{
    public function approve(MemoryRecord $memory, User $user): MemoryRecord
    {
        $memory->forceFill([
            'reviewed_at' => Carbon::now(),
            'approved_by' => $user->id,
        ])->save();

        return $memory->refresh();
    }
}
