<?php

namespace App\Domains\Assets\Models;

use App\Domains\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $table = 'assets';

    protected $fillable = [
        'asset_category_id',
        'asset_batch_id',
        'staff_member_id',
        'name',
        'type',
        'model_name',
        'asset_code',
        'serial_state',
        'serial_number',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    public function batch()
    {
        return $this->belongsTo(AssetBatch::class, 'asset_batch_id');
    }

    public function assignments()
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_at')->latestOfMany('assigned_at');
    }
}
