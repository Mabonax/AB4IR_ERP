<?php

namespace App\Domains\Leave\Services;

use App\Domains\Leave\Models\LeaveRequest;
use App\Domains\Staff\Models\StaffMember;
use Carbon\Carbon;

class LeaveBalanceService
{
    public function calculate(StaffMember $staff): array
    {
        $now = Carbon::now();
        $fyStart = Carbon::create($now->year, 3, 1);
        if ($now->lt($fyStart)) {
            $fyStart = $fyStart->subYear();
        }
        $fyEnd = (clone $fyStart)->addYear()->subDay();

        $startDate = $staff->start_date ? Carbon::parse($staff->start_date) : $fyStart;
        $accrualStart = $startDate->gt($fyStart) ? $startDate : $fyStart;

        if ($accrualStart->gt($now)) {
            $accrued = 0;
        } else {
            $months = $accrualStart->startOfMonth()->diffInMonths($now->startOfMonth());
            $accrued = round($months * 1.25, 2);
        }

        $used = LeaveRequest::where('staff_member_id', $staff->id)
            ->where('status', 'hr_approved')
            ->whereBetween('start_date', [$fyStart->format('Y-m-d'), $fyEnd->format('Y-m-d')])
            ->sum('total_days');

        $available = max($accrued - $used, 0);

        return [
            'accrued' => $accrued,
            'used' => $used,
            'available' => $available,
            'period_start' => $fyStart->format('Y-m-d'),
            'period_end' => $fyEnd->format('Y-m-d'),
        ];
    }
}
