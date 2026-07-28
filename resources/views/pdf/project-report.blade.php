<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1, h2, h3 { margin: 0 0 8px; }
        .muted { color: #6b7280; }
        .section { margin-top: 18px; }
        .card { border: 1px solid #d1d5db; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        .pill { display: inline-block; padding: 4px 8px; border: 1px solid #d1d5db; border-radius: 999px; font-size: 11px; }
        ul { margin: 6px 0 0 18px; padding: 0; }
    </style>
</head>
<body>
    @include('pdf.partials.brand-header')

    <h1>{{ $report->title }}</h1>
    <p class="muted">
        {{ ucfirst($report->report_type) }} report |
        Project: {{ $project->name }} |
        Date: {{ $report->report_date?->format('Y-m-d') }}
    </p>

    <div class="section card">
        <table class="grid">
            <tr>
                <th align="left">Program</th>
                <td>{{ $project->program?->title ?? '-' }}</td>
                <th align="left">Project Manager</th>
                <td>{{ trim(($project->projectManager?->first_name ?? '').' '.($project->projectManager?->last_name ?? '')) ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Sponsor</th>
                <td>{{ $project->sponsor ? trim($project->sponsor->organization_name.' - '.$project->sponsor->name) : '-' }}</td>
                <th align="left">Partners</th>
                <td>{{ $project->partners->map(fn ($partner) => trim($partner->organization_name.' - '.$partner->name))->implode(', ') ?: '-' }}</td>
            </tr>
            <tr>
                <th align="left">Status</th>
                <td>{{ ucfirst((string) $project->status) }}</td>
                <th align="left">Created By</th>
                <td>{{ $report->createdBy?->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    @if($report->executive_summary)
        <div class="section card">
            <h2>Executive Summary</h2>
            <p>{{ $report->executive_summary }}</p>
        </div>
    @endif

    @php($summary = $report->snapshot['summary'] ?? [])
    @php($locations = $report->snapshot['locations'] ?? [])

    <div class="section card">
        <h2>Delivery Snapshot</h2>
        <table class="grid">
            <tr>
                <th align="left">Locations</th>
                <th align="left">Active Beneficiaries</th>
                <th align="left">Completed Beneficiaries</th>
                <th align="left">Milestone Delivery</th>
                <th align="left">Beneficiary Completion</th>
                <th align="left">Attendance Health</th>
                <th align="left">Blocked Sites</th>
            </tr>
            <tr>
                <td>{{ $summary['total_locations'] ?? 0 }}</td>
                <td>{{ $summary['active_beneficiaries'] ?? 0 }}</td>
                <td>{{ $summary['completed_beneficiaries'] ?? 0 }}</td>
                <td>{{ $summary['milestone_completion_rate'] ?? 0 }}%</td>
                <td>{{ $summary['beneficiary_completion_rate'] ?? 0 }}%</td>
                <td>{{ $summary['attendance_rate'] ?? 0 }}%</td>
                <td>{{ $summary['blocked_locations'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    <div class="section card">
        <h2>Location Progress</h2>
        <table class="grid">
            <tr>
                <th align="left">Location</th>
                <th align="left">Facilitator</th>
                <th align="left">Active Beneficiaries</th>
                <th align="left">Milestones</th>
                <th align="left">Completion</th>
                <th align="left">Attendance</th>
                <th align="left">Status</th>
            </tr>
            @foreach($locations as $location)
                <tr>
                    <td>{{ $location['location'] ?? '-' }}</td>
                    <td>{{ $location['facilitator_name'] ?? '-' }}</td>
                    <td>{{ $location['active_beneficiaries'] ?? 0 }}</td>
                    <td>{{ $location['milestone_completion_rate'] ?? 0 }}%</td>
                    <td>{{ $location['beneficiary_completion_rate'] ?? 0 }}%</td>
                    <td>{{ $location['attendance_rate'] ?? 0 }}%</td>
                    <td>{{ !empty($location['is_blocked']) ? 'Needs intervention' : 'On track' }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    @if($report->key_findings)
        <div class="section card">
            <h2>Key Findings</h2>
            <p>{{ $report->key_findings }}</p>
        </div>
    @endif

    @if($report->recommendations)
        <div class="section card">
            <h2>Recommendations</h2>
            <p>{{ $report->recommendations }}</p>
        </div>
    @endif

    @if(!empty($summary['blockers']))
        <div class="section card">
            <h2>Current Project Risks</h2>
            <ul>
                @foreach($summary['blockers'] as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('pdf.partials.brand-footer')
</body>
</html>
