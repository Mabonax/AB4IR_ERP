<?php

namespace App\Domains\TaskManagement\Models;

use App\Domains\Assets\Models\Asset;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'requester_user_id',
        'requester_department_id',
        'assigned_to_user_id',
        'assigned_department_id',
        'project_id',
        'program_id',
        'asset_id',
        'resolution_summary',
        'resolved_at',
        'first_responded_at',
        'closed_at',
        'closed_by_user_id',
        'assignment_notified_at',
        'resolved_notified_at',
        'overdue_notified_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'first_responded_at' => 'datetime',
        'closed_at' => 'datetime',
        'assignment_notified_at' => 'datetime',
        'resolved_notified_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function requesterDepartment(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'requester_department_id');
    }

    public function assignedDepartment(): BelongsTo
    {
        return $this->belongsTo(StaffDepartment::class, 'assigned_department_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->latest();
    }
}
