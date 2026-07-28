<?php

namespace App\Domains\ServiceDelivery\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Events\Models\Event;
use App\Domains\Meetings\Models\Meeting;
use App\Domains\Members\Models\Member;
use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceAttendance extends Model
{
    protected $fillable = [
        'member_id',
        'beneficiary_id',
        'program_id',
        'project_id',
        'project_activity_id',
        'event_id',
        'meeting_id',
        'attendance_type',
        'attendance_date',
        'attendance_status',
    ];

    protected $casts = [
        'attendance_date' => 'date:Y-m-d',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(ProjectActivity::class, 'project_activity_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
