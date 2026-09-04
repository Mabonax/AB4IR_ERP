<?php

namespace App\Domains\Projects\Models;

use App\Domains\Programs\Models\Program;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramMilestoneTemplate extends Model
{
    use HasFactory;

    protected $table = 'program_milestone_templates';

    protected $fillable = [
        'program_id',
        'title',
        'description',
        'sort_order',
        'max_score',
        'is_required',
        'is_active',
        'pass_mark',
        'expected_timing',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function projectMilestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'program_milestone_template_id');
    }
}
