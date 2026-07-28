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
        <div className="border border-[#ECECEC] bg-white p-5">
            <div className="text-[11px] font-semibold uppercase tracking-[0.28em] text-[#9CA3AF]">{label}</div>
            <div className="mt-3 text-3xl font-semibold tracking-[-0.03em] text-[#111111]">{value}</div>
        </div>
    );
}

function EmptyState({ message }: { message: string }) {
    return <div className="border border-dashed border-[#D1D5DB] bg-[#F7F7F7] p-4 text-sm leading-6 text-[#6B7280]">{message}</div>;
}

function TaskList({ items, emptyMessage }: { items: TaskCard[]; emptyMessage: string }) {
    if (items.length === 0) {
        return <EmptyState message={emptyMessage} />;
    }

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.id} className="border border-[#ECECEC] bg-white p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="font-medium text-[#111111]">{item.title}</div>
                            <div className="mt-1 text-xs text-[#6B7280]">
                                {item.priority.toUpperCase()} | {item.status.replaceAll('_', ' ')}
                                {item.due_date ? ` | Due ${item.due_date}` : ''}
                            </div>
                        </div>
                        <div className="text-right text-xs text-[#6B7280]">
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
                <div key={item.id} className="border border-[#ECECEC] bg-white p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <div className="font-medium text-[#111111]">{item.title}</div>
                            <div className="mt-1 text-xs text-[#6B7280]">
                                {item.priority.toUpperCase()} | {item.status.replaceAll('_', ' ')} | Age {item.age_hours}h
                            </div>
                        </div>
                        <div className="text-right text-xs text-[#6B7280]">
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

            <div className="space-y-6 bg-[#F7F7F7] p-4 md:p-6">
                <section className="border border-[#ECECEC] bg-white">
                    <div className="grid gap-8 p-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.7fr)] lg:p-8">
                        <div className="flex flex-wrap items-start justify-between gap-6">
                            <div className="max-w-2xl">
                                <p className="text-xs font-semibold uppercase tracking-[0.34em] text-[#C8102E]">
                                    Executive dashboard
                                </p>
                                <h1 className="mt-4 text-4xl font-semibold tracking-[-0.04em] text-[#111111]">
                                    Executive operating dashboard
                                </h1>
                                <p className="mt-4 max-w-2xl text-sm leading-7 text-[#4B5563]">
                                    {managerView
                                        ? 'Programme delivery, governance pressure, and organisational workload are prioritised for managers.'
                                        : responderView
                                          ? 'Operational response pressure comes first, with only the active work relevant to your remit.'
                                          : 'Assigned work, requests, and immediate deadlines are prioritised for individual staff.'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {dashboard.tasks.available ? (
                                    <Link href={dashboard.tasks.href} className="inline-flex h-11 items-center border border-[#C8102E] bg-[#C8102E] px-5 text-sm font-medium text-white transition hover:bg-[#D71920]">
                                        {dashboard.tasks.can_create ? 'Open task board' : 'View my tasks'}
                                    </Link>
                                ) : null}
                                <Link href={dashboard.tickets.href} className="inline-flex h-11 items-center border border-[#111111] px-5 text-sm font-medium text-[#111111] transition hover:bg-[#111111] hover:text-white">
                                    Open support tickets
                                </Link>
                            </div>
                        </div>
                        <aside className="border border-[#ECECEC] bg-[#111111] p-6 text-white">
                            <p className="text-[11px] font-semibold uppercase tracking-[0.32em] text-[#FCA5A5]">
                                Operational focus
                            </p>
                            <div className="mt-5 space-y-5">
                                {[
                                    'Use red only for active priorities, approvals, and intervention points.',
                                    'Most surfaces stay white, flat, and document-like to preserve institutional clarity.',
                                    'Secondary panels only surface when your permissions make them actionable.',
                                ].map((item) => (
                                    <div key={item} className="border-t border-white/10 pt-5 text-sm leading-6 text-white/74 first:border-t-0 first:pt-0">
                                        {item}
                                    </div>
                                ))}
                            </div>
                        </aside>
                    </div>

                    {dashboard.tasks.available ? (
                        <div className="space-y-6 border-t border-[#ECECEC] p-6 lg:p-8">
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
                                        <h2 className="text-base font-semibold text-[#111111]">Assigned to me</h2>
                                        <p className="text-sm text-[#6B7280]">The work currently sitting with you.</p>
                                    </div>
                                    <TaskList items={dashboard.tasks.assigned} emptyMessage="No active direct assignments right now." />
                                </section>
                                <section className="space-y-3">
                                    <div>
                                        <h2 className="text-base font-semibold text-[#111111]">Overdue tasks</h2>
                                        <p className="text-sm text-[#6B7280]">Anything past due in your current task visibility scope.</p>
                                    </div>
                                    <TaskList items={dashboard.tasks.overdue} emptyMessage="Nothing overdue in your visible task scope." />
                                </section>
                            </div>

                            {managerView ? (
                                <div className="grid gap-4 xl:grid-cols-2">
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold text-[#111111]">Awaiting signoff</h2>
                                            <p className="text-sm text-[#6B7280]">Submitted work waiting for your approval or return.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.pending_review} emptyMessage="No submitted tasks are waiting for manager signoff." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold text-[#111111]">Tasks I created</h2>
                                            <p className="text-sm text-[#6B7280]">Open work you initiated that still needs delivery.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.created} emptyMessage="No open tasks created by you need attention." />
                                    </section>
                                </div>
                            ) : null}

                            {managerView ? (
                                <div className="grid gap-4 xl:grid-cols-2">
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold text-[#111111]">Department queue</h2>
                                            <p className="text-sm text-[#6B7280]">Unassigned work waiting in your department queue.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.queue} emptyMessage="No unassigned queue work is waiting in your department." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h2 className="text-base font-semibold text-[#111111]">Returned for amendments</h2>
                                            <p className="text-sm text-[#6B7280]">Work sent back to assignees that still needs correction.</p>
                                        </div>
                                        <TaskList items={dashboard.tasks.returned} emptyMessage="No tasks are currently sitting in amendment state." />
                                    </section>
                                </div>
                            ) : null}
                        </div>
                    ) : (
                        <div className="border-t border-[#ECECEC] p-6 lg:p-8">
                            <EmptyState message="Your account does not currently expose the work-task board. Support tickets and role-specific workflow panels remain available below." />
                        </div>
                    )}
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(22rem,1fr)]">
                    <div className="border border-[#ECECEC] bg-white p-6 lg:p-8">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h2 className="text-lg font-semibold text-[#111111]">Support tickets</h2>
                                <p className="mt-1 text-sm leading-6 text-[#6B7280]">
                                    {dashboard.tickets.can_respond
                                        ? 'Incident queue exposure, SLA pressure, and responder workload are prioritized here.'
                                        : 'Support requests you opened or that are currently assigned to you.'}
                                </p>
                            </div>
                            <Link href={dashboard.tickets.href} className="inline-flex h-11 items-center border border-[#111111] px-5 text-sm font-medium text-[#111111] transition hover:bg-[#111111] hover:text-white">
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
                                    <h3 className="text-base font-semibold text-[#111111]">{dashboard.tickets.can_respond ? 'Assigned incidents' : 'Assigned tickets'}</h3>
                                    <p className="text-sm text-[#6B7280]">
                                        {dashboard.tickets.can_respond ? 'The support work that currently needs responder action.' : 'The support work that currently needs your response.'}
                                    </p>
                                </div>
                                <TicketList items={dashboard.tickets.assigned} emptyMessage="No active tickets are currently assigned to you." />
                            </section>
                            <section className="space-y-3">
                                <div>
                                    <h3 className="text-base font-semibold text-[#111111]">Overdue tickets</h3>
                                    <p className="text-sm text-[#6B7280]">Tickets currently running outside SLA.</p>
                                </div>
                                <TicketList items={dashboard.tickets.overdue} emptyMessage="No tickets are currently outside SLA." />
                            </section>
                        </div>

                        <div className="mt-4 grid gap-4 xl:grid-cols-2">
                            {dashboard.tickets.can_respond ? (
                                <>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold text-[#111111]">Unassigned queue</h3>
                                            <p className="text-sm text-[#6B7280]">New technical issues still waiting for ownership.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.unassigned} emptyMessage="No unassigned tickets are waiting in the queue." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold text-[#111111]">Requests I opened</h3>
                                            <p className="text-sm text-[#6B7280]">Your own support issues that remain active alongside responder work.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.requested} emptyMessage="You do not have active support requests right now." />
                                    </section>
                                </>
                            ) : (
                                <>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold text-[#111111]">Requests I opened</h3>
                                            <p className="text-sm text-[#6B7280]">Your support issues that are still active.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.requested} emptyMessage="You do not have active support requests right now." />
                                    </section>
                                    <section className="space-y-3">
                                        <div>
                                            <h3 className="text-base font-semibold text-[#111111]">Unassigned queue</h3>
                                            <p className="text-sm text-[#6B7280]">Visible queue exposure is limited when you are not a responder.</p>
                                        </div>
                                        <TicketList items={dashboard.tickets.unassigned} emptyMessage="No queue items are exposed to your current support workflow." />
                                    </section>
                                </>
                            )}
                        </div>
                    </div>

                    <aside className="border border-[#ECECEC] bg-white p-6 lg:p-8">
                        <div>
                            <h2 className="text-lg font-semibold text-[#111111]">Organisation panels</h2>
                            <p className="mt-1 text-sm leading-6 text-[#6B7280]">
                                Secondary operational data only appears when your permissions make it actionable.
                            </p>
                        </div>

                        <div className="mt-6 space-y-3">
                            {dashboard.secondary.length === 0 ? (
                                <EmptyState message="No additional workflow panels are currently relevant for your permissions." />
                            ) : (
                                dashboard.secondary.map((widget) => (
                                    <Link key={widget.key} href={widget.href} className="block border border-[#ECECEC] p-4 transition hover:bg-[#F7F7F7]">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium text-[#111111]">{widget.title}</div>
                                                <div className="mt-1 text-sm leading-6 text-[#6B7280]">{widget.description}</div>
                                            </div>
                                            <div className="text-2xl font-semibold tracking-[-0.03em] text-[#C8102E]">{widget.value}</div>
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
