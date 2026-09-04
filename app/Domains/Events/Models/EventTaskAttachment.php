<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_task_id',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
        'attachment_type',
        'sort_order',
    ];

    public function task()
    {
        return $this->belongsTo(EventTask::class, 'event_task_id');
    }
}
