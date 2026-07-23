<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'folder_id',
        'user_id',
        'action',
        'entity_context',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class, 'folder_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
