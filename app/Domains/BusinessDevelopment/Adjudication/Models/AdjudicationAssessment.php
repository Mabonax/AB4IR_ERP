<?php

namespace App\Domains\BusinessDevelopment\Adjudication\Models;

use App\Domains\BusinessDevelopment\Models\BdsApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjudicationAssessment extends Model
{
    use HasFactory;

    protected $table = 'bd_adjudication_assessments';

    protected $fillable = [
        'smme_id',
        'judge_id',
        'platform_name',
        'adjudication_date',
        'development_stage',
        'status',
        'total_score',
        'additional_notes',
        'submitted_at',
    ];

    protected $casts = [
        'adjudication_date' => 'date',
        'submitted_at' => 'datetime',
    ];

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function smme(): BelongsTo
    {
        return $this->belongsTo(BdsApplication::class, 'smme_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AdjudicationScore::class, 'assessment_id');
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(
            AdjudicationSection::class,
            'bd_adjudication_scores',
            'assessment_id',
            'section_id'
        )->withPivot(['score', 'comment'])->withTimestamps();
    }
}
