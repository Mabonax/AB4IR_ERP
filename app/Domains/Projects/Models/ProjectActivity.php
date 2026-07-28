<?php

namespace App\Domains\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectActivity extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'planned_date',
        'actual_date',
        'status',
        'assigned_team',
    ];

    protected $casts = [
        'planned_date' => 'date:Y-m-d',
        'actual_date' => 'date:Y-m-d',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
