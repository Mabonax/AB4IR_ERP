import { Head, Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Box,
    BriefcaseBusiness,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Eye,
    FilePenLine,
    Folder,
    Headphones,
    Inbox,
    LayoutDashboard,
    LifeBuoy,
    Loader2,
    MoreVertical,
    PlusCircle,
    RotateCcw,
    Search,
    Settings,
    Sun,
    Users,
    XCircle,
} from 'lucide-react';
import { type ComponentType, type SVGProps, useMemo, useState } from 'react';

import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import FlashMessages from '@/components/flash-messages';
import { StaffAttendancePrompt } from '@/components/staff-attendance-prompt';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';

type IconType = ComponentType<SVGProps<SVGSVGElement>>;

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

type Metric = {
    label: string;
    value: number;
    Icon: IconType;
    tone: string;
    bar: string;
};

type Panel = {
    key: string;
    title: string;
    description: string;
    Icon: IconType;
    tone: string;
    accent: string;
    emptyMessage: string;
    items: Array<TaskCard | TicketCard>;
};

const isTicketCard = (item: TaskCard | TicketCard): item is TicketCard =>
    'responder_name' in item;

const queryIncludes = (query: string, ...values: Array<string | number | null | undefined>) => {
    if (!query) {
        return true;
    }

    return values
        .filter((value) => value !== null && value !== undefined)
        .some((value) => String(value).toLowerCase().includes(query));
};

function DashboardTopbar({
    query,
    onQueryChange,
}: {
    query: string;
    onQueryChange: (query: string) => void;
}) {
    const { auth, notifications } = usePage<SharedData>().props;
    const user = auth?.user;
    const initials = (user?.name ?? 'Local Admin')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

    return (
        <header className="sticky top-0 z-20 flex h-18 items-center justify-between border-b border-border bg-background/95 dark:border-white/[0.08] dark:bg-[#0a1017]/95 px-6 backdrop-blur">
            <div className="flex items-center gap-4">
                <SidebarTrigger className="size-9 rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.04] text-orange-400 hover:bg-orange-500/10 hover:text-orange-300" />
                <div className="flex items-center gap-3">
                    <span className="flex size-9 items-center justify-center rounded-xl bg-orange-500/10 text-orange-400">
                        <LayoutDashboard className="size-5" />
                    </span>
                    <span className="font-semibold text-foreground dark:text-white">Dashboard</span>
                </div>
            </div>

            <div className="flex min-w-0 flex-1 items-center justify-end gap-4">
                <label className="relative hidden w-full max-w-[20rem] lg:block">
                    <span className="sr-only">Search dashboard</span>
                    <Search className="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-muted-foreground dark:text-white/70" />
                    <input
                        value={query}
                        onChange={(event) => onQueryChange(event.target.value)}
                        placeholder="Search anything..."
                        className="h-11 w-full rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.045] pr-12 pl-4 text-sm text-foreground outline-none dark:text-white transition placeholder:text-muted-foreground dark:placeholder:text-white/45 focus:border-orange-400/60 focus:ring-4 focus:ring-orange-500/10"
                        type="search"
                    />
                </label>

                <button className="relative flex size-10 items-center justify-center rounded-full text-muted-foreground dark:text-white/80 transition hover:bg-muted hover:text-foreground dark:hover:bg-white/[0.06] dark:hover:text-white">
                    <Bell className="size-5" />
                    {(notifications?.unread_count ?? 0) > 0 ? (
                        <span className="absolute -top-1 -right-1 flex size-5 items-center justify-center rounded-full bg-orange-500 text-[10px] font-bold text-white">
                            {notifications?.unread_count}
                        </span>
                    ) : null}
                </button>
                <div className="hidden items-center gap-2 rounded-full border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-1 lg:flex">
                    <button className="flex size-8 items-center justify-center rounded-full text-muted-foreground dark:text-white/70 transition hover:bg-muted hover:text-foreground dark:hover:bg-white/[0.06] dark:hover:text-white">
                        <Sun className="size-4" />
                    </button>
                    <button className="flex size-8 items-center justify-center rounded-full text-muted-foreground dark:text-white/70 transition hover:bg-muted hover:text-foreground dark:hover:bg-white/[0.06] dark:hover:text-white">
                        <Settings className="size-4" />
                    </button>
                </div>
                <div className="flex items-center gap-3">
                    <span className="flex size-10 items-center justify-center rounded-full bg-gradient-to-br from-red-600 to-orange-500 text-sm font-semibold text-white">
                        {initials}
                    </span>
                    <div className="hidden text-right lg:block">
                        <p className="text-sm font-semibold text-foreground dark:text-white">{user?.name ?? 'Local Super Admin'}</p>
                        <p className="text-xs text-muted-foreground dark:text-white/55">{user?.roles?.[0] ?? 'Administrator'}</p>
                    </div>
                </div>
            </div>
        </header>
    );
}

