<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Domains\BusinessDevelopment\Adjudication\Models\AdjudicationAssessment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BdsPitchSession extends Model
{
    use HasFactory;

    protected $table = 'bd_pitch_sessions';

    protected $fillable = [
        'title',
        'scheduled_for',
        'venue',
        'expected_prospect_count',
        'notes',
        'status',
        'started_at',
        'consolidated_at',
        'approved_at',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'consolidated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function panelists(): HasMany
    {
        return $this->hasMany(BdsPitchSessionPanelist::class, 'pitch_session_id');
    }

    public function prospects(): HasMany
    {
        return $this->hasMany(BdsPitchSessionProspect::class, 'pitch_session_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(AdjudicationAssessment::class, 'pitch_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
