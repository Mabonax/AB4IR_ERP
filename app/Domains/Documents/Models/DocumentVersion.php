<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'version_number',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'size_bytes' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
