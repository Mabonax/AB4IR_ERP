<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterpriseDevelopmentDimension extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sequence',
        'weighting',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'weighting' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function criteria(): HasMany
    {
        return $this->hasMany(EnterpriseDevelopmentCriterion::class, 'dimension_id')->orderBy('sequence');
    }
}
