<?php

namespace App\Domains\Organisation\Actions;

use App\Domains\Organisation\Models\Organisation;
use App\Domains\Organisation\Services\OrganisationService;

class RegisterOrganisationAction
{
    public function __construct(
        protected OrganisationService $service
    ) {}

    public function execute(array $data): Organisation
    {
        return $this->service->register($data);
    }
}
