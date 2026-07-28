<?php

namespace App\Domains\ServiceDelivery\Models;

use App\Domains\Beneficiaries\Models\Beneficiary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryPlacement extends Model
{
    protected $fillable = [
        'beneficiary_id',
        'employer',
        'opportunity_type',
        'placement_date',
        'completion_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'placement_date' => 'date:Y-m-d',
        'completion_date' => 'date:Y-m-d',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
