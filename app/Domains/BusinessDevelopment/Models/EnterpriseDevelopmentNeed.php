<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDevelopmentNeed extends Model
{
    protected $fillable = [
        'bds_incubatee_id',
        'enterprise_diagnostic_id',
        'development_gap_id',
        'title',
        'dimension_code',
        'dimension_name',
        'priority',
        'reason',
        'source',
        'status',
        'created_by',
        'updated_by',
    ];

    public function incubatee(): BelongsTo
    {
        return $this->belongsTo(BdsIncubatee::class, 'bds_incubatee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
