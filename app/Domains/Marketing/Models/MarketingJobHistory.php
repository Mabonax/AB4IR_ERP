<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingJobHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_job_id',
        'actor_user_id',
        'action',
        'summary',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketingJob::class, 'marketing_job_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
