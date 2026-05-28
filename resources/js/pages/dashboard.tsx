import { Head, Link } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { dashboard as dashboardRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type TaskCard = {
    id: number;
    title: string;
    status: string;
    priority: string;
    due_date: string | null;
    assignee_name?: string | null;
    department_name?: string | null;
    context_name?: string | null;
};

type TicketCard = {
    id: number;
    title: string;
    status: string;
    priority: string;
    requester_name: string | null;
    responder_name: string | null;
    age_hours: number;
    sla_target_hours: number;
};

type Widget = {
    key: string;
    title: string;
    value: number;
    description: string;
    href: string;
};

type DashboardPayload = {
    tasks: {
        available: boolean;
        can_create: boolean;
        persona: string;
        href: string;
        summary: {
            total: number;
            assigned_to_me: number;
            created_by_me: number;
            overdue: number;
            pending_review: number;
            changes_requested: number;
            unassigned_queue: number;
        };
        assigned: TaskCard[];
        created: TaskCard[];
        overdue: TaskCard[];
        queue: TaskCard[];
        pending_review: TaskCard[];
        returned: TaskCard[];
    };
    tickets: {
        available: boolean;
        can_respond: boolean;
        persona: string;
        href: string;
        summary: {
            total: number;
            assigned_to_me: number;
            requested_by_me: number;
            overdue: number;
            unassigned_queue: number;
        };
        assigned: TicketCard[];
        requested: TicketCard[];
        overdue: TicketCard[];
        unassigned: TicketCard[];
    };
    secondary: Widget[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboardRoute().url,
    },
];

function MetricCard({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-2xl border bg-card p-4 shadow-sm">
            <div className="text-xs uppercase tracking-[0.2em] text-muted-foreground">{label}</div>
            <div className="mt-3 text-3xl font-semibold">{value}</div>
        </div>
    );
}

function EmptyState({ message }: { message: string }) {
    return <div className="rounded-2xl border border-dashed p-4 text-sm text-muted-foreground">{message}</div>;
}

function TaskList({ items, emptyMessage }: { items: TaskCard[]; emptyMessage: string }) {
    if (items.length === 0) {
        return <EmptyState message={emptyMessage} />;
    }

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.id} className="rounded-2xl border bg-background p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="font-medium">{item.title}</div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {item.priority.toUpperCase()} | {item.status.replaceAll('_', ' ')}
                                {item.due_date ? ` | Due ${item.due_date}` : ''}
                            </div>
                        </div>
                        <div className="text-right text-xs text-muted-foreground">
                            <div>{item.assignee_name ?? item.department_name ?? 'Queue'}</div>
                            <div>{item.context_name ?? 'General'}</div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function TicketList({ items, emptyMessage }: { items: TicketCard[]; emptyMessage: string }) {
    if (items.length === 0) {
        return <EmptyState message={emptyMessage} />;
    }

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.id} className="rounded-2xl border bg-background p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="font-medium">{item.title}</div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {item.priority.toUpperCase()} | {item.status.replaceAll('_', ' ')} | Age {item.age_hours}h
                            </div>
                        </div>
                        <div className="text-right text-xs text-muted-foreground">
                            <div>Requester {item.requester_name ?? '-'}</div>
                            <div>Responder {item.responder_name ?? 'Unassigned'}</div>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Dashboard({ dashboard }: { dashboard: DashboardPayload }) {
    const managerView = dashboard.tasks.persona === 'manager';
    const responderView = dashboard.tickets.can_respond && dashboard.tickets.persona === 'technical_responder';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4">
                <section className="rounded-[2rem] border bg-card p-6 shadow-sm">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div className="max-w-2xl">
                            <h1 className="text-2xl font-semibold">My work dashboard</h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {managerView
                                    ? 'Operational delivery, team workload, and escalation pressure are prioritized for managers.'
                                    : responderView
                                        ? 'Ticket response pressure comes first, with only the task work directly relevant to you.'
                                        : 'Assigned work, ticket requests, and immediate deadlines are prioritized for individual staff.'}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            {dashboard.tasks.available ? (
                                <Link href={dashboard.tasks.href} className="rounded-full border px-4 py-2 text-sm font-medium">
                                    {dashboard.tasks.can_create ? 'Open task board' : 'View my tasks'}
                                </Link>
                            ) : null}
                            <Link href={dashboard.tickets.href} className="rounded-full border px-4 py-2 text-sm font-medium">
                                Open support tickets
                            </Link>
                        </div>
                    </div>

                    {dashboard.tasks.available ? (
                        <div className="mt-6 space-y-6">
                            <div className="grid gap-3 md:grid-cols-4 xl:grid-cols-5">
                                <MetricCard label="Visible Tasks" value={dashboard.tasks.summary.total} />
                                <MetricCard label="Assigned To Me" value={dashboard.tasks.summary.assigned_to_me} />
                                <MetricCard label="Created By Me" value={dashboard.tasks.summary.created_by_me} />
                                <MetricCard label="Overdue" value={dashboard.tasks.summary.overdue} />
                                <MetricCard label="Awaiting Review" value={dashboard.tasks.summary.pending_review} />
                                <MetricCard label="Returned" value={dashboard.tasks.summary.changes_requested} />
                                <MetricCard label="Queue Intake" value={dashboard.tasks.summary.unassigned_queue} />
                            </div>

                            <div className="grid gap-4 xl:grid-cols-2">
                                <section className="space-y-3">
                                    <div>
                                        <h2 className="text-base font-semibold">Assigned to me</h2>
                                        <p className="text-sm text-muted-foreground">The work currently sitting with you.</p>
                                    </div>
                                    <TaskList items={dashboard.tasks.assigned} emptyMessage="No active direct assignments right now." />
                                </section>
                                <section className="space-y-3">
                                    <div>
                                        <h2 className="text-base font-semibold">Overdue tasks</h2>
                                        <p className="text-sm text-muted-foreground">Anything past due in your current task visibility scope.</p>
                                    </div>
                                    <TaskList items={dashboard.tasks.overdue} emptyMessage="Nothing overdue in your visible task scope." />
                                </section>
                            </div>

                            {managerView ? (
                                <div className="grid gap-4 xl:grid-cols-2">
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold">Awaiting signoff</h2>
                                            <p className="text-sm text-muted-foreground">Submitted work waiting for your approval or return.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.pending_review} emptyMessage="No submitted tasks are waiting for manager signoff." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold">Tasks I created</h2>
                                            <p className="text-sm text-muted-foreground">Open work you initiated that still needs delivery.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.created} emptyMessage="No open tasks created by you need attention." />
                                    </section>
                                </div>
                            ) : null}

                            {managerView ? (
                                <div className="grid gap-4 xl:grid-cols-2">
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold">Department queue</h2>
                                            <p className="text-sm text-muted-foreground">Unassigned work waiting in your department queue.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.queue} emptyMessage="No unassigned queue work is waiting in your department." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold">Returned for amendments</h2>
                                            <p className="text-sm text-muted-foreground">Work sent back to assignees that still needs correction.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.returned} emptyMessage="No tasks are currently sitting in amendment state." />
                                    </section>
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        <div className="mt-6">
                            <EmptyState message="Your account does not currently expose the work-task board. Support tickets and role-specific workflow panels remain available below." />
                        </div>
                    )}
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(22rem,1fr)]">
                    <div className="rounded-[2rem] border bg-card p-6 shadow-sm">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold">Support tickets</h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {dashboard.tickets.can_respond
                                        ? 'Incident queue exposure, SLA pressure, and responder workload are prioritized here.'
                                        : 'Support requests you opened or that are currently assigned to you.'}
                                </p>
                            </div>
                            <Link href={dashboard.tickets.href} className="rounded-full border px-4 py-2 text-sm font-medium">
                                View queue
                            </Link>
                        </div>

                        <div className="mt-6 grid gap-3 md:grid-cols-4 xl:grid-cols-5">
                            <MetricCard label="Visible Tickets" value={dashboard.tickets.summary.total} />
                            <MetricCard label="Assigned To Me" value={dashboard.tickets.summary.assigned_to_me} />
                            <MetricCard label="Requested By Me" value={dashboard.tickets.summary.requested_by_me} />
                            <MetricCard label="Overdue" value={dashboard.tickets.summary.overdue} />
                            <MetricCard label="Queue Intake" value={dashboard.tickets.summary.unassigned_queue} />
                        </div>

                        <div className="mt-6 grid gap-4 xl:grid-cols-2">
                            <section className="space-y-3">
                                <div>
                                    <h3 className="text-base font-semibold">{dashboard.tickets.can_respond ? 'Assigned incidents' : 'Assigned tickets'}</h3>
                                    <p className="text-sm text-muted-foreground">
                                        {dashboard.tickets.can_respond ? 'The support work that currently needs responder action.' : 'The support work that currently needs your response.'}
                                    </p>
                                </div>
                                <TicketList items={dashboard.tickets.assigned} emptyMessage="No active tickets are currently assigned to you." />
                            </section>
                            <section className="space-y-3">
                                <div>
                                    <h3 className="text-base font-semibold">Overdue tickets</h3>
                                    <p className="text-sm text-muted-foreground">Tickets currently running outside SLA.</p>
                                </div>
                                <TicketList items={dashboard.tickets.overdue} emptyMessage="No tickets are currently outside SLA." />
                            </section>
                        </div>

                        <div className="mt-4 grid gap-4 xl:grid-cols-2">
                            {dashboard.tickets.can_respond ? (
                                <>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold">Unassigned queue</h3>
                                            <p className="text-sm text-muted-foreground">New technical issues still waiting for ownership.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.unassigned} emptyMessage="No unassigned tickets are waiting in the queue." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold">Requests I opened</h3>
                                            <p className="text-sm text-muted-foreground">Your own support issues that remain active alongside responder work.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.requested} emptyMessage="You do not have active support requests right now." />
                                    </section>
                                </>
                            ) : (
                                <>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold">Requests I opened</h3>
                                            <p className="text-sm text-muted-foreground">Your support issues that are still active.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.requested} emptyMessage="You do not have active support requests right now." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold">Unassigned queue</h3>
                                            <p className="text-sm text-muted-foreground">Visible queue exposure is limited when you are not a responder.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.unassigned} emptyMessage="No queue items are exposed to your current support workflow." />
                                    </section>
                                </>
                            )}
                        </div>
                    </div>

                    <aside className="rounded-[2rem] border bg-card p-6 shadow-sm">
                        <div>
                            <h2 className="text-lg font-semibold">Role-specific panels</h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Secondary data only appears when your permissions make it actionable.
                            </p>
                        </div>

                        <div className="mt-6 space-y-3">
                            {dashboard.secondary.length === 0 ? (
                                <EmptyState message="No additional workflow panels are currently relevant for your permissions." />
                            ) : (
                                dashboard.secondary.map((widget) => (
                                    <Link key={widget.key} href={widget.href} className="block rounded-2xl border p-4 transition hover:bg-muted/40">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium">{widget.title}</div>
                                                <div className="mt-1 text-sm text-muted-foreground">{widget.description}</div>
                                            </div>
                                            <div className="text-2xl font-semibold">{widget.value}</div>
                                        </div>
                                    </Link>
                                ))
                            )}
                        </div>
                    </aside>
                </section>
            </div>
        </AppLayout>
    );
}
