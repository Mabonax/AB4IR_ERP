<?php

namespace App\Domains\Events\Models;

use App\Domains\Documents\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventSeries extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $table = 'event_series';

    protected $fillable = [
        'name',
        'slug',
        'series_key',
        'description',
        'objectives',
        'default_title_pattern',
        'default_event_type',
        'default_format',
        'default_theme',
        'status',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'event_series_id')->orderByDesc('event_year')->orderByDesc('start_date');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EventSeriesAsset::class, 'event_series_id')
            ->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderByDesc('year');
    }

    public function documentFolders(): HasMany
    {
        return $this->hasMany(DocumentFolder::class, 'owner_id')
            ->where('owner_type', self::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
