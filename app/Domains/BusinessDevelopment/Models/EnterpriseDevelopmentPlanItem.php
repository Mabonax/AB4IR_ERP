<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnterpriseDevelopmentPlanItem extends Model
{
    protected $fillable = [
        'development_plan_id',
        'development_need_id',
        'objective',
        'priority',
        'target_date',
        'responsible_user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];

    public function need(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDevelopmentNeed::class, 'development_need_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(EnterpriseDevelopmentPlan::class, 'development_plan_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
