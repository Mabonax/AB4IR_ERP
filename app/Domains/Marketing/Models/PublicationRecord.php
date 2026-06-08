<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicationRecord extends Model
{
    use HasFactory;

    protected $table = 'marketing_publication_records';

    protected $fillable = [
        'marketing_asset_id',
        'publication_channel',
        'published_by_user_id',
        'published_at',
        'external_reference',
        'publication_notes',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MarketingAsset::class, 'marketing_asset_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function metricSnapshots(): HasMany
    {
        return $this->hasMany(MetricSnapshot::class, 'publication_record_id')->latest('metric_date');
    }
}
