<?php

namespace App\Domains\Organisation\Events;

use App\Domains\Organisation\Models\Organisation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganisationRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organisation $organisation
    ) {}
}
