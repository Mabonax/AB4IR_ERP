<?php

namespace App\Domains\Marketing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingRequestDocument extends Model
{
    use HasFactory;

    protected $table = 'marketing_request_documents';

    protected $fillable = [
        'marketing_request_id',
        'uploaded_by_user_id',
        'title',
        'document_kind',
        'notes',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MarketingRequest::class, 'marketing_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
