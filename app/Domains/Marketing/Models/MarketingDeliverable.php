<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use App\Domains\TaskManagement\Models\WorkTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'work_package_id',
        'title',
        'deliverable_type',
        'assigned_to_user_id',
        'assigned_unit',
        'status',
        'due_date',
        'review_notes',
        'approved_at',
        'published_at',
        'current_version_id',
        'source_marketing_job_id',
        'work_task_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MarketingRequest::class, 'request_id');
    }

    public function workPackage(): BelongsTo
    {
        return $this->belongsTo(MarketingWorkPackage::class, 'work_package_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DeliverableVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DeliverableVersion::class, 'deliverable_id')->orderByDesc('version_number');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(MarketingAsset::class, 'deliverable_id');
    }

    public function legacyJob(): BelongsTo
    {
        return $this->belongsTo(MarketingJob::class, 'source_marketing_job_id');
    }

    public function workTask(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }
}
