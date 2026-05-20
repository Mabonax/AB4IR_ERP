<?php

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\StaffMember;
use App\Domains\Staff\Repositories\StaffRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffService
{
    public function __construct(
        protected StaffRepositoryInterface $repository
    ) {}

    public function paginateStaffMembers(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getStaffById(int $id): StaffMember
    {
        $staff = $this->repository->find($id);

        if (! $staff) {
            throw new ModelNotFoundException('Staff member not found.');
        }

        return $staff;
    }

    public function createStaffWithNextOfKin(array $data): StaffMember
    {
        return DB::transaction(function () use ($data) {
            $staffData = $this->normalizeStaffData($data['staff']);
            $user = $this->ensureLinkedUser($staffData);
            $staffData['user_id'] = $user->id;

            $staff = $this->repository->create($staffData);
            $this->repository->createNextOfKin($staff, $data['next_of_kin']);

            $user->forceFill(['staff_id' => $staff->id])->save();

            return $this->repository->find($staff->id) ?? $staff;
        });
    }

    public function updateStaffWithNextOfKin(int $id, array $data): StaffMember
    {
        return DB::transaction(function () use ($id, $data) {
            $staff = $this->getStaffById($id);
            $staffData = $this->normalizeStaffData($data['staff']);
            $previousUserId = $staff->user_id;

            $user = $this->ensureLinkedUser($staffData, $staff);
            $staffData['user_id'] = $user->id;

            $this->repository->update($staff, $staffData);
            $this->repository->updateNextOfKin($staff, $data['next_of_kin']);

            $user->forceFill(['staff_id' => $staff->id])->save();

            if ($previousUserId && (int) $previousUserId !== (int) $user->id) {
                User::query()
                    ->where('id', $previousUserId)
                    ->where('staff_id', $staff->id)
                    ->update(['staff_id' => null]);
            }

            return $this->repository->find($staff->id) ?? $staff;
        });
    }

    public function deleteStaff(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $staff = $this->getStaffById($id);
            if ($staff->user_id) {
                User::query()
                    ->where('id', $staff->user_id)
                    ->where('staff_id', $staff->id)
                    ->update(['staff_id' => null]);
            }

            return $this->repository->delete($staff);
        });
    }

    public function promoteToManager(int $id): StaffMember
    {
        return DB::transaction(function () use ($id) {
            $staff = $this->getStaffById($id);

            if ((bool) $staff->is_manager) {
                throw ValidationException::withMessages([
                    'staff' => 'This staff member is already a manager.',
                ]);
            }

            if ((bool) $staff->is_ceo) {
                throw ValidationException::withMessages([
                    'staff' => 'The CEO does not need a separate manager promotion.',
                ]);
            }

            if ($staff->status !== 'active') {
                throw ValidationException::withMessages([
                    'staff' => 'Only active staff members can be promoted to manager.',
                ]);
            }

            $this->repository->update($staff, [
                'is_manager' => true,
            ]);

            return $this->repository->find($staff->id) ?? $staff->refresh();
        });
    }

    protected function staffDisplayName(array $staffData): string
    {
        $first = trim((string) ($staffData['first_name'] ?? ''));
        $last = trim((string) ($staffData['last_name'] ?? ''));
        $full = trim($first.' '.$last);

        return $full !== '' ? $full : ((string) ($staffData['email'] ?? 'Staff'));
    }

    protected function guardUserIsNotLinkedToAnotherStaff(int $userId, int $exceptStaffId = 0): void
    {
        $exists = StaffMember::query()
            ->where('user_id', $userId)
            ->when($exceptStaffId > 0, fn ($q) => $q->where('id', '!=', $exceptStaffId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'staff.email' => 'This user account is already linked to another staff member.',
            ]);
        }
    }

    protected function ensureLinkedUser(array $staffData, ?StaffMember $staff = null): User
    {
        $email = strtolower(trim((string) ($staffData['email'] ?? '')));
        $name = $this->staffDisplayName($staffData);

        if ($email === '') {
            throw ValidationException::withMessages([
                'staff.email' => 'Staff email is required.',
            ]);
        }

        $staffId = $staff?->id ?? 0;

        if ($staff?->user_id) {
            $linkedUser = User::query()->find($staff->user_id);
            if ($linkedUser) {
                $emailOwner = User::query()->where('email', $email)->first();
                if ($emailOwner && (int) $emailOwner->id !== (int) $linkedUser->id) {
                    throw ValidationException::withMessages([
                        'staff.email' => 'Another user already exists with this email.',
                    ]);
                }

                $linkedUser->update([
                    'name' => $name,
                    'email' => $email,
                ]);

                return $linkedUser;
            }
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => env('STAFF_USER_DEFAULT_PASSWORD', 'password'),
            ]);
        } else {
            $user->update([
                'name' => $name,
            ]);
        }

        $this->guardUserIsNotLinkedToAnotherStaff($user->id, $staffId);

        return $user;
    }

    protected function normalizeStaffData(array $staffData): array
    {
        $isIntern = filter_var($staffData['is_intern'] ?? false, FILTER_VALIDATE_BOOL);

        if (! $isIntern) {
            $staffData['intern_sponsor_name'] = null;
            $staffData['internship_start_date'] = null;
            $staffData['internship_end_date'] = null;
        }

        return $staffData;
    }
}
