<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Claim {{ $claim->claim_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 12px; }
        .section { margin-bottom: 12px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        .grid th { background: #f97316; color: white; text-align: left; }
        .meta td.label { width: 18%; font-weight: bold; background: #f9fafb; }
        .summary { margin-top: 12px; width: 40%; margin-left: auto; }
    </style>
</head>
<body>
    @include('pdf.partials.brand-header')

    <div class="title">TRANSPORT CLAIM : PRIVATE VEHICLE</div>

    <table class="grid meta section">
        <tr>
            <td class="label">Claim Number</td>
            <td>{{ $claim->claim_number }}</td>
            <td class="label">Claim Month</td>
            <td>{{ optional($claim->claim_month)->format('Y-m') }}</td>
        </tr>
        <tr>
            <td class="label">Name</td>
            <td>{{ $claim->claimant_name }}</td>
            <td class="label">Make and Model</td>
            <td>{{ $claim->vehicle_make_model }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td>{{ $claim->claimant_address }}</td>
            <td class="label">Type of Vehicle</td>
            <td>{{ $claim->vehicle_type }}</td>
        </tr>
        <tr>
            <td class="label">Year Manufactured</td>
            <td>{{ $claim->vehicle_year }}</td>
            <td class="label">Engine swept volume</td>
            <td>{{ $claim->engine_volume }}</td>
        </tr>
        <tr>
            <td class="label">Tariff Self</td>
            <td>R{{ number_format((float) $claim->tariff_per_km, 2) }}/km</td>
            <td class="label">Home distance travelled daily to work</td>
            <td>{{ number_format((float) $claim->home_distance_km, 2) }} Km</td>
        </tr>
    </table>

    <table class="grid section">
        <thead>
            <tr>
                <th>Date</th>
                <th>From</th>
                <th>To</th>
                <th>Starting</th>
                <th>Ending</th>
                <th>Nature of Duty</th>
                <th>Actual distance</th>
                <th>Claimable distance</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($claim->trips as $trip)
                <tr>
                    <td>{{ optional($trip->travel_date)->format('d/m/Y') }}</td>
                    <td>{{ $trip->route_from }}</td>
                    <td>{{ $trip->route_to }}</td>
                    <td>{{ $trip->start_time }}</td>
                    <td>{{ $trip->end_time }}</td>
                    <td>{{ $trip->nature_of_duty }}</td>
                    <td>{{ number_format((float) $trip->actual_distance_km, 2) }}</td>
                    <td>{{ number_format((float) $trip->claimable_distance_km, 2) }}</td>
                    <td>R{{ number_format((float) $trip->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="grid summary">
        <tr>
            <td class="label">Actual distance</td>
            <td>{{ number_format((float) $claim->total_actual_distance_km, 2) }} Km</td>
        </tr>
        <tr>
            <td class="label">Claimable distance</td>
            <td>{{ number_format((float) $claim->total_claimable_distance_km, 2) }} Km</td>
        </tr>
        <tr>
            <td class="label">Total Amount</td>
            <td>R{{ number_format((float) $claim->total_amount, 2) }}</td>
        </tr>
    </table>

    <table class="grid section" style="margin-top: 24px;">
        <tr>
            <td class="label">Claimant</td>
            <td>{{ $claim->claimant_name }}</td>
            <td class="label">Checked and Approved By</td>
            <td>{{ $claim->checkedBy ? trim($claim->checkedBy->first_name.' '.$claim->checkedBy->last_name) : '' }}</td>
        </tr>
        <tr>
            <td class="label">Submitted</td>
            <td>{{ optional($claim->submitted_at)->format('Y-m-d H:i') }}</td>
            <td class="label">Finance Status</td>
            <td>{{ \Illuminate\Support\Str::of($claim->status)->replace('_', ' ')->title() }}</td>
        </tr>
    </table>

    @include('pdf.partials.brand-footer')
</body>
</html>
