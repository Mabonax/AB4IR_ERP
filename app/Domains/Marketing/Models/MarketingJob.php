<?php

namespace App\Domains\Marketing\Models;

use App\Domains\Events\Models\Event;
use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'brief',
        'job_type',
        'status',
        'priority',
        'due_date',
        'event_id',
        'creator_user_id',
        'creator_department_id',
        'assigned_to_user_id',
        'assigned_department_id',
        'delivery_notes',
        'proof_disk',
        'proof_path',
        'proof_file_name',
        'proof_mime_type',
        'proof_file_size',
        'proof_url',
        'submitted_for_approval_at',
        'submitted_by_user_id',
        'approval_notes',
        'reviewed_at',
        'reviewed_by_user_id',
        'returned_for_amendments_at',
        'approved_at',
        'closed_at',
        'closed_by_user_id',
        'assignment_notified_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'proof_file_size' => 'integer',
        'submitted_for_approval_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'returned_for_amendments_at' => 'datetime',
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
        'assignment_notified_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function creatorDepartment(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'creator_department_id');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'assigned_department_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MarketingJobDocument::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MarketingJobComment::class)->latest();
    }

    public function history(): HasMany
    {
        return $this->hasMany(MarketingJobHistory::class)->latest();
    }
}
