<?php

namespace App\Domains\Projects\Models;

use App\Domains\Programs\Models\Program;
use App\Domains\Stakeholders\Models\Stakeholder;
use App\Domains\Staff\Models\StaffMember;
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
        'name',
        'start_date',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function sponsor()
    {
        return $this->belongsTo(Stakeholder::class, 'sponsor_stakeholder_id');
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
}
