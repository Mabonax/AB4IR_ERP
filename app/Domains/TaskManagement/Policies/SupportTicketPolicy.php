<?php

namespace App\Domains\TaskManagement\Policies;

use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Services\TaskWorkflowGovernance;
use App\Models\User;

class SupportTicketPolicy
{
    protected function governance(): TaskWorkflowGovernance
    {
        return app(TaskWorkflowGovernance::class);
    }

    protected function canRespond(User $user): bool
    {
        return $this->governance()->canRespondToTechnicalTickets($user);
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
        return $this->governance()->canManageTechnicalTickets($user)
            && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true);
    }

    public function reply(User $user, SupportTicket $ticket): bool
    {
        return (int) $ticket->requester_user_id === (int) $user->id
            || $this->governance()->canWorkTechnicalTicket($user, $ticket);
    }

    public function resolve(User $user, SupportTicket $ticket): bool
    {
        return $this->canRespond($user)
            && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true)
            && $this->governance()->canWorkTechnicalTicket($user, $ticket);
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
