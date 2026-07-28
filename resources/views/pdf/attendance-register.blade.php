<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Register</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 20px;
        }
        .header {
            border: 1px solid #e5e7eb;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }
        .subtle {
            color: #6b7280;
            margin: 2px 0;
        }
        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .grid td {
            width: 50%;
            vertical-align: top;
            padding: 4px 0;
        }
        .stats {
            margin-top: 12px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
        }
        .stats table {
            width: 100%;
            border-collapse: collapse;
        }
        .stats td {
            width: 25%;
            text-align: center;
            padding: 4px 2px;
        }
        .stats .label {
            font-size: 10px;
            color: #6b7280;
        }
        .stats .value {
            font-size: 16px;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge.holiday {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge.register {
            background: #e5e7eb;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
        }
        .table td {
            border: 1px solid #e5e7eb;
            padding: 8px 6px;
        }
        .footer {
            margin-top: 10px;
            font-size: 10px;
            color: #6b7280;
            text-align: right;
        }
    </style>
</head>
<body>
    @include('pdf.partials.brand-header')

    <div class="header">
        <h1 class="title">Attendance Register Details</h1>
        <p class="subtle"><strong>Register ID:</strong> {{ $register_reference }}</p>
        <p class="subtle"><strong>Project Name:</strong> {{ $location->project?->name ?? '-' }}</p>
        <p class="subtle"><strong>Location:</strong> {{ $location->province?->name ?? '-' }}</p>
        <p class="subtle"><strong>Facilitator:</strong> {{ $location->facilitator ? trim($location->facilitator->name . ' ' . $location->facilitator->surname) : '-' }}</p>
        <p class="subtle"><strong>Venue:</strong> {{ $location->training_venue_address ?? '-' }}</p>
        <p class="subtle"><strong>Total Number of Students:</strong> {{ $total_students }}</p>
        <p class="subtle"><strong>Project Start and End Date:</strong> {{ $location->project?->start_date?->format('Y-m-d') ?? '-' }} to {{ $location->project?->end_date?->format('Y-m-d') ?? '-' }}</p>
    </div>

    <table class="grid">
        <tr>
            <td><strong>Date:</strong> {{ $register->attendance_date?->format('Y-m-d') ?? '-' }}</td>
            <td><strong>Day:</strong> {{ $register->attendance_date?->format('l') ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Type:</strong>
                <span class="badge {{ $register->is_holiday ? 'holiday' : 'register' }}">
                    {{ $register->is_holiday ? 'Holiday' : 'Register' }}
                </span>
            </td>
            <td><strong>Holiday Reason:</strong> {{ $register->holiday_reason ?? '-' }}</td>
        </tr>
    </table>

    <div class="stats">
        <table>
            <tr>
                <td>
                    <div class="label">Attendance</div>
                    <div class="value">{{ $stats['attendance_rate'] }}%</div>
                </td>
                <td>
                    <div class="label">Absent</div>
                    <div class="value">{{ $stats['absent'] }}</div>
                </td>
                <td>
                    <div class="label">Present</div>
                    <div class="value">{{ $stats['present'] }}</div>
                </td>
                <td>
                    <div class="label">Excused</div>
                    <div class="value">{{ $stats['excused'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 6%;">#</th>
                <th style="width: 44%;">Beneficiary</th>
                <th style="width: 18%;">Status</th>
                <th style="width: 32%;">Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $index => $entry)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $entry['beneficiary_name'] }}</td>
                    <td>{{ ucfirst($entry['status']) }}</td>
                    <td>{{ $entry['excused_reason'] ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No attendance entries captured for this date.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        @include('pdf.partials.brand-footer')
    </div>
</body>
</html>
