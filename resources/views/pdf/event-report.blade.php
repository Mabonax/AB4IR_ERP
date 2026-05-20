<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $event->title }} Event Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1, h2 { margin: 0 0 8px; }
        .muted { color: #6b7280; }
        .section { margin-top: 18px; }
        .card { border: 1px solid #d1d5db; border-radius: 6px; padding: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        .grid th { background: #b91c1c; color: white; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ $event->title }}</h1>
    <p class="muted">
        {{ $event->event_type ?: 'Institutional event' }}
        @if($event->event_year) | {{ $event->event_year }} @endif
        | {{ \Illuminate\Support\Str::of($event->status)->replace('_', ' ')->title() }}
    </p>

    <div class="section card">
        <table class="grid">
            <tr>
                <th align="left">Format</th>
                <td>{{ $event->event_format ? \Illuminate\Support\Str::title($event->event_format) : '-' }}</td>
                <th align="left">Theme</th>
                <td>{{ $event->theme ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Track</th>
                <td>{{ $event->track_name ?: '-' }}</td>
                <th align="left">Location</th>
                <td>{{ $event->location ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Start Date</th>
                <td>{{ $event->start_date?->format('Y-m-d') ?: '-' }}</td>
                <th align="left">End Date</th>
                <td>{{ $event->end_date?->format('Y-m-d') ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Owner</th>
                <td>{{ $event->owner ? trim($event->owner->first_name.' '.$event->owner->last_name) : '-' }}</td>
                <th align="left">Annual Series</th>
                <td>{{ $series_summary['series_key'] ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Venue</th>
                <td>{{ $event->venue_name ?: '-' }}</td>
                <th align="left">Expected Attendees</th>
                <td>{{ $event->expected_attendees ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Venue Contact</th>
                <td colspan="3">{{ collect([$event->venue_contact_person, $event->venue_contact_phone, $event->venue_contact_email])->filter()->implode(' | ') ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Venue Address</th>
                <td colspan="3">{{ $event->venue_address ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section card">
        <h2>Operational Links and Technical Requirements</h2>
        <table class="grid">
            <tr>
                <th align="left">Registration Link</th>
                <td>{{ $event->registration_link ?: '-' }}</td>
                <th align="left">Zoom Join URL</th>
                <td>{{ $event->zoom_join_url ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Zoom Host URL</th>
                <td>{{ $event->zoom_host_url ?: '-' }}</td>
                <th align="left">Meeting Credentials</th>
                <td>{{ collect([$event->zoom_meeting_id ? 'ID: '.$event->zoom_meeting_id : null, $event->zoom_passcode ? 'Passcode: '.$event->zoom_passcode : null])->filter()->implode(' | ') ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Technical Requirements</th>
                <td colspan="3">{{ $event->technical_requirements ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section card">
        <h2>Event Partners</h2>
        @if($event->partners->isNotEmpty())
            <ul>
                @foreach($event->partners as $partner)
                    <li>{{ trim(($partner->organization_name ?: 'Stakeholder').' - '.$partner->name) }}</li>
                @endforeach
            </ul>
        @else
            <p>No event partners recorded.</p>
        @endif
    </div>

    <div class="section card">
        <h2>Attendance Summary</h2>
        <table class="grid">
            <tr>
                <th align="left">Registered</th>
                <th align="left">Confirmed</th>
                <th align="left">Checked In</th>
                <th align="left">Attended</th>
                <th align="left">Cancelled</th>
                <th align="left">Attendance Rate</th>
            </tr>
            <tr>
                <td>{{ $summary['registered'] ?? 0 }}</td>
                <td>{{ $summary['confirmed'] ?? 0 }}</td>
                <td>{{ $summary['checked_in'] ?? 0 }}</td>
                <td>{{ $summary['attended'] ?? 0 }}</td>
                <td>{{ $summary['cancelled'] ?? 0 }}</td>
                <td>{{ $summary['attendance_rate'] ?? 0 }}%</td>
            </tr>
        </table>
    </div>

    <div class="section card">
        <h2>Speakers</h2>
        <table class="grid">
            <tr>
                <th align="left">Name</th>
                <th align="left">Title</th>
                <th align="left">Organization</th>
                <th align="left">Topic</th>
            </tr>
            @forelse($event->speakers as $speaker)
                <tr>
                    <td>{{ $speaker->name }}</td>
                    <td>{{ $speaker->title ?: '-' }}</td>
                    <td>{{ $speaker->organization_name ?: '-' }}</td>
                    <td>{{ $speaker->topic ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No speakers recorded.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section card">
        <h2>Attendees</h2>
        <table class="grid">
            <tr>
                <th align="left">Name</th>
                <th align="left">Organization</th>
                <th align="left">Role</th>
                <th align="left">Status</th>
                <th align="left">Checked In At</th>
            </tr>
            @forelse($event->attendees as $attendee)
                <tr>
                    <td>{{ $attendee->name }}</td>
                    <td>{{ $attendee->organization_name ?: '-' }}</td>
                    <td>{{ $attendee->role ?: '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::of($attendee->attendance_status)->replace('_', ' ')->title() }}</td>
                    <td>{{ $attendee->checked_in_at?->format('Y-m-d H:i') ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No attendees recorded.</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="section card">
        <h2>Post-Event Report</h2>
        <table class="grid">
            <tr>
                <th align="left">Report Status</th>
                <td>{{ \Illuminate\Support\Str::of($outcome_report['report_status'] ?? 'draft')->replace('_', ' ')->title() }}</td>
                <th align="left">Reporter</th>
                <td>{{ $outcome_report['reporter_name'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Reported At</th>
                <td colspan="3">{{ $outcome_report['reported_at'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Summary</th>
                <td colspan="3">{{ $outcome_report['summary'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Highlights</th>
                <td colspan="3">{{ $outcome_report['highlights'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Opportunities Created</th>
                <td colspan="3">{{ $outcome_report['opportunities_created'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Partnerships Formed</th>
                <td colspan="3">{{ $outcome_report['partnerships_formed'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Training Opportunities</th>
                <td colspan="3">{{ $outcome_report['training_opportunities'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Media Coverage</th>
                <td colspan="3">{{ $outcome_report['media_coverage'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Statistics Summary</th>
                <td colspan="3">{{ $outcome_report['statistics_summary'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Thank You Status</th>
                <td colspan="3">{{ $outcome_report['thank_you_status'] ?? '-' }}</td>
            </tr>
            <tr>
                <th align="left">Follow-Up Actions</th>
                <td colspan="3">{{ $outcome_report['follow_up_actions'] ?? '-' }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($series_summary['series_key']))
        <div class="section card">
            <h2>Annual Series Overview</h2>
            <table class="grid">
                <tr>
                    <th align="left">Years Run</th>
                    <th align="left">Completed Events</th>
                    <th align="left">Total Attendees</th>
                    <th align="left">Total Speakers</th>
                    <th align="left">Latest Year</th>
                </tr>
                <tr>
                    <td>{{ $series_summary['years_run'] ?? 0 }}</td>
                    <td>{{ $series_summary['completed_events'] ?? 0 }}</td>
                    <td>{{ $series_summary['total_attendees'] ?? 0 }}</td>
                    <td>{{ $series_summary['total_speakers'] ?? 0 }}</td>
                    <td>{{ $series_summary['latest_year'] ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <div class="section card">
            <h2>Annual Series History</h2>
            <table class="grid">
                <tr>
                    <th align="left">Year</th>
                    <th align="left">Title</th>
                    <th align="left">Status</th>
                    <th align="left">Location</th>
                    <th align="left">Speakers</th>
                    <th align="left">Attendees</th>
                </tr>
                @foreach($series_history as $item)
                    <tr>
                        <td>{{ $item['event_year'] ?: '-' }}</td>
                        <td>{{ $item['title'] }}</td>
                        <td>{{ \Illuminate\Support\Str::of($item['status'])->replace('_', ' ')->title() }}</td>
                        <td>{{ $item['location'] ?: '-' }}</td>
                        <td>{{ $item['speaker_count'] ?? 0 }}</td>
                        <td>{{ $item['attendee_count'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</body>
</html>
