<?php

namespace App\Domains\BusinessDevelopment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDevelopmentGap extends Model
{
    protected $fillable = [
        'enterprise_diagnostic_id',
        'bds_incubatee_id',
        'criterion_result_id',
        'dimension_code',
        'dimension_name',
        'criterion_code',
        'criterion_name',
        'severity',
        'reason',
        'status',
    ];

    public function incubatee(): BelongsTo
    {
        return $this->belongsTo(BdsIncubatee::class, 'bds_incubatee_id');
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDiagnostic::class, 'enterprise_diagnostic_id');
    }
}
