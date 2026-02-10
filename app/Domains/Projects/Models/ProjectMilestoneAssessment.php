<?php

namespace App\Domains\Projects\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Facilitators\Models\Facilitator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestoneAssessment extends Model
{
    use HasFactory;

    protected $table = 'project_milestone_assessments';

    protected $fillable = [
        'project_milestone_id',
        'beneficiary_id',
        'project_location_id',
        'facilitator_id',
        'status',
        'score',
        'comments',
        'assessed_at',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function milestone()
    {
        return $this->belongsTo(ProjectMilestone::class, 'project_milestone_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function location()
    {
        return $this->belongsTo(ProjectLocation::class, 'project_location_id');
    }

    public function facilitator()
    {
        return $this->belongsTo(Facilitator::class);
    }
}
