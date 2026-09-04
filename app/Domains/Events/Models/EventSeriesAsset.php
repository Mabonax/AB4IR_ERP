<?php

namespace App\Domains\Events\Models;

use App\Domains\Documents\Models\DocumentFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventSeriesAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_series_id',
        'document_file_id',
        'asset_type',
        'label',
        'year',
        'is_featured',
        'display_order',
        'created_by_user_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(EventSeries::class, 'event_series_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_file_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
