<?php

namespace App\Domains\Geography\Models;

use App\Models\Provinces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    protected $fillable = [
        'province_id',
        'municipality_id',
        'region_id',
        'township_id',
        'ward_id',
        'name',
        'code',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function township(): BelongsTo
    {
        return $this->belongsTo(Township::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
