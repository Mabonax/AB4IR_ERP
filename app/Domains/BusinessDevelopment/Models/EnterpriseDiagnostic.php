<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterpriseDiagnostic extends Model
{
    protected $fillable = [
        'bds_incubatee_id',
        'assessment_type',
        'assessment_date',
        'assessor_id',
        'status',
        'overall_score',
        'dimension_scores',
        'outcome_baseline',
        'summary',
        'notes',
        'completed_at',
        'locked_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'overall_score' => 'decimal:2',
        'dimension_scores' => 'array',
        'outcome_baseline' => 'array',
        'completed_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function incubatee(): BelongsTo
    {
        return $this->belongsTo(BdsIncubatee::class, 'bds_incubatee_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(EnterpriseDiagnosticCriterion::class, 'enterprise_diagnostic_id');
    }

    public function gaps(): HasMany
    {
        return $this->hasMany(EnterpriseDevelopmentGap::class, 'enterprise_diagnostic_id');
    }
}
