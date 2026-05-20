<?php

namespace App\Domains\Events\Models;

use App\Domains\Staff\Models\StaffMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventOutcomeReport extends Model
{
    use HasFactory;

    protected $table = 'event_outcome_reports';

    protected $fillable = [
        'event_id',
        'summary',
        'highlights',
        'opportunities_created',
        'partnerships_formed',
        'training_opportunities',
        'media_coverage',
        'statistics_summary',
        'thank_you_status',
        'follow_up_actions',
        'report_status',
        'reported_by_staff_member_id',
        'reported_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function reporter()
    {
        return $this->belongsTo(StaffMember::class, 'reported_by_staff_member_id');
    }
}
