<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectMilestone extends Model
{
    use HasFactory;

    protected $table = 'project_milestones';

    protected $fillable = [
        'project_id',
        'program_milestone_template_id',
        'title',
        'description',
        'sort_order',
        'max_score',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function template()
    {
        return $this->belongsTo(ProgramMilestoneTemplate::class, 'program_milestone_template_id');
    }

    public function assessments()
    {
        return $this->hasMany(ProjectMilestoneAssessment::class);
    }
}
