<?php

namespace App\Domains\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetricSnapshot extends Model
{
    use HasFactory;

    protected $table = 'marketing_metric_snapshots';

    protected $fillable = [
        'publication_record_id',
        'metric_date',
        'impressions',
        'reach',
        'engagements',
        'clicks',
        'sessions',
        'conversions',
        'followers',
    ];

    protected $casts = [
        'metric_date' => 'date',
    ];

    public function publicationRecord(): BelongsTo
    {
        return $this->belongsTo(PublicationRecord::class, 'publication_record_id');
    }
}
