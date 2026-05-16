<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdsIncubateeKpiReview extends Model
{
    use HasFactory;

    protected $table = 'bds_incubatee_kpi_reviews';

    protected $fillable = [
        'bds_incubatee_kpi_id',
        'review_date',
        'actual_value',
        'progress_percent',
        'status',
        'evidence_notes',
        'mentor_comments',
        'reviewed_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'actual_value' => 'decimal:2',
    ];

    public function incubateeKpi(): BelongsTo
    {
        return $this->belongsTo(BdsIncubateeKpi::class, 'bds_incubatee_kpi_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
