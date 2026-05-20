<?php

namespace App\Domains\TaskManagement\Services;

use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\TaskManagement\Models\SupportTicket;
use App\Domains\TaskManagement\Notifications\SupportTicketAssignedNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketOverdueNotification;
use App\Domains\TaskManagement\Notifications\SupportTicketResolvedNotification;
use App\Domains\TaskManagement\Repositories\SupportTicketRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportTicketService
{
    public function __construct(
        protected SupportTicketRepositoryInterface $repository
    ) {}

    public function paginateForUser(User $actor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SupportTicket::query()
            ->with([
                'requester:id,name,email',
                'assignee:id,name,email',
                'requesterDepartment:id,name',
                'assignedDepartment:id,name',
                'project:id,name',
                'program:id,title',
                'asset:id,name,asset_code,asset_category_id',
                'asset.category:id,name',
                'replies.user:id,name,email',
            ])
            ->latest();

        if (! $this->canRespond($actor)) {
            $query->where(function (Builder $builder) use ($actor) {
                $builder->where('requester_user_id', $actor->id)
                    ->orWhere('assigned_to_user_id', $actor->id);
            });
        }

        $query = $this->applyFilters($query, $filters);

        return $this->repository->paginateVisible($query, $perPage);
    }

    public function createTicket(array $data, User $actor): SupportTicket
    {
        $ticket = $this->repository->create([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'],
            'requester_user_id' => $actor->id,
            'requester_department_id' => $actor->staffMember?->department_id,
            'assigned_department_id' => $this->technicalDepartmentId(),
            'project_id' => $data['project_id'] ?: null,
            'program_id' => $data['program_id'] ?: null,
            'asset_id' => $data['asset_id'] ?? null,
        ]);

        $this->notifyTechnicalQueue($ticket, 'A new technical support ticket has been logged.');

        return $ticket->load([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);
    }

    public function assignTicket(SupportTicket $ticket, int $assigneeUserId): SupportTicket
    {
        $assignee = User::query()->with('staffMember')->findOrFail($assigneeUserId);

        if (! $this->canRespond($assignee)) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => ['Selected assignee is not allowed to respond to technical tickets.'],
            ]);
        }

        $ticket = $this->repository->update($ticket, [
            'assigned_to_user_id' => $assignee->id,
            'assigned_department_id' => $assignee->staffMember?->department_id ?? $this->technicalDepartmentId(),
            'status' => 'assigned',
        ]);

        $this->notifyUsers(
            collect([$assignee, $ticket->requester])->filter(),
            new SupportTicketAssignedNotification($ticket, 'A support ticket has been assigned for action.')
        );

        $ticket->forceFill([
            'assignment_notified_at' => now(),
            'overdue_notified_at' => null,
        ])->save();

        return $ticket->load([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);
    }

    public function replyToTicket(SupportTicket $ticket, User $actor, string $message): SupportTicket
    {
        DB::transaction(function () use ($ticket, $actor, $message) {
            $ticket->replies()->create([
                'user_id' => $actor->id,
                'message' => $message,
                'is_resolution' => false,
            ]);

            if ($this->canRespond($actor) && in_array($ticket->status, ['open', 'assigned'], true)) {
                $ticket->update([
                    'status' => 'in_progress',
                    'assigned_to_user_id' => $ticket->assigned_to_user_id ?: $actor->id,
                    'first_responded_at' => $ticket->first_responded_at ?: now(),
                ]);
            }
        });

        return $ticket->fresh([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);
    }

    public function resolveTicket(SupportTicket $ticket, User $actor, string $resolutionSummary): SupportTicket
    {
        DB::transaction(function () use ($ticket, $actor, $resolutionSummary) {
            $ticket->replies()->create([
                'user_id' => $actor->id,
                'message' => $resolutionSummary,
                'is_resolution' => true,
            ]);

            $ticket->update([
                'assigned_to_user_id' => $ticket->assigned_to_user_id ?: $actor->id,
                'status' => 'resolved',
                'resolution_summary' => $resolutionSummary,
                'first_responded_at' => $ticket->first_responded_at ?: now(),
                'resolved_at' => now(),
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);
        });

        $fresh = $ticket->fresh([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);

        $this->notifyUsers(
            collect([$fresh?->requester, $fresh?->assignee])->filter(),
            new SupportTicketResolvedNotification($fresh)
        );

        $fresh?->forceFill([
            'resolved_notified_at' => now(),
        ])->save();

        return $fresh;
    }

    public function closeTicket(SupportTicket $ticket, User $actor, ?string $closingNotes = null): SupportTicket
    {
        DB::transaction(function () use ($ticket, $actor, $closingNotes) {
            if (filled($closingNotes)) {
                $ticket->replies()->create([
                    'user_id' => $actor->id,
                    'message' => $closingNotes,
                    'is_resolution' => false,
                ]);
            }

            $ticket->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_user_id' => $actor->id,
            ]);
        });

        return $ticket->fresh([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);
    }

    public function reopenTicket(SupportTicket $ticket, User $actor, string $reason): SupportTicket
    {
        DB::transaction(function () use ($ticket, $actor, $reason) {
            $ticket->replies()->create([
                'user_id' => $actor->id,
                'message' => $reason,
                'is_resolution' => false,
            ]);

            $ticket->update([
                'status' => $ticket->assigned_to_user_id ? 'assigned' : 'open',
                'resolved_at' => null,
                'closed_at' => null,
                'closed_by_user_id' => null,
            ]);
        });

        return $ticket->fresh([
            'requester:id,name,email',
            'assignee:id,name,email',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
            'replies.user:id,name,email',
        ]);
    }

    public function technicalResponders(): array
    {
        return User::query()
            ->with('staffMember.department')
            ->get()
            ->filter(fn (User $user) => $this->canRespond($user))
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'department_name' => $user->staffMember?->department?->name,
            ])
            ->values()
            ->all();
    }

    public function dashboardSummary(User $actor, array $filters = []): array
    {
        $query = SupportTicket::query();

        if (! $this->canRespond($actor)) {
            $query->where(function (Builder $builder) use ($actor) {
                $builder->where('requester_user_id', $actor->id)
                    ->orWhere('assigned_to_user_id', $actor->id);
            });
        }

        $tickets = $this->applyFilters($query, $filters, ignore: ['status', 'overdue'])->get();

        return [
            'total' => $tickets->count(),
            'open' => $tickets->where('status', 'open')->count(),
            'in_progress' => $tickets->where('status', 'in_progress')->count(),
            'resolved' => $tickets->where('status', 'resolved')->count(),
            'closed' => $tickets->where('status', 'closed')->count(),
            'overdue' => $tickets->filter(fn (SupportTicket $ticket) => $this->slaStatus($ticket)['is_overdue'])->count(),
        ];
    }

    public function operationalDashboard(User $actor): array
    {
        $query = SupportTicket::query()->with([
            'requester:id,name',
            'assignee:id,name',
            'requesterDepartment:id,name',
            'assignedDepartment:id,name',
            'project:id,name',
            'program:id,title',
            'asset:id,name,asset_code,asset_category_id',
            'asset.category:id,name',
        ]);

        if (! $this->canRespond($actor)) {
            $query->where(function (Builder $builder) use ($actor) {
                $builder->where('requester_user_id', $actor->id)
                    ->orWhere('assigned_to_user_id', $actor->id);
            });
        }

        $tickets = $query->latest()->get();

        $overdueTickets = $tickets
            ->filter(fn (SupportTicket $ticket) => $this->slaStatus($ticket)['is_overdue'])
            ->take(5)
            ->values();

        $unassignedTickets = $tickets
            ->filter(fn (SupportTicket $ticket) => $ticket->assigned_to_user_id === null && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true))
            ->take(5)
            ->values();

        $workloadByResponder = $tickets
            ->filter(fn (SupportTicket $ticket) => $ticket->assigned_to_user_id !== null && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true))
            ->groupBy('assigned_to_user_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'responder_name' => $first?->assignee?->name ?? 'Unknown',
                    'open_count' => $items->where('status', 'open')->count(),
                    'assigned_count' => $items->where('status', 'assigned')->count(),
                    'in_progress_count' => $items->where('status', 'in_progress')->count(),
                    'active_count' => $items->count(),
                ];
            })
            ->sortByDesc('active_count')
            ->take(6)
            ->values()
            ->all();

        $projectPressure = $tickets
            ->filter(fn (SupportTicket $ticket) => $ticket->project_id !== null && in_array($ticket->status, ['open', 'assigned', 'in_progress'], true))
            ->groupBy('project_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'project_name' => $first?->project?->name ?? 'Unknown',
                    'active_count' => $items->count(),
                    'overdue_count' => $items->filter(fn (SupportTicket $ticket) => $this->slaStatus($ticket)['is_overdue'])->count(),
                ];
            })
            ->sortByDesc('active_count')
            ->take(6)
            ->values()
            ->all();

        return [
            'summary' => $this->dashboardSummary($actor),
            'overdue_tickets' => $overdueTickets->map(function (SupportTicket $ticket) {
                $sla = $this->slaStatus($ticket);

                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'requester_name' => $ticket->requester?->name,
                    'responder_name' => $ticket->assignee?->name,
                    'age_hours' => $sla['age_hours'],
                    'sla_target_hours' => $sla['sla_target_hours'],
                ];
            })->all(),
            'unassigned_queue' => $unassignedTickets->map(function (SupportTicket $ticket) {
                $sla = $this->slaStatus($ticket);

                return [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'requester_name' => $ticket->requester?->name,
                    'age_hours' => $sla['age_hours'],
                ];
            })->all(),
            'workload_by_responder' => $workloadByResponder,
            'project_pressure' => $projectPressure,
        ];
    }

    public function sendOverdueReminders(): int
    {
        $tickets = SupportTicket::query()
            ->with(['requester:id,name,email', 'assignee:id,name,email'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereNull('overdue_notified_at')
            ->get()
            ->filter(fn (SupportTicket $ticket) => $this->slaStatus($ticket)['is_overdue']);

        foreach ($tickets as $ticket) {
            $sla = $this->slaStatus($ticket);
            $recipients = collect([$ticket->assignee, $ticket->requester])->filter();

            if ($recipients->isEmpty()) {
                $recipients = collect($this->technicalResponders())
                    ->map(fn (array $item) => User::query()->find($item['id']))
                    ->filter();
            }

            $this->notifyUsers(
                $recipients,
                new SupportTicketOverdueNotification($ticket, $sla['age_hours'], $sla['sla_target_hours'])
            );

            $ticket->forceFill([
                'overdue_notified_at' => now(),
            ])->save();
        }

        return $tickets->count();
    }

    public function slaStatus(SupportTicket $ticket): array
    {
        $targets = [
            'low' => 72,
            'medium' => 48,
            'high' => 24,
            'urgent' => 8,
        ];

        $targetHours = $targets[$ticket->priority] ?? 48;
        $end = $ticket->resolved_at ?? now();
        $ageHours = max(0, (int) ceil(Carbon::parse($ticket->created_at)->diffInMinutes($end) / 60));
        $isResolved = in_array($ticket->status, ['resolved', 'closed'], true);
        $isOverdue = ! $isResolved && $ageHours > $targetHours;
        $firstResponseHours = $ticket->first_responded_at
            ? max(0, (int) ceil(Carbon::parse($ticket->created_at)->diffInMinutes($ticket->first_responded_at) / 60))
            : null;

        return [
            'age_hours' => $ageHours,
            'first_response_hours' => $firstResponseHours,
            'sla_target_hours' => $targetHours,
            'is_overdue' => $isOverdue,
            'sla_status' => $isResolved ? 'resolved' : ($isOverdue ? 'overdue' : 'within_sla'),
        ];
    }

    protected function technicalDepartmentId(): ?int
    {
        return StaffDepartment::query()
            ->whereRaw('LOWER(name) = ?', ['technical'])
            ->value('id');
    }

    protected function canRespond(User $user): bool
    {
        return $user->can('technical-tickets.respond')
            || $user->hasAnyRole(['super-admin', 'super admin', 'admin']);
    }

    protected function applyFilters(Builder $query, array $filters, array $ignore = []): Builder
    {
        if (! in_array('status', $ignore, true) && filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['priority'] ?? null)) {
            $query->where('priority', $filters['priority']);
        }

        if (filled($filters['assigned_to_user_id'] ?? null)) {
            $query->where('assigned_to_user_id', (int) $filters['assigned_to_user_id']);
        }

        if (filled($filters['project_id'] ?? null)) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (filled($filters['program_id'] ?? null)) {
            $query->where('program_id', (int) $filters['program_id']);
        }

        if (filled($filters['asset_id'] ?? null)) {
            $query->where('asset_id', (int) $filters['asset_id']);
        }

        if (filled($filters['requester_user_id'] ?? null)) {
            $query->where('requester_user_id', (int) $filters['requester_user_id']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if (! in_array('overdue', $ignore, true) && filter_var($filters['overdue'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotIn('status', ['resolved', 'closed']);
            $query->where(function (Builder $builder) {
                $builder
                    ->where(function (Builder $urgent) {
                        $urgent->where('priority', 'urgent')->where('created_at', '<', now()->subHours(8));
                    })
                    ->orWhere(function (Builder $high) {
                        $high->where('priority', 'high')->where('created_at', '<', now()->subHours(24));
                    })
                    ->orWhere(function (Builder $medium) {
                        $medium->where('priority', 'medium')->where('created_at', '<', now()->subHours(48));
                    })
                    ->orWhere(function (Builder $low) {
                        $low->where('priority', 'low')->where('created_at', '<', now()->subHours(72));
                    });
            });
        }

        return $query;
    }

    protected function notifyTechnicalQueue(SupportTicket $ticket, string $context): void
    {
        $recipients = User::query()
            ->with('staffMember.department')
            ->get()
            ->filter(fn (User $user) => $this->canRespond($user));

        $this->notifyUsers($recipients, new SupportTicketAssignedNotification($ticket, $context));

        $ticket->forceFill([
            'assignment_notified_at' => now(),
            'overdue_notified_at' => null,
        ])->save();
    }

    protected function notifyUsers(Collection $users, object $notification): void
    {
        $users->unique('id')->each(fn (User $user) => $user->notify($notification));
    }
}
