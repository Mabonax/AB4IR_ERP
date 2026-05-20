<?php

namespace App\Domains\Leave\Services;

use App\Domains\Staff\Models\StaffMember;

class LeaveBalanceService
{
    public function __construct(
        protected LeaveManagementService $leaveManagementService
    ) {}

    public function calculate(StaffMember $staff): array
    {
        return $this->leaveManagementService->legacyBalance($staff);
    }
}
