<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 16px; }
        h1 { margin: 0 0 8px 0; font-size: 18px; }
        p { margin: 2px 0; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .sub-table { margin-top: 6px; }
        .sub-table th, .sub-table td { font-size: 10px; padding: 4px; }
    </style>
</head>
<body>
    @include('pdf.partials.brand-header')

    <h1>Staff Attendance Report</h1>
    <p><strong>Period:</strong> {{ $period['label'] }} ({{ $period['start'] }} to {{ $period['end'] }})</p>
    <p><strong>Generated:</strong> {{ $generatedAt->format('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Staff Member</th>
                <th>Department</th>
                <th>Manager</th>
                <th>Recorded Days</th>
                <th>Present Days</th>
                <th>Late Days</th>
                <th>Auto Clock-outs</th>
                <th>Total Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['staff_name'] }}</td>
                    <td>{{ $row['department_name'] ?? '-' }}</td>
                    <td>{{ $row['manager_name'] ?? '-' }}</td>
                    <td>{{ $row['recorded_days'] }}</td>
                    <td>{{ $row['present_days'] }}</td>
                    <td>{{ $row['late_days'] }}</td>
                    <td>{{ $row['auto_clock_out_days'] }}</td>
                    <td>{{ number_format((float) $row['total_hours'], 2) }}</td>
                </tr>
                <tr>
                    <td colspan="8">
                        <table class="sub-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Clock In</th>
                                    <th>Clock Out</th>
                                    <th>Status</th>
                                    <th>Out Source</th>
                                    <th>Hours</th>
                                    <th>Override Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($row['records'] as $record)
                                    <tr>
                                        <td>{{ $record['attendance_date'] }}</td>
                                        <td>{{ $record['clock_in_at'] ?? '-' }}</td>
                                        <td>{{ $record['clock_out_at'] ?? '-' }}</td>
                                        <td>{{ $record['clock_in_status_label'] }}</td>
                                        <td>{{ $record['clock_out_source'] ?? '-' }}</td>
                                        <td>{{ $record['hours_worked'] ?? '-' }}</td>
                                        <td>{{ $record['late_override_reason'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">No attendance records in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No attendance records match the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('pdf.partials.brand-footer')
</body>
</html>
