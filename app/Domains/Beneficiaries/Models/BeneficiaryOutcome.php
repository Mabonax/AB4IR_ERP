<?php

namespace App\Domains\Beneficiaries\Models;

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeneficiaryOutcome extends Model
{
    use HasFactory;

    public const TYPES = [
        'employment',
        'self_employment',
        'internship',
        'learnership',
        'further_education',
        'volunteer_placement',
        'entrepreneurship',
        'unknown_outcome',
    ];

    protected $fillable = [
        'beneficiary_id',
        'program_id',
        'project_id',
        'outcome_type',
        'notes',
        'recorded_at',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
