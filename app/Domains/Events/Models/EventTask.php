<?php

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTask extends Model
{
    use HasFactory;

    protected $table = 'event_tasks';

    protected $fillable = [
        'event_workstream_id',
        'phase',
        'task_group',
        'is_custom',
        'duty',
        'due_date',
        'responsible_person',
        'outcome',
        'status',
        'comment',
        'evidence_disk',
        'evidence_path',
        'evidence_file_name',
        'evidence_mime_type',
        'evidence_file_size',
        'evidence_url',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'is_custom' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function workstream()
    {
        return $this->belongsTo(EventWorkstream::class, 'event_workstream_id');
    }
}
