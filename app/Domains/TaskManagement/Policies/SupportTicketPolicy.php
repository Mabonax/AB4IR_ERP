<?php

namespace App\Domains\TaskManagement\Policies;

use App\Domains\TaskManagement\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    protected function canRespond(User $user): bool
    {
        return $user->can('technical-tickets.respond')
            || $user->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $this->canRespond($user)
            || (int) $ticket->requester_user_id === (int) $user->id
            || (int) ($ticket->assigned_to_user_id ?? 0) === (int) $user->id;
    }

    public function assign(User $user, SupportTicket $ticket): bool
    {
        return $this->canRespond($user) && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function resolve(User $user, SupportTicket $ticket): bool
    {
        return $this->canRespond($user) && in_array($ticket->status, ['assigned', 'in_progress'], true);
    }

    public function close(User $user, SupportTicket $ticket): bool
    {
        return in_array($ticket->status, ['resolved'], true)
            && (
                $this->canRespond($user)
                || (int) $ticket->requester_user_id === (int) $user->id
            );
    }

    public function reopen(User $user, SupportTicket $ticket): bool
    {
        return in_array($ticket->status, ['resolved', 'closed'], true)
            && (
                $this->canRespond($user)
                || (int) $ticket->requester_user_id === (int) $user->id
            );
    }
}
