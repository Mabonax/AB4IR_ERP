<?php

namespace App\Domains\Assets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetBatch extends Model
{
    use HasFactory;

    protected $table = 'asset_batches';

    protected $fillable = [
        'name',
        'asset_category_id',
        'type',
        'model_name',
        'quantity',
        'serial_state',
        'notes',
        'created_by',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_batch_id');
    }
}
