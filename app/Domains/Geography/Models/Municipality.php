<?php

namespace App\Domains\Geography\Models;

use App\Models\Provinces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipality extends Model
{
    protected $fillable = [
        'province_id',
        'name',
        'code',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Provinces::class, 'province_id');
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }
}
