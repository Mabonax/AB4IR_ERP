<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'title',
        'description',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'version',
        'status',
        'checked_out_by',
        'checked_out_at',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'version' => 'integer',
        'checked_out_at' => 'datetime',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'document_id')->latest();
    }

    public function links(): HasMany
    {
        return $this->hasMany(DocumentLink::class, 'document_id')->latest();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(DocumentActivityLog::class, 'document_id')->latest();
    }

    public function isCheckedOut(): bool
    {
        return $this->checked_out_by !== null;
    }
}
