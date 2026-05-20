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
        'completed_at',
        'assignment_notified_at',
        'overdue_notified_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
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
}
