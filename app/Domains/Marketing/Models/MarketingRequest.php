<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Events\Models\Event;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Models\WorkTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'objective',
        'description',
        'target_audience',
        'campaign_goal',
        'requester_user_id',
        'approver_user_id',
        'project_id',
        'program_id',
        'event_id',
        'owner_department_id',
        'priority',
        'due_date',
        'status',
        'source_marketing_job_id',
        'work_task_id',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ownerDepartment(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'owner_department_id');
    }

    public function workPackages(): HasMany
    {
        return $this->hasMany(MarketingWorkPackage::class, 'request_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(MarketingDeliverable::class, 'request_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MarketingActivity::class, 'request_id')->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MarketingRequestComment::class, 'marketing_request_id')->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MarketingRequestDocument::class, 'marketing_request_id')->latest();
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
