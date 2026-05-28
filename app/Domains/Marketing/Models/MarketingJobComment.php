<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingJobComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_job_id',
        'user_id',
        'message',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(MarketingJob::class, 'marketing_job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
