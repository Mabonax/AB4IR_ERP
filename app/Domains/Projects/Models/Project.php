<?php

namespace App\Domains\Projects\Models;

use App\Domains\Programs\Models\Program;
use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'program_id',
        'sponsor_stakeholder_id',
        'project_manager_id',
        'contract_reference',
        'funding_amount',
        'reporting_cadence',
        'reporting_obligations',
        'name',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'funding_amount' => 'decimal:2',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(Stakeholder::class, 'sponsor_stakeholder_id');
    }

    public function partners()
    {
        return $this->belongsToMany(
            Stakeholder::class,
            'project_partner_stakeholders',
            'project_id',
            'stakeholder_id'
        )->withTimestamps();
    }

    public function projectManager()
    {
        return $this->belongsTo(StaffMember::class, 'project_manager_id');
    }

    public function locations()
    {
        return $this->hasMany(ProjectLocation::class);
    }

    public function enrollments()
    {
        return $this->hasMany(ProjectEnrollment::class);
    }

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class);
    }

    public function closure()
    {
        return $this->hasOne(ProjectClosure::class);
    }

    public function closureEvidence()
    {
        return $this->hasMany(ProjectClosureEvidence::class)->latest();
    }

    public function reports()
    {
        return $this->hasMany(ProjectReport::class)->latest('report_date')->latest('id');
    }

    public function history()
    {
        return $this->hasMany(ProjectHistory::class)->latest();
    }

    public function attendanceRegisters()
    {
        return $this->hasMany(AttendanceRegister::class);
    }
}
