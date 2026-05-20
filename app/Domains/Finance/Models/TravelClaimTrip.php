<?php

namespace App\Domains\Finance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelClaimTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'travel_claim_id',
        'travel_date',
        'route_from',
        'route_to',
        'start_time',
        'end_time',
        'nature_of_duty',
        'actual_distance_km',
        'claimable_distance_km',
        'line_total',
    ];

    protected $casts = [
        'travel_date' => 'date',
        'actual_distance_km' => 'decimal:2',
        'claimable_distance_km' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function claim()
    {
        return $this->belongsTo(TravelClaim::class, 'travel_claim_id');
    }
}
