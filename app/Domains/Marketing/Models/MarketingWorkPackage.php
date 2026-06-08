<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingWorkPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'assigned_unit',
        'operational_owner_user_id',
        'workload_status',
        'planned_start_date',
        'planned_end_date',
        'actual_end_date',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_end_date' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MarketingRequest::class, 'request_id');
    }

    public function operationalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operational_owner_user_id');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(MarketingDeliverable::class, 'work_package_id');
    }
}
