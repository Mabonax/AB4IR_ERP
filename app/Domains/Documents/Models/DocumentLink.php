<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'linkable_type',
        'linkable_id',
        'relationship_type',
        'linked_by',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_by');
    }
}
