<?php

namespace App\Domains\Assets\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetDecommissionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'decommissioned_by_user_id',
        'reason',
        'notes',
        'decommissioned_at',
    ];

    protected $casts = [
        'decommissioned_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function decommissionedBy()
    {
        return $this->belongsTo(User::class, 'decommissioned_by_user_id');
    }
}
