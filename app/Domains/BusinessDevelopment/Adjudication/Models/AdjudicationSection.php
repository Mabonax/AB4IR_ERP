<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjudicationSection extends Model
{
    use HasFactory;

    protected $table = 'bd_adjudication_sections';

    protected $fillable = [
        'key',
        'title',
        'description',
        'max_points',
        'sort_order',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(AdjudicationScore::class, 'section_id');
    }
}
