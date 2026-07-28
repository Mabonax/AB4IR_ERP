<?php

namespace App\Domains\Geography\Models;

use App\Models\Provinces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'province_id',
        'municipality_id',
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

    public function townships(): HasMany
    {
        return $this->hasMany(Township::class);
    }
}
