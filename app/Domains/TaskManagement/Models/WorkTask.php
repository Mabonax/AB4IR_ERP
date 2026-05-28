<?php

namespace App\Domains\TaskManagement\Models;

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkTask extends Model
{
    use HasFactory;

    protected $table = 'work_tasks';

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'context_type',
        'project_id',
        'program_id',
        'creator_user_id',
        'creator_department_id',
        'assigned_to_user_id',
        'assigned_department_id',
        'completion_notes',
        'proof_disk',
        'proof_path',
        'proof_file_name',
        'proof_mime_type',
        'proof_file_size',
        'proof_url',
        'submitted_for_review_at',
        'submitted_by_user_id',
        'manager_review_notes',
        'reviewed_at',
        'reviewed_by_user_id',
        'returned_for_amendments_at',
        'completed_at',
        'closed_at',
        'closed_by_user_id',
        'assignment_notified_at',
        'overdue_notified_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'proof_file_size' => 'integer',
        'submitted_for_review_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'returned_for_amendments_at' => 'datetime',
        'completed_at' => 'datetime',
        'closed_at' => 'datetime',
        'assignment_notified_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
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

    public function comments(): HasMany
    {
        return $this->hasMany(WorkTaskComment::class)->latest();
    }

    public function history(): HasMany
    {
        return $this->hasMany(WorkTaskHistory::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WorkTaskDocument::class)->latest();
    }
}
