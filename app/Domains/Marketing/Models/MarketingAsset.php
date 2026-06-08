<?php

namespace App\Domains\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverable_id',
        'deliverable_version_id',
        'asset_type',
        'asset_disk',
        'asset_path',
        'asset_file_name',
        'asset_mime_type',
        'asset_file_size',
        'reusable',
        'archived_at',
    ];

    protected $casts = [
        'reusable' => 'boolean',
        'archived_at' => 'datetime',
        'asset_file_size' => 'integer',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(MarketingDeliverable::class, 'deliverable_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DeliverableVersion::class, 'deliverable_version_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(PublicationRecord::class, 'marketing_asset_id')->latest('published_at');
    }
}
