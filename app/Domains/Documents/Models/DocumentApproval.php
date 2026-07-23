<?php

namespace App\Domains\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'approver_id',
        'status',
        'comments',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
