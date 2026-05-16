<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsIncubateeKpi extends Model
{
    use HasFactory;

    protected $table = 'bds_incubatee_kpis';

    protected $fillable = [
        'bds_incubatee_id',
        'bds_kpi_definition_id',
        'target_value',
        'baseline_value',
        'start_date',
        'due_date',
        'status',
        'assigned_by',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'baseline_value' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
    ];

    public function incubatee(): BelongsTo
    {
        return $this->belongsTo(BdsIncubatee::class, 'bds_incubatee_id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(BdsKpiDefinition::class, 'bds_kpi_definition_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(BdsIncubateeKpiReview::class, 'bds_incubatee_kpi_id');
    }
}
