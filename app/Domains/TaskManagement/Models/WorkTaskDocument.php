<?php

namespace App\Domains\TaskManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskDocument extends Model
{
    use HasFactory;

    protected $table = 'work_task_documents';

    protected $fillable = [
        'work_task_id',
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

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
