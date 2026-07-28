<div style="margin-bottom: 14px; border-bottom: 1px solid #e5e7eb; padding-bottom: 8px;">
    <div style="font-size: 15px; font-weight: 700; color: #111827;">{{ $brand['name'] ?? config('app.name') }}</div>
    @if(! empty($brand['tagline']))
        <div style="margin-top: 2px; font-size: 10px; color: #6b7280;">{{ $brand['tagline'] }}</div>
    @endif
</div>