function MetricCard({ metric }: { metric: Metric }) {
    const Icon = metric.Icon;

    return (
        <div className="rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.045] p-4 shadow-[0_20px_60px_rgba(0,0,0,0.22)]">
            <div className="flex items-start gap-4">
                <span className={`flex size-12 items-center justify-center rounded-full ${metric.tone}`}>
                    <Icon className="size-6" />
                </span>
                <div className="min-w-0">
                    <p className="text-sm text-muted-foreground dark:text-white/78">{metric.label}</p>
                    <p className="mt-1 text-3xl font-semibold text-foreground dark:text-white">{metric.value}</p>
                </div>
            </div>
            <div className="mt-4 h-1 rounded-full bg-muted dark:bg-white/[0.06]">
                <div className={`h-full w-12 rounded-full ${metric.bar}`} />
            </div>
        </div>
    );
}

function EmptyState({ message }: { message: string }) {
    return (
        <div className="rounded-lg border border-border bg-muted/40 dark:border-white/[0.06] dark:bg-black/10 px-5 py-4 text-sm text-muted-foreground dark:text-white/58">
            {message}
        </div>
    );
}

function PanelCard({ panel }: { panel: Panel }) {
    const Icon = panel.Icon;

    return (
        <section className={`rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-5 shadow-[0_18px_55px_rgba(0,0,0,0.2)] ${panel.accent}`}>
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-start gap-4">
                    <span className={`mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg ${panel.tone}`}>
                        <Icon className="size-5" />
                    </span>
                    <div>
                        <h3 className="font-semibold text-foreground dark:text-white">{panel.title}</h3>
                        <p className="mt-2 text-xs leading-5 text-muted-foreground dark:text-white/55">{panel.description}</p>
                    </div>
                </div>
                <button className="rounded-md p-1 text-muted-foreground dark:text-white/50 transition hover:bg-muted hover:text-foreground dark:hover:bg-white/[0.06] dark:hover:text-white" aria-label={`${panel.title} options`}>
                    <MoreVertical className="size-5" />
                </button>
            </div>

            <div className="mt-5">
                {panel.items.length === 0 ? (
                    <EmptyState message={panel.emptyMessage} />
                ) : (
                    <div className="space-y-3">
                        {panel.items.map((item) => (
                            <div key={`${panel.key}-${item.id}`} className="rounded-lg border border-border bg-muted/40 dark:border-white/[0.06] dark:bg-black/10 p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium text-foreground dark:text-white">{item.title}</p>
                                        <p className="mt-1 text-xs text-muted-foreground dark:text-white/52">
                                            {item.priority.toUpperCase()} | {item.status.replaceAll('_', ' ')}
                                            {'due_date' in item && item.due_date ? ` | Due ${item.due_date}` : ''}
                                            {'age_hours' in item ? ` | Age ${item.age_hours}h` : ''}
                                        </p>
                                    </div>
                                    <p className="text-right text-xs text-muted-foreground dark:text-white/46">
                                        {isTicketCard(item)
                                            ? (item.responder_name ?? 'Unassigned')
                                            : (item.assignee_name ?? item.department_name ?? 'Queue')}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}

function TicketMetrics({ dashboard }: { dashboard: DashboardPayload }) {
    const metrics: Metric[] = [
        { label: 'Visible tickets', value: dashboard.tickets.summary.total, Icon: Headphones, tone: 'bg-orange-500/12 text-orange-400', bar: 'bg-orange-500' },
        { label: 'Assigned to me', value: dashboard.tickets.summary.assigned_to_me, Icon: Users, tone: 'bg-muted dark:bg-white/[0.08] text-muted-foreground dark:text-white/70', bar: 'bg-white/25' },
        { label: 'Requested by me', value: dashboard.tickets.summary.requested_by_me, Icon: PlusCircle, tone: 'bg-muted dark:bg-white/[0.08] text-muted-foreground dark:text-white/70', bar: 'bg-white/25' },
        { label: 'Overdue', value: dashboard.tickets.summary.overdue, Icon: Clock3, tone: 'bg-red-500/12 text-red-400', bar: 'bg-red-500' },
        { label: 'Queue intake', value: dashboard.tickets.summary.unassigned_queue, Icon: Inbox, tone: 'bg-blue-500/12 text-blue-400', bar: 'bg-blue-500' },
    ];

    return (
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            {metrics.map((metric) => (
                <div key={metric.label} className="rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-4">
                    <p className="text-xs text-muted-foreground dark:text-white/58">{metric.label}</p>
                    <p className="mt-2 text-3xl font-semibold text-foreground dark:text-white">{metric.value}</p>
                </div>
            ))}
        </div>
    );
}

function RolePanel({ widget, Icon, tone }: { widget: Widget; Icon: IconType; tone: string }) {
    return (
        <Link href={widget.href} className="block rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-4 transition hover:border-orange-500/45 hover:bg-orange-500/5">
            <div className="flex items-center gap-4">
                <span className={`flex size-10 items-center justify-center rounded-lg ${tone}`}>
                    <Icon className="size-5" />
                </span>
                <div className="min-w-0 flex-1">
                    <p className="font-semibold text-foreground dark:text-white">{widget.title}</p>
                    <p className="mt-1 line-clamp-2 text-xs leading-4 text-muted-foreground dark:text-white/55">{widget.description}</p>
                </div>
                <span className="text-2xl font-semibold text-foreground dark:text-white">{widget.value}</span>
            </div>
        </Link>
    );
}

export default function Dashboard({ dashboard }: { dashboard: DashboardPayload }) {
    const [query, setQuery] = useState('');
    const normalizedQuery = query.trim().toLowerCase();
    const managerView = dashboard.tasks.persona === 'manager';
    const responderView = dashboard.tickets.can_respond && dashboard.tickets.persona === 'technical_responder';

    const taskMetrics: Metric[] = [
        { label: 'Visible tasks', value: dashboard.tasks.summary.total, Icon: Eye, tone: 'bg-orange-500/12 text-orange-400', bar: 'bg-orange-500' },
        { label: 'Assigned to me', value: dashboard.tasks.summary.assigned_to_me, Icon: Users, tone: 'bg-orange-500/12 text-orange-400', bar: 'bg-orange-500' },
        { label: 'Created by me', value: dashboard.tasks.summary.created_by_me, Icon: PlusCircle, tone: 'bg-red-500/12 text-red-400', bar: 'bg-red-500' },
        { label: 'Overdue', value: dashboard.tasks.summary.overdue, Icon: Clock3, tone: 'bg-red-500/12 text-red-400', bar: 'bg-red-500' },
        { label: 'Awaiting review', value: dashboard.tasks.summary.pending_review, Icon: Loader2, tone: 'bg-amber-500/12 text-amber-400', bar: 'bg-amber-500' },
        { label: 'Returned', value: dashboard.tasks.summary.changes_requested, Icon: RotateCcw, tone: 'bg-purple-500/12 text-purple-400', bar: 'bg-purple-500' },
        { label: 'Queue intake', value: dashboard.tasks.summary.unassigned_queue, Icon: Inbox, tone: 'bg-blue-500/12 text-blue-400', bar: 'bg-blue-500' },
    ];

    const taskPanels: Panel[] = [
        {
            key: 'assigned',
            title: 'Assigned to me',
            description: 'The work currently sitting with you.',
            Icon: Users,
            tone: 'text-orange-400',
            accent: 'border-l-2 border-l-orange-500',
            emptyMessage: 'No active direct assignments right now.',
            items: dashboard.tasks.assigned,
        },
        {
            key: 'overdue',
            title: 'Overdue tasks',
            description: 'Anything past due in your current task visibility scope.',
            Icon: Clock3,
            tone: 'text-red-400',
            accent: 'border-l-2 border-l-red-500',
            emptyMessage: 'Nothing overdue in your visible task scope.',
            items: dashboard.tasks.overdue,
        },
        {
            key: 'pending-review',
            title: 'Awaiting signoff',
            description: 'Submitted work waiting for your approval or return.',
            Icon: FilePenLine,
            tone: 'text-purple-400',
            accent: 'border-l-2 border-l-purple-500',
            emptyMessage: 'No submitted tasks are waiting for manager signoff.',
            items: managerView ? dashboard.tasks.pending_review : [],
        },
        {
            key: 'created',
            title: 'Tasks I created',
            description: 'Open work you initiated that still needs delivery.',
            Icon: PlusCircle,
            tone: 'text-blue-400',
            accent: 'border-l-2 border-l-blue-500',
            emptyMessage: 'No open tasks created by you need attention.',
            items: managerView ? dashboard.tasks.created : [],
        },
        {
            key: 'queue',
            title: 'Department queue',
            description: 'Unassigned work waiting in your department queue.',
            Icon: Folder,
            tone: 'text-blue-400',
            accent: 'border-l-2 border-l-blue-500',
            emptyMessage: 'No unassigned queue work is waiting in your department.',
            items: managerView ? dashboard.tasks.queue : [],
        },
        {
            key: 'returned',
            title: 'Returned for amendments',
            description: 'Work sent back to assignees that still needs correction.',
            Icon: RotateCcw,
            tone: 'text-orange-400',
            accent: 'border-l-2 border-l-orange-500',
            emptyMessage: 'No tasks are currently sitting in amendment state.',
            items: managerView ? dashboard.tasks.returned : [],
        },
    ];

    const ticketPanels: Panel[] = [
        {
            key: 'assigned-incidents',
            title: dashboard.tickets.can_respond ? 'Assigned incidents' : 'Assigned tickets',
            description: dashboard.tickets.can_respond
                ? 'The support work that currently needs responder action.'
                : 'The support work that currently needs your response.',
            Icon: Users,
            tone: 'text-orange-400',
            accent: '',
            emptyMessage: 'No active tickets are currently assigned to you.',
            items: dashboard.tickets.assigned,
        },
        {
            key: 'overdue-tickets',
            title: 'Overdue tickets',
            description: 'Tickets currently running outside SLA.',
            Icon: Clock3,
            tone: 'text-red-400',
            accent: '',
            emptyMessage: 'No tickets are currently outside SLA.',
            items: dashboard.tickets.overdue,
        },
        {
            key: 'unassigned-tickets',
            title: 'Unassigned queue',
            description: dashboard.tickets.can_respond
                ? 'New technical issues still waiting for ownership.'
                : 'Visible queue exposure is limited when you are not a responder.',
            Icon: Inbox,
            tone: 'text-blue-400',
            accent: '',
            emptyMessage: dashboard.tickets.can_respond
                ? 'No unassigned tickets are waiting in the queue.'
                : 'No queue items are exposed to your current support workflow.',
            items: dashboard.tickets.unassigned,
        },
        {
            key: 'requested-tickets',
            title: 'Requests I opened',
            description: dashboard.tickets.can_respond
                ? 'Your own support issues that remain active alongside responder work.'
                : 'Your support issues that are still active.',
            Icon: PlusCircle,
            tone: 'text-lime-400',
            accent: '',
            emptyMessage: 'You do not have active support requests right now.',
            items: dashboard.tickets.requested,
        },
    ];

    const roleIcons = [CalendarDays, BriefcaseBusiness, BriefcaseBusiness, Box, Users, Clock3];
    const roleTones = [
        'bg-amber-500/12 text-amber-400',
        'bg-purple-500/12 text-purple-400',
        'bg-blue-500/12 text-blue-400',
        'bg-cyan-500/12 text-cyan-400',
        'bg-lime-500/12 text-lime-400',
        'bg-orange-500/12 text-orange-400',
    ];

    const visibleTaskMetrics = useMemo(
        () => taskMetrics.filter((metric) => queryIncludes(normalizedQuery, metric.label, metric.value, 'task')),
        [normalizedQuery],
    );
    const visibleTaskPanels = useMemo(
        () =>
            taskPanels.filter((panel) =>
                queryIncludes(normalizedQuery, panel.title, panel.description, panel.emptyMessage, panel.items.map((item) => item.title).join(' ')),
            ),
        [normalizedQuery],
    );
    const visibleTicketPanels = useMemo(
        () =>
            ticketPanels.filter((panel) =>
                queryIncludes(normalizedQuery, panel.title, panel.description, panel.emptyMessage, panel.items.map((item) => item.title).join(' ')),
            ),
        [normalizedQuery],
    );
    const visibleRolePanels = useMemo(
        () => dashboard.secondary.filter((widget) => queryIncludes(normalizedQuery, widget.title, widget.description, widget.value, 'role panel')),
        [dashboard.secondary, normalizedQuery],
    );

    return (
        <AppShell variant="sidebar">
            <Head title="Dashboard" />
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden bg-background text-foreground dark:bg-[#0a1017] dark:text-white">
                <DashboardTopbar query={query} onQueryChange={setQuery} />
                <FlashMessages />
                <StaffAttendancePrompt />

                <div className="mx-auto w-full max-w-[98rem] space-y-7 px-6 py-8">
                    <div className="lg:hidden">
                        <label className="relative block">
                            <span className="sr-only">Search dashboard</span>
                            <Search className="pointer-events-none absolute top-1/2 right-4 size-5 -translate-y-1/2 text-muted-foreground dark:text-white/70" />
                            <input
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Search anything..."
                                className="h-11 w-full rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.045] pr-12 pl-4 text-sm text-foreground outline-none dark:text-white placeholder:text-muted-foreground dark:placeholder:text-white/45 focus:border-orange-400/60"
                                type="search"
                            />
                        </label>
                    </div>

                    <section className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight text-foreground dark:text-white">Welcome back, Super Admin 👋</h1>
                            <p className="mt-2 text-sm text-muted-foreground dark:text-white/60">
                                {managerView
                                    ? 'Operational delivery, team workload, and escalation pressure are prioritized for managers.'
                                    : responderView
                                      ? 'Ticket response pressure comes first, with only the task work directly relevant to you.'
                                      : 'Assigned work, ticket requests, and immediate deadlines are prioritized for individual staff.'}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {dashboard.tasks.available ? (
                                <Link
                                    href={dashboard.tasks.href}
                                    className="inline-flex h-11 items-center gap-2 rounded-xl bg-gradient-to-r from-red-600 to-orange-500 px-5 text-sm font-semibold text-white shadow-[0_14px_35px_rgba(255,75,0,0.25)] transition hover:from-red-500 hover:to-orange-400"
                                >
                                    <CheckCircle2 className="size-4" />
                                    {dashboard.tasks.can_create ? 'Open task board' : 'View my tasks'}
                                </Link>
                            ) : null}
                            <Link
                                href={dashboard.tickets.href}
                                className="inline-flex h-11 items-center gap-2 rounded-xl border border-border bg-card dark:border-white/[0.12] dark:bg-white/[0.03] px-5 text-sm font-semibold text-foreground dark:text-white transition hover:border-orange-500/50 hover:bg-orange-500/10"
                            >
                                <LifeBuoy className="size-4 text-orange-400" />
                                Open support tickets
                            </Link>
                        </div>
                    </section>

                    {normalizedQuery ? (
                        <div className="flex items-center justify-between rounded-xl border border-orange-500/20 bg-orange-500/[0.08] px-4 py-3 text-sm text-orange-100">
                            <span>
                                Searching for <strong>{query}</strong>. Matching dashboard sections are shown below.
                            </span>
                            <button onClick={() => setQuery('')} className="rounded-lg px-3 py-1 text-orange-200 transition hover:bg-orange-500/15">
                                Clear
                            </button>
                        </div>
                    ) : null}

                    {dashboard.tasks.available ? (
                        <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                            {visibleTaskMetrics.map((metric) => (
                                <MetricCard key={metric.label} metric={metric} />
                            ))}
                        </section>
                    ) : null}

                    {dashboard.tasks.available ? (
                        <section className="grid gap-6 xl:grid-cols-2">
                            {visibleTaskPanels.map((panel) => (
                                <PanelCard key={panel.key} panel={panel} />
                            ))}
                        </section>
                    ) : (
                        <EmptyState message="Your account does not currently expose the work-task board. Support tickets and role-specific workflow panels remain available below." />
                    )}

                    <section className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(24rem,1fr)]">
                        <div className="rounded-2xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-5 shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div className="flex items-start gap-4">
                                    <span className="flex size-10 items-center justify-center rounded-xl bg-orange-500/12 text-orange-400">
                                        <Headphones className="size-6" />
                                    </span>
                                    <div>
                                        <h2 className="text-lg font-semibold text-foreground dark:text-white">Support tickets</h2>
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground dark:text-white/55">
                                            {dashboard.tickets.can_respond
                                                ? 'Incident queue exposure, SLA pressure, and responder workload are prioritized here.'
                                                : 'Support requests you opened or that are currently assigned to you.'}
                                        </p>
                                    </div>
                                </div>
                                <Link
                                    href={dashboard.tickets.href}
                                    className="rounded-xl border border-border dark:border-white/[0.12] px-5 py-2 text-sm font-semibold text-foreground dark:text-white transition hover:border-orange-500/50 hover:bg-orange-500/10"
                                >
                                    View queue
                                </Link>
                            </div>

                            <div className="mt-6">
                                <TicketMetrics dashboard={dashboard} />
                            </div>

                            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                                {visibleTicketPanels.map((panel) => (
                                    <PanelCard key={panel.key} panel={panel} />
                                ))}
                            </div>
                        </div>

                        <aside className="rounded-2xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-5 shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
                            <div className="flex items-start gap-4">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-orange-500/12 text-orange-400">
                                    <Users className="size-6" />
                                </span>
                                <div>
                                    <h2 className="text-lg font-semibold text-foreground dark:text-white">Role-specific panels</h2>
                                    <p className="mt-2 text-xs leading-5 text-muted-foreground dark:text-white/55">
                                        Secondary data only appears when your permissions make it actionable.
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 space-y-3">
                                {visibleRolePanels.length === 0 ? (
                                    <EmptyState message="No additional workflow panels are currently relevant for your permissions." />
                                ) : (
                                    visibleRolePanels.map((widget, index) => (
                                        <RolePanel
                                            key={widget.key}
                                            widget={widget}
                                            Icon={roleIcons[index % roleIcons.length]}
                                            tone={roleTones[index % roleTones.length]}
                                        />
                                    ))
                                )}
                            </div>
                        </aside>
                    </section>

                    {normalizedQuery &&
                    visibleTaskMetrics.length === 0 &&
                    visibleTaskPanels.length === 0 &&
                    visibleTicketPanels.length === 0 &&
                    visibleRolePanels.length === 0 ? (
                        <div className="rounded-2xl border border-dashed border-border dark:border-white/[0.14] p-8 text-center">
                            <XCircle className="mx-auto size-8 text-muted-foreground dark:text-white/45" />
                            <p className="mt-3 font-semibold text-foreground dark:text-white">No dashboard results found</p>
                            <p className="mt-1 text-sm text-muted-foreground dark:text-white/55">Try searching for tasks, tickets, queue, overdue, review, or a panel name.</p>
                        </div>
                    ) : null}
                </div>
            </AppContent>
        </AppShell>
    );
}
