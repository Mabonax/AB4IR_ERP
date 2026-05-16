<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdsPitchSessionProspect extends Model
{
    use HasFactory;

    protected $table = 'bd_pitch_session_prospects';

    protected $fillable = [
        'pitch_session_id',
        'bds_application_id',
        'sequence_number',
        'consolidated_total_score',
        'submitted_assessments_count',
        'consolidated_at',
        'consolidated_by',
        'manager_decision',
        'manager_decided_at',
        'manager_notes',
    ];

    protected $casts = [
        'consolidated_at' => 'datetime',
        'manager_decided_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BdsPitchSession::class, 'pitch_session_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(BdsApplication::class, 'bds_application_id');
    }

    public function consolidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consolidated_by');
    }
}
