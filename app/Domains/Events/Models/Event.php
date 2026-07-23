<?php

namespace App\Domains\Events\Models;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\Stakeholders\Models\Stakeholder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    public const MANAGEABLE_STATUSES = [
        'planned',
        'open_for_registration',
        'registration_closed',
        'active',
        'completed',
        'cancelled',
        'postponed',
        'archived',
    ];

    protected $table = 'events';

    protected $fillable = [
        'title',
        'event_type',
        'event_format',
        'annual_series_key',
        'event_year',
        'is_annual',
        'theme',
        'track_name',
        'location',
        'venue_name',
        'venue_address',
        'venue_contact_person',
        'venue_contact_phone',
        'venue_contact_email',
        'start_date',
        'end_date',
        'status',
        'status_reason',
        'description',
        'objectives',
        'technical_requirements',
        'registration_link',
        'zoom_join_url',
        'zoom_host_url',
        'zoom_meeting_id',
        'zoom_passcode',
        'expected_attendees',
        'owner_staff_member_id',
        'registration_opened_at',
        'registration_closed_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'postponed_at',
        'archived_at',
    ];

    protected $casts = [
        'is_annual' => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'registration_opened_at' => 'datetime',
        'registration_closed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'postponed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function owner()
    {
        return $this->belongsTo(StaffMember::class, 'owner_staff_member_id');
    }

    public function speakers()
    {
        return $this->hasMany(EventParticipant::class)
            ->whereIn('category', ['speaker', 'facilitator'])
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function attendees()
    {
        return $this->hasMany(EventParticipant::class)
            ->whereNotIn('category', ['speaker', 'facilitator'])
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function partners()
    {
        return $this->belongsToMany(Stakeholder::class, 'event_partner_stakeholders')
            ->withTimestamps()
            ->orderBy('organization_name');
    }

    public function workstreams()
    {
        return $this->hasMany(EventWorkstream::class)->orderBy('sort_order')->orderBy('name');
    }

    public function outcomeReport()
    {
        return $this->hasOne(EventOutcomeReport::class);
    }

    public function closureReport()
    {
        return $this->hasOne(EventClosureReport::class);
    }

    public function history()
    {
        return $this->hasMany(EventHistory::class)->latest();
    }
}
