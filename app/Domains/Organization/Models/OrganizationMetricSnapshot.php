<?php

namespace App\Domains\Organization\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMetricSnapshot extends Model
{
    use HasFactory;

    protected $table = 'organization_metric_snapshots';

    protected $fillable = [
        'organization_profile_id',
        'captured_at',
        'impact_total',
        'impact_digital',
        'impact_physical',
        'trainings_conducted',
        'impact_website',
        'impact_walkins',
        'impact_facebook',
        'impact_x',
        'impact_linkedin',
        'impact_livestreaming',
        'impact_instagram',
        'impact_youtube',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(OrganizationProfile::class, 'organization_profile_id');
    }
}
