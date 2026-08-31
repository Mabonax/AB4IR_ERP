<?php

namespace App\Domains\Projects\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLearningMapping extends Model
{
    protected $fillable = [
        'project_id',
        'lms_offering_id',
        'status',
        'offering_snapshot',
        'mapped_at',
        'mapped_by_user_id',
    ];

    protected $casts = [
        'offering_snapshot' => 'array',
        'mapped_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function mappedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mapped_by_user_id');
    }
}
