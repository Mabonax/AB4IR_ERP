<?php

namespace App\Domains\TaskManagement\Repositories;

use App\Domains\TaskManagement\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

interface SupportTicketRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): SupportTicket;

    public function update(SupportTicket $ticket, array $data): SupportTicket;
}
