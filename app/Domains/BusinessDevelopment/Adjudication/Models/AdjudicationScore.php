<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjudicationScore extends Model
{
    use HasFactory;

    protected $table = 'bd_adjudication_scores';

    protected $fillable = [
        'assessment_id',
        'section_id',
        'score',
        'comment',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(AdjudicationAssessment::class, 'assessment_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AdjudicationSection::class, 'section_id');
    }
}
