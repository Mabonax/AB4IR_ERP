<?php

namespace App\Domains\Facilitators\Services;

use App\Domains\Facilitators\Models\Facilitator;
use App\Domains\Facilitators\Repositories\FacilitatorRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class FacilitatorService
{
    public function __construct(
        protected FacilitatorRepositoryInterface $repository
    ) {}

    public function list(): Collection
    {
        return $this->repository->all();
    }

    public function paginateFacilitators(): LengthAwarePaginator
    {
        return $this->repository->paginate();
    }

    public function getById(int $id): Facilitator
    {
        $facilitator = $this->repository->find($id);

        if (! $facilitator) {
            throw new ModelNotFoundException('Facilitator not found.');
        }

        return $facilitator;
    }

    protected function facilitatorDisplayName(array $data): string
    {
        $first = trim((string) ($data['name'] ?? ''));
        $last = trim((string) ($data['surname'] ?? ''));
        $full = trim($first.' '.$last);

        return $full !== '' ? $full : ($data['email'] ?? 'Facilitator');
    }

    protected function guardUserIsNotLinkedToAnotherFacilitator(int $userId, int $exceptFacilitatorId = 0): void
    {
        $exists = Facilitator::query()
            ->where('user_id', $userId)
            ->when($exceptFacilitatorId > 0, fn ($q) => $q->where('id', '!=', $exceptFacilitatorId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'This user account is already linked to another facilitator profile.',
            ]);
        }
    }

    protected function ensureLinkedUser(array $data, ?Facilitator $facilitator = null): User
    {
        Role::firstOrCreate([
            'name' => 'facilitator',
            'guard_name' => config('access_control.guard', 'web'),
        ]);

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $name = $this->facilitatorDisplayName($data);

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Facilitator email is required.',
            ]);
        }

        $facilitatorId = $facilitator?->id ?? 0;

        if ($facilitator?->user_id) {
            $linkedUser = User::query()->find($facilitator->user_id);
            if ($linkedUser) {
                $emailOwner = User::query()->where('email', $email)->first();
                if ($emailOwner && $emailOwner->id !== $linkedUser->id) {
                    throw ValidationException::withMessages([
                        'email' => 'Another user already exists with this email.',
                    ]);
                }

                $linkedUser->update([
                    'name' => $name,
                    'email' => $email,
                ]);

                if (! $linkedUser->hasRole('facilitator')) {
                    $linkedUser->assignRole('facilitator');
                }

                return $linkedUser;
            }
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $defaultPassword = env('FACILITATOR_USER_DEFAULT_PASSWORD', 'password');

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $defaultPassword,
            ]);
        } else {
            $user->update([
                'name' => $name,
            ]);
        }

        $this->guardUserIsNotLinkedToAnotherFacilitator($user->id, $facilitatorId);

        if (! $user->hasRole('facilitator')) {
            $user->assignRole('facilitator');
        }

        return $user;
    }

    public function create(array $data): Facilitator
    {
        return DB::transaction(function () use ($data) {
            $user = $this->ensureLinkedUser($data);
            $data['user_id'] = $user->id;

            return $this->repository->create($this->normalizePayload($data));
        });
    }

    public function update(int $id, array $data): Facilitator
    {
        return DB::transaction(function () use ($id, $data) {
            $facilitator = $this->getById($id);
            $user = $this->ensureLinkedUser($data, $facilitator);
            $data['user_id'] = $user->id;

            return $this->repository->update($facilitator, $this->normalizePayload($data));
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $facilitator = $this->getById($id);
            $user = $facilitator->user_id
                ? User::query()->find($facilitator->user_id)
                : null;

            if ($user && $user->hasRole('facilitator')) {
                $user->removeRole('facilitator');
            }

            return $this->repository->delete($facilitator);
        });
    }

    protected function normalizePayload(array $data): array
    {
        foreach (['dob', 'id_number', 'address', 'cell', 'specialization', 'province_id'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] !== '') {
                continue;
            }

            $data[$field] = null;
        }

        return $data;
    }
}
