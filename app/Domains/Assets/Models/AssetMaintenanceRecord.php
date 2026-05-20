<?php

namespace App\Domains\Assets\Models;

use App\Domains\TaskManagement\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'support_ticket_id',
        'started_by_user_id',
        'completed_by_user_id',
        'issue_summary',
        'maintenance_notes',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function supportTicket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }
}
