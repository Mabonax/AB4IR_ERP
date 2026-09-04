<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnterpriseDevelopmentPlan extends Model
{
    protected $fillable = [
        'bds_incubatee_id',
        'baseline_diagnostic_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function incubatee(): BelongsTo
    {
        return $this->belongsTo(BdsIncubatee::class, 'bds_incubatee_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EnterpriseDevelopmentPlanItem::class, 'development_plan_id');
    }
}
