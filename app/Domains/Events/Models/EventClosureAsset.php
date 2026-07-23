<?php

namespace App\Domains\Events\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventClosureAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_closure_report_id',
        'category',
        'uploaded_by_user_id',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
        'description',
    ];

    public function closureReport()
    {
        return $this->belongsTo(EventClosureReport::class, 'event_closure_report_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
