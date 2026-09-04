<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseDevelopmentCriterion extends Model
{
    use SoftDeletes;

    protected $table = 'enterprise_development_criteria';

    protected $fillable = [
        'dimension_id',
        'name',
        'code',
        'description',
        'sequence',
        'weighting',
        'required',
        'active',
        'evidence_required',
        'guidance',
        'expires',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'weighting' => 'decimal:2',
        'required' => 'boolean',
        'active' => 'boolean',
        'evidence_required' => 'boolean',
        'expires' => 'boolean',
    ];

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDevelopmentDimension::class, 'dimension_id');
    }
}
