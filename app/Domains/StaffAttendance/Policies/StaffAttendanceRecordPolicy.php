<?php

namespace App\Domains\StaffAttendance\Policies;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\StaffAttendance\Models\StaffAttendanceRecord;
use App\Models\User;

class StaffAttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('domain.human-resources.view')
            || $user->can('domain.human-resources.manage')
            || $user->can('domain.staff.view')
            || $user->can('domain.staff.manage')
            || $user->staffMember !== null;
    }

    public function view(User $user, StaffAttendanceRecord $record): bool
    {
        $staff = $user->staffMember;

        if ($staff && (int) $record->staff_member_id === (int) $staff->id) {
            return true;
        }

        return $this->canManageStaffAttendance($user, $record->staffMember);
    }

    public function clock(User $user): bool
    {
        return $user->staffMember !== null;
    }

    public function openLateOverride(User $user, StaffMember $staff): bool
    {
        if ($user->can('domain.human-resources.manage') || $user->can('domain.staff.manage')) {
            return true;
        }

        $actor = $user->staffMember;
        if (! $actor) {
            return false;
        }

        if ((bool) $actor->is_ceo) {
            return true;
        }

        return (int) $staff->manager_id === (int) $actor->id;
    }

    public function viewReports(User $user, ?StaffMember $staff = null): bool
    {
        if ($user->can('domain.human-resources.view') || $user->can('domain.human-resources.manage') || $user->can('domain.staff.manage')) {
            return true;
        }

        $actor = $user->staffMember;
        if (! $actor || ! $staff) {
            return false;
        }

        return (int) $staff->manager_id === (int) $actor->id;
    }

    protected function canManageStaffAttendance(User $user, ?StaffMember $staff): bool
    {
        if (! $staff) {
            return false;
        }

        if ($user->can('domain.human-resources.view') || $user->can('domain.human-resources.manage') || $user->can('domain.staff.manage')) {
            return true;
        }

        $actor = $user->staffMember;
        if (! $actor) {
            return false;
        }

        if ((bool) $actor->is_ceo) {
            return true;
        }

        return (int) $staff->manager_id === (int) $actor->id;
    }
}
