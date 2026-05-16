<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsKpiDefinition extends Model
{
    use HasFactory;

    protected $table = 'bds_kpi_definitions';

    protected $fillable = [
        'name',
        'category',
        'measurement_type',
        'unit',
        'default_target_value',
        'weight',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_target_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function incubateeKpis(): HasMany
    {
        return $this->hasMany(BdsIncubateeKpi::class, 'bds_kpi_definition_id');
    }
}
