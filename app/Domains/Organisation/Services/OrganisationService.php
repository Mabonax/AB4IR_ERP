<?php

namespace App\Domains\Organisation\Services;

use App\Domains\Organisation\Events\OrganisationRegistered;
use App\Domains\Organisation\Interfaces\OrganisationRepositoryInterface;
use App\Domains\Organisation\Models\Organisation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class OrganisationService
{
    public function __construct(
        protected OrganisationRepositoryInterface $repository
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function findOrFail(int $id): Organisation
    {
        $organisation = $this->repository->find($id);

        if (! $organisation) {
            throw new ModelNotFoundException('Organisation not found.');
        }

        return $organisation;
    }

    public function register(array $data): Organisation
    {
        $organisation = $this->repository->create($data);

        event(new OrganisationRegistered($organisation));

        return $organisation;
    }

    public function update(int $id, array $data): Organisation
    {
        $organisation = $this->findOrFail($id);

        return $this->repository->update($organisation, $data);
    }

    public function registry(): array
    {
        $organisations = $this->repository->all();

        return [
            'stats' => [
                'total' => $this->repository->countAll(),
                'active' => $this->repository->countActive(),
                'npc' => $this->repository->countByType('NPC'),
                'compliance_ready' => $organisations
                    ->filter(fn (Organisation $organisation) => filled($organisation->npo_number) || filled($organisation->pbo_number))
                    ->count(),
            ],
            'organisations' => $organisations->map(fn (Organisation $organisation) => [
                'id' => $organisation->id,
                'name' => $organisation->name,
                'registration_number' => $organisation->registration_number,
                'organisation_type' => $organisation->organisation_type,
                'npo_number' => $organisation->npo_number,
                'pbo_number' => $organisation->pbo_number,
                'tax_reference_number' => $organisation->tax_reference_number,
                'constitution_version' => $organisation->constitution_version,
                'registered_at' => $organisation->registered_at?->toDateString(),
                'status' => $organisation->status,
                'contact_email' => data_get($organisation->contact_details, 'email'),
                'contact_phone' => data_get($organisation->contact_details, 'phone'),
                'contact_person' => data_get($organisation->contact_details, 'contact_person'),
            ])->values()->all(),
        ];
    }

    public function dashboardWidget(): array
    {
        $total = $this->repository->countAll();
        $active = $this->repository->countActive();
        $npc = $this->repository->countByType('NPC');
        $complianceReady = $this->repository->all()
            ->filter(fn (Organisation $organisation) => filled($organisation->npo_number) || filled($organisation->pbo_number))
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'npc' => $npc,
            'compliance_ready' => $complianceReady,
        ];
    }
}
