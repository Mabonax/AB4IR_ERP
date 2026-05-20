<?php

namespace App\Domains\TaskManagement\Repositories;

use App\Domains\TaskManagement\Models\SupportTicket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketRepository implements SupportTicketRepositoryInterface
{
    public function paginateVisible(Builder $query, int $perPage = 15): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function create(array $data): SupportTicket
    {
        return SupportTicket::query()->create($data);
    }

    public function update(SupportTicket $ticket, array $data): SupportTicket
    {
        $ticket->update($data);

        return $ticket;
    }
}
