<?php

namespace App\Domains\Leave\Models;

use App\Domains\Documents\Models\DocumentFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestDocument extends Model
{
    protected $fillable = [
        'leave_request_id',
        'document_file_id',
        'document_kind',
        'uploaded_by',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    public function documentFile(): BelongsTo
    {
        return $this->belongsTo(DocumentFile::class, 'document_file_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
