<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Register</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1, h2, h3 { margin: 0; }
        .meta { margin-bottom: 16px; }
        .summary { margin: 12px 0 18px; }
        .summary td { padding: 4px 8px; border: 1px solid #d1d5db; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 22px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .muted { color: #6b7280; }
        .register-title { margin-top: 12px; }
    </style>
</head>
<body>
    @include('pdf.partials.brand-header')

    <div class="meta">
        <h1>{{ $event->title }}</h1>
        <div class="muted">
            {{ $event->event_type ?? 'Institutional Event' }}
            @if($event->event_year) | {{ $event->event_year }} @endif
            @if($event->location) | {{ $event->location }} @endif
        </div>
        <div class="muted">
            Venue: {{ $event->venue_name ?? '-' }} | Dates: {{ optional($event->start_date)->format('Y-m-d') ?? '-' }} to {{ optional($event->end_date)->format('Y-m-d') ?? '-' }}
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><strong>Total Register</strong><br>{{ $eventDaySummary['total_register'] ?? 0 }}</td>
            <td><strong>Confirmed</strong><br>{{ $eventDaySummary['confirmed'] ?? 0 }}</td>
            <td><strong>Checked In</strong><br>{{ $eventDaySummary['checked_in'] ?? 0 }}</td>
            <td><strong>Attended</strong><br>{{ $eventDaySummary['attended'] ?? 0 }}</td>
            <td><strong>Outstanding Arrivals</strong><br>{{ $eventDaySummary['outstanding_arrivals'] ?? 0 }}</td>
        </tr>
    </table>

    @foreach($registers as $register)
        <div class="register-title">
            <h2>{{ $register['label'] }}</h2>
            <div class="muted">Participants: {{ $register['count'] }} | Checked In: {{ $register['checked_in'] }} | Attended: {{ $register['attended'] }}</div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Title / Role</th>
                    <th>Organization</th>
                    <th>Attendance Type</th>
                    <th>Topic</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Checked In</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($register['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] ?? '-' }}</td>
                        <td>{{ $item['title'] ?? $item['role'] ?? '-' }}</td>
                        <td>{{ $item['organization_name'] ?? '-' }}</td>
                        <td>{{ $item['attendance_type'] ?? '-' }}</td>
                        <td>{{ $item['topic'] ?? '-' }}</td>
                        <td>{{ $item['email'] ?? '-' }}</td>
                        <td>{{ $item['phone'] ?? '-' }}</td>
                        <td>{{ str_replace('_', ' ', (string) ($item['attendance_status'] ?? '-')) }}</td>
                        <td>{{ $item['checked_in_at'] ?? '-' }}</td>
                        <td>{{ $item['notes'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No participants in this register.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    @include('pdf.partials.brand-footer')
</body>
</html>
