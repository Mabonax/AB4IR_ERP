<?php

namespace App\Domains\BusinessDevelopment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BdsPitchSessionPanelist extends Model
{
    use HasFactory;

    protected $table = 'bd_pitch_session_panelists';

    protected $fillable = [
        'pitch_session_id',
        'user_id',
        'panel_role',
        'is_chair',
    ];

    protected $casts = [
        'is_chair' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BdsPitchSession::class, 'pitch_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
