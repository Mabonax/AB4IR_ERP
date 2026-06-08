<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableVersion extends Model
{
    use HasFactory;

    protected $table = 'marketing_deliverable_versions';

    protected $fillable = [
        'deliverable_id',
        'version_number',
        'uploaded_by_user_id',
        'change_notes',
        'asset_disk',
        'asset_path',
        'asset_file_name',
        'asset_mime_type',
        'asset_file_size',
        'external_reference',
        'approval_status',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'asset_file_size' => 'integer',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(MarketingDeliverable::class, 'deliverable_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
