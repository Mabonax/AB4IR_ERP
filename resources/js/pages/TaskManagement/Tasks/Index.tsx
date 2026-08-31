import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    CirclePlay,
    ClipboardList,
    Clock3,
    Flag,
    Headphones,
    ListChecks,
    LoaderCircle,
    MoreHorizontal,
    Plus,
    RotateCcw,
    Search,
    Send,
    SlidersHorizontal,
    UserRound,
} from 'lucide-react';
import { type ComponentType, type SVGProps, useMemo, useRef, useState } from 'react';

import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import FlashMessages from '@/components/flash-messages';
import { StaffAttendancePrompt } from '@/components/staff-attendance-prompt';
import { SidebarTrigger } from '@/components/ui/sidebar';
import tasksRoutes from '@/routes/task-management/tasks';
import ticketsRoutes from '@/routes/task-management/tickets';
import { type SharedData } from '@/types';

type IconType = ComponentType<SVGProps<SVGSVGElement>>;

type TaskStatus = 'open' | 'in_progress' | 'blocked' | 'pending_review' | 'changes_requested' | 'completed' | 'cancelled';

type TaskRow = {
    id: number;
    title: string;
    description: string | null;
    status: TaskStatus;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    due_date: string | null;
    context_type: string;
    project_name: string | null;
    program_title: string | null;
    creator_name: string | null;
    assignee_name: string | null;
    assigned_department_name: string | null;
    submitted_for_review_at: string | null;
    manager_review_notes: string | null;
    completed_at: string | null;
    transaction_state: 'open' | 'closed';
    transaction_closed_at: string | null;
    closed_by_name: string | null;
};

type Summary = {
    total: number;
    open: number;
    in_progress: number;
    pending_review: number;
    changes_requested: number;
    completed: number;
    overdue: number;
};

type SelectOption = {
    value: string;
    label: string;
};

type Metric = {
    label: string;
    value: number;
    Icon: IconType;
    tone: string;
};

const fieldClass =
    'h-11 w-full rounded-lg border border-border bg-card dark:border-white/[0.09] dark:bg-white/[0.035] px-4 text-sm text-foreground outline-none dark:text-white transition placeholder:text-muted-foreground dark:placeholder:text-white/42 focus:border-orange-400/60 focus:ring-4 focus:ring-orange-500/10';

const selectClass =
    'h-11 w-full rounded-lg border border-border bg-card dark:border-white/[0.09] dark:bg-[#111820] px-4 text-sm text-foreground outline-none dark:text-white transition focus:border-orange-400/60 focus:ring-4 focus:ring-orange-500/10';

const labelClass = 'mb-2 block text-sm font-medium text-foreground dark:text-white';

const statusLabels: Record<TaskStatus, string> = {
    open: 'Open',
    in_progress: 'In Progress',
    blocked: 'Blocked',
    pending_review: 'Awaiting Review',
    changes_requested: 'Returned',
    completed: 'Completed',
    cancelled: 'Cancelled',
};

const statusBadgeClass = (status: TaskStatus) => {
    switch (status) {
        case 'completed':
            return 'border-emerald-400/25 bg-emerald-500/10 text-emerald-300';
        case 'pending_review':
            return 'border-amber-400/25 bg-amber-500/10 text-amber-300';
        case 'changes_requested':
            return 'border-orange-400/25 bg-orange-500/10 text-orange-300';
        case 'blocked':
            return 'border-red-400/25 bg-red-500/10 text-red-300';
        case 'cancelled':
            return 'border-border bg-muted dark:border-white/[0.12] dark:bg-white/[0.06] text-muted-foreground dark:text-white/62';
        case 'in_progress':
            return 'border-blue-400/25 bg-blue-500/10 text-blue-300';
        default:
            return 'border-green-400/25 bg-green-500/10 text-green-300';
    }
};

const priorityBadgeClass = (priority: TaskRow['priority']) => {
    switch (priority) {
        case 'urgent':
            return 'border-red-400/25 bg-red-500/10 text-red-300';
        case 'high':
            return 'border-orange-400/25 bg-orange-500/10 text-orange-300';
        case 'low':
            return 'border-blue-400/25 bg-blue-500/10 text-blue-300';
        default:
            return 'border-amber-400/25 bg-amber-500/10 text-amber-300';
    }
};

const formatDate = (date: string | null) =>
    date
        ? new Intl.DateTimeFormat('en', {
              year: 'numeric',
              month: 'short',
              day: '2-digit',
          }).format(new Date(date))
        : '-';

function TaskTopbar() {
    const { auth } = usePage<SharedData>().props;
    const user = auth?.user;

    return (
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-background/95 dark:border-white/[0.08] dark:bg-[#0a1017]/95 px-6 backdrop-blur">
            <div className="flex min-w-0 items-center gap-4">
                <SidebarTrigger className="size-9 rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.04] text-orange-400 hover:bg-orange-500/10 hover:text-orange-300" />
                <nav className="flex min-w-0 items-center gap-3 text-sm">
                    <span className="flex size-8 items-center justify-center rounded-lg text-muted-foreground dark:text-white/78">
                        <ListChecks className="size-4" />
                    </span>
                    <span className="font-medium text-muted-foreground dark:text-white/78">Task Management</span>
                    <ChevronDown className="-rotate-90 size-4 text-muted-foreground dark:text-white/35" />
                    <span className="font-semibold text-foreground dark:text-white">Tasks</span>
                </nav>
            </div>
            <div className="hidden items-center gap-3 lg:flex">
                <div className="text-right">
                    <p className="text-sm font-semibold text-foreground dark:text-white">{user?.name ?? 'Local Super Admin'}</p>
                    <p className="text-xs text-muted-foreground dark:text-white/55">{user?.roles?.[0] ?? 'Super Administrator'}</p>
                </div>
            </div>
        </header>
    );
}

function MetricCard({ metric }: { metric: Metric }) {
    const Icon = metric.Icon;

    return (
        <div className="rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.04] p-5 shadow-[0_18px_55px_rgba(0,0,0,0.2)]">
            <div className="flex items-center gap-4">
                <span className={`flex size-11 shrink-0 items-center justify-center rounded-full ${metric.tone}`}>
                    <Icon className="size-5" />
                </span>
                <div className="min-w-0">
                    <p className="text-sm text-muted-foreground dark:text-white/72">{metric.label}</p>
                    <p className="mt-1 text-3xl font-semibold leading-none text-foreground dark:text-white">{metric.value}</p>
                </div>
            </div>
        </div>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? <p className="mt-1.5 text-sm text-red-300">{message}</p> : null;
}

function NativeSelect({
    value,
    onChange,
    options,
    ariaLabel,
}: {
    value: string;
    onChange: (value: string) => void;
    options: SelectOption[];
    ariaLabel: string;
}) {
    return (
        <select value={value} onChange={(event) => onChange(event.currentTarget.value)} className={selectClass} aria-label={ariaLabel}>
            {options.map((option) => (
                <option key={`${ariaLabel}-${option.value}`} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

function EmptyTasks({ onCreateFocus }: { onCreateFocus: () => void }) {
    return (
        <div className="flex min-h-[14rem] flex-col items-center justify-center border-t border-border dark:border-white/[0.08] px-6 py-10 text-center">
            <span className="flex size-18 items-center justify-center rounded-full border border-border bg-muted dark:border-white/[0.12] dark:bg-white/[0.04] text-muted-foreground dark:text-white/65">
                <ClipboardList className="size-9" />
            </span>
            <h3 className="mt-4 font-semibold text-foreground dark:text-white">No tasks available</h3>
            <p className="mt-1 text-sm text-muted-foreground dark:text-white/55">Create your first task to get started.</p>
            <button
                type="button"
                onClick={onCreateFocus}
                className="mt-4 inline-flex h-10 items-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-orange-500 px-4 text-sm font-semibold text-white transition hover:from-red-500 hover:to-orange-400"
            >
                <Plus className="size-4" />
                Create Task
            </button>
        </div>
    );
}

export default function TaskManagementTasksIndex({
    tasks,
    assignees,
    departments,
    projects,
    programs,
    filters,
    summary,
    can,
}: {
    tasks: { data: TaskRow[] };
    assignees: Array<{ id: number; name: string; email: string }>;
    departments: Array<{ id: number; name: string }>;
    projects: Array<{ id: number; name: string }>;
    programs: Array<{ id: number; title: string }>;
    filters: Record<string, string>;
    summary: Summary;
    can: { create: boolean };
}) {
    const [showMoreFilters, setShowMoreFilters] = useState(false);
    const titleRef = useRef<HTMLInputElement>(null);
    const createSectionRef = useRef<HTMLElement>(null);

    const createForm = useForm({
        title: '',
        description: '',
        priority: 'medium',
        due_date: '',
        project_id: '',
        program_id: '',
        assigned_to_user_id: '',
        assigned_department_id: '',
    });
    const filterForm = useForm({
        search: filters.search ?? '',
        status: filters.status ?? '',
        priority: filters.priority ?? '',
        department_id: filters.department_id ?? '',
        project_id: filters.project_id ?? '',
        program_id: filters.program_id ?? '',
        assignee_user_id: filters.assignee_user_id ?? '',
        overdue: filters.overdue ?? '',
    });

    const assigneeOptions = useMemo<SelectOption[]>(
        () => [
            { value: '', label: 'Select user' },
            ...assignees.map((assignee) => ({
                value: String(assignee.id),
                label: `${assignee.name} | ${assignee.email}`,
            })),
        ],
        [assignees],
    );
    const departmentOptions = useMemo<SelectOption[]>(
        () => [{ value: '', label: 'Select department' }, ...departments.map((department) => ({ value: String(department.id), label: department.name }))],
        [departments],
    );
    const projectOptions = useMemo<SelectOption[]>(
        () => [{ value: '', label: 'Select project' }, ...projects.map((project) => ({ value: String(project.id), label: project.name }))],
        [projects],
    );
    const programOptions = useMemo<SelectOption[]>(
        () => [{ value: '', label: 'Select program' }, ...programs.map((program) => ({ value: String(program.id), label: program.title }))],
        [programs],
    );

    const focusCreateTask = () => {
        createSectionRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => titleRef.current?.focus(), 250);
    };

    const applyFilters = () => {
        router.get(tasksRoutes.index.url(), filterForm.data, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        router.get(tasksRoutes.index.url(), {}, { preserveState: false, preserveScroll: true });
    };

    const metrics: Metric[] = [
        { label: 'Visible Tasks', value: summary.total, Icon: ListChecks, tone: 'bg-muted dark:bg-white/[0.08] text-muted-foreground dark:text-white/72' },
        { label: 'Open', value: summary.open, Icon: CirclePlay, tone: 'bg-green-500/12 text-green-400' },
        { label: 'In Progress', value: summary.in_progress, Icon: LoaderCircle, tone: 'bg-blue-500/12 text-blue-400' },
        { label: 'Awaiting Review', value: summary.pending_review, Icon: Clock3, tone: 'bg-amber-500/12 text-amber-400' },
        { label: 'Returned', value: summary.changes_requested, Icon: RotateCcw, tone: 'bg-red-500/12 text-red-400' },
        { label: 'Completed', value: summary.completed, Icon: CheckCircle2, tone: 'bg-emerald-500/12 text-emerald-400' },
        { label: 'Overdue', value: summary.overdue, Icon: AlertTriangle, tone: 'bg-red-500/12 text-red-400' },
    ];

    return (
        <AppShell variant="sidebar">
            <Head title="Task Management" />
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden bg-background text-foreground dark:bg-[#0a1017] dark:text-white">
                <TaskTopbar />
                <FlashMessages />
                <StaffAttendancePrompt />

                <main className="mx-auto w-full max-w-[100rem] space-y-6 px-6 py-7">
                    <section className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-semibold tracking-tight text-foreground dark:text-white">Task Management</h1>
                            <p className="mt-2 text-sm text-muted-foreground dark:text-white/62">
                                Create tasks, assign to team members, and track progress across departments and projects.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <Link
                                href={ticketsRoutes.index.url()}
                                className="inline-flex h-11 items-center gap-2 rounded-lg border border-orange-500/70 bg-card dark:bg-white/[0.02] px-5 text-sm font-semibold text-foreground dark:text-white transition hover:bg-orange-500/10"
                            >
                                <Headphones className="size-4 text-orange-400" />
                                Support Tickets
                            </Link>
                            {can.create ? (
                                <button
                                    type="button"
                                    onClick={focusCreateTask}
                                    className="inline-flex h-11 items-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-orange-500 px-5 text-sm font-semibold text-white shadow-[0_14px_35px_rgba(255,75,0,0.22)] transition hover:from-red-500 hover:to-orange-400"
                                >
                                    <Plus className="size-4" />
                                    Create Task
                                </button>
                            ) : null}
                        </div>
                    </section>

                    <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-7">
                        {metrics.map((metric) => (
                            <MetricCard key={metric.label} metric={metric} />
                        ))}
                    </section>

                    {can.create ? (
                        <section
                            ref={createSectionRef}
                            className="rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] p-6 shadow-[0_24px_70px_rgba(0,0,0,0.24)]"
                        >
                            <h2 className="text-xl font-semibold text-foreground dark:text-white">Create Task</h2>
                            <p className="mt-2 text-sm text-muted-foreground dark:text-white/55">Fill in the task details below to create a new task.</p>
                            <form
                                className="mt-5 grid gap-4 lg:grid-cols-12"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    createForm.post(tasksRoutes.store.url(), {
                                        preserveScroll: true,
                                        onSuccess: () => createForm.reset(),
                                    });
                                }}
                            >
                                <div className="lg:col-span-6">
                                    <label htmlFor="task-title" className={labelClass}>
                                        Title <span className="text-red-400">*</span>
                                    </label>
                                    <input
                                        id="task-title"
                                        ref={titleRef}
                                        value={createForm.data.title}
                                        onChange={(event) => createForm.setData('title', event.currentTarget.value)}
                                        placeholder="Enter task title"
                                        className={fieldClass}
                                    />
                                    <FieldError message={createForm.errors.title} />
                                </div>
                                <div className="lg:col-span-3">
                                    <label className={labelClass}>
                                        Priority <span className="text-red-400">*</span>
                                    </label>
                                    <div className="relative">
                                        <Flag className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-amber-400" />
                                        <select
                                            value={createForm.data.priority}
                                            onChange={(event) => createForm.setData('priority', event.currentTarget.value as typeof createForm.data.priority)}
                                            className={`${selectClass} pl-11`}
                                        >
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                    <FieldError message={createForm.errors.priority} />
                                </div>
                                <div className="lg:col-span-3">
                                    <label htmlFor="task-due-date" className={labelClass}>
                                        Due Date <span className="text-red-400">*</span>
                                    </label>
                                    <input
                                        id="task-due-date"
                                        type="date"
                                        value={createForm.data.due_date}
                                        onChange={(event) => createForm.setData('due_date', event.currentTarget.value)}
                                        className={fieldClass}
                                    />
                                    <FieldError message={createForm.errors.due_date} />
                                </div>
                                <div className="lg:col-span-12">
                                    <label htmlFor="task-description" className={labelClass}>
                                        Description
                                    </label>
                                    <textarea
                                        id="task-description"
                                        value={createForm.data.description}
                                        onChange={(event) => createForm.setData('description', event.currentTarget.value)}
                                        rows={3}
                                        placeholder="Enter task description"
                                        className={`${fieldClass} h-auto min-h-20 py-3`}
                                    />
                                    <FieldError message={createForm.errors.description} />
                                </div>
                                <div className="lg:col-span-6">
                                    <label className={labelClass}>
                                        Assign To User <span className="text-red-400">*</span>
                                    </label>
                                    <NativeSelect
                                        value={String(createForm.data.assigned_to_user_id)}
                                        onChange={(value) => createForm.setData('assigned_to_user_id', value)}
                                        options={assigneeOptions}
                                        ariaLabel="Assign to user"
                                    />
                                    <FieldError message={createForm.errors.assigned_to_user_id} />
                                </div>
                                <div className="lg:col-span-6">
                                    <label className={labelClass}>
                                        Assign To Department <span className="text-red-400">*</span>
                                    </label>
                                    <NativeSelect
                                        value={String(createForm.data.assigned_department_id)}
                                        onChange={(value) => createForm.setData('assigned_department_id', value)}
                                        options={departmentOptions}
                                        ariaLabel="Assign to department"
                                    />
                                    <FieldError message={createForm.errors.assigned_department_id} />
                                </div>
                                <div className="lg:col-span-6">
                                    <label className={labelClass}>Related Project</label>
                                    <NativeSelect
                                        value={String(createForm.data.project_id)}
                                        onChange={(value) => createForm.setData('project_id', value)}
                                        options={projectOptions}
                                        ariaLabel="Related project"
                                    />
                                    <FieldError message={createForm.errors.project_id} />
                                </div>
                                <div className="lg:col-span-6">
                                    <label className={labelClass}>Related Program</label>
                                    <NativeSelect
                                        value={String(createForm.data.program_id)}
                                        onChange={(value) => createForm.setData('program_id', value)}
                                        options={programOptions}
                                        ariaLabel="Related program"
                                    />
                                    <FieldError message={createForm.errors.program_id} />
                                </div>
                                <div className="lg:col-span-12">
                                    <button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="inline-flex h-11 items-center gap-2 rounded-lg bg-gradient-to-r from-red-600 to-orange-500 px-5 text-sm font-semibold text-white shadow-[0_14px_35px_rgba(255,75,0,0.18)] transition hover:from-red-500 hover:to-orange-400 disabled:cursor-not-allowed disabled:opacity-55"
                                    >
                                        {createForm.processing ? 'Creating...' : 'Create Task'}
                                        <Send className="size-4" />
                                    </button>
                                </div>
                            </form>
                        </section>
                    ) : null}

                    <section className="overflow-hidden rounded-xl border border-border bg-card dark:border-white/[0.08] dark:bg-white/[0.035] shadow-[0_24px_70px_rgba(0,0,0,0.24)]">
                        <div className="p-5 pb-4">
                            <div className="flex items-center gap-2">
                                <h2 className="font-semibold text-foreground dark:text-white">Task Filters</h2>
                                <ChevronDown className="size-4 text-muted-foreground dark:text-white/60" />
                            </div>
                            <form
                                className="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-[minmax(13rem,1.2fr)_repeat(4,minmax(10rem,1fr))_auto_auto_auto]"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    applyFilters();
                                }}
                            >
                                <label className="relative">
                                    <span className="sr-only">Search tasks</span>
                                    <Search className="pointer-events-none absolute top-1/2 right-4 size-4 -translate-y-1/2 text-muted-foreground dark:text-white/55" />
                                    <input
                                        value={filterForm.data.search}
                                        onChange={(event) => filterForm.setData('search', event.currentTarget.value)}
                                        placeholder="Search tasks..."
                                        className={`${fieldClass} pr-11`}
                                        type="search"
                                    />
                                </label>
                                <NativeSelect
                                    value={filterForm.data.status}
                                    onChange={(value) => filterForm.setData('status', value)}
                                    ariaLabel="Filter status"
                                    options={[
                                        { value: '', label: 'All statuses' },
                                        { value: 'open', label: 'Open' },
                                        { value: 'in_progress', label: 'In Progress' },
                                        { value: 'blocked', label: 'Blocked' },
                                        { value: 'pending_review', label: 'Awaiting Review' },
                                        { value: 'changes_requested', label: 'Returned' },
                                        { value: 'completed', label: 'Completed' },
                                        { value: 'cancelled', label: 'Cancelled' },
                                    ]}
                                />
                                <NativeSelect
                                    value={filterForm.data.priority}
                                    onChange={(value) => filterForm.setData('priority', value)}
                                    ariaLabel="Filter priority"
                                    options={[
                                        { value: '', label: 'All priorities' },
                                        { value: 'low', label: 'Low' },
                                        { value: 'medium', label: 'Medium' },
                                        { value: 'high', label: 'High' },
                                        { value: 'urgent', label: 'Urgent' },
                                    ]}
                                />
                                <NativeSelect
                                    value={filterForm.data.assignee_user_id}
                                    onChange={(value) => filterForm.setData('assignee_user_id', value)}
                                    ariaLabel="Filter assignee"
                                    options={[{ value: '', label: 'All assignees' }, ...assignees.map((assignee) => ({ value: String(assignee.id), label: assignee.name }))]}
                                />
                                <NativeSelect
                                    value={filterForm.data.department_id}
                                    onChange={(value) => filterForm.setData('department_id', value)}
                                    ariaLabel="Filter department"
                                    options={[{ value: '', label: 'All departments' }, ...departments.map((department) => ({ value: String(department.id), label: department.name }))]}
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowMoreFilters((current) => !current)}
                                    className="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-border dark:border-white/[0.12] px-4 text-sm font-semibold text-foreground dark:text-white transition hover:border-orange-500/50 hover:bg-orange-500/10"
                                >
                                    <SlidersHorizontal className="size-4" />
                                    More filters
                                </button>
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    className="inline-flex h-11 items-center justify-center rounded-lg border border-border dark:border-white/[0.12] px-5 text-sm font-semibold text-foreground dark:text-white transition hover:bg-muted dark:hover:bg-white/[0.06]"
                                >
                                    Reset
                                </button>
                                <button
                                    type="submit"
                                    disabled={filterForm.processing}
                                    className="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-to-r from-red-600 to-orange-500 px-5 text-sm font-semibold text-white transition hover:from-red-500 hover:to-orange-400 disabled:cursor-not-allowed disabled:opacity-55"
                                >
                                    Apply Filters
                                </button>
                                {showMoreFilters ? (
                                    <div className="grid gap-3 md:col-span-2 xl:col-span-4 xl:grid-cols-3 2xl:col-span-8">
                                        <NativeSelect
                                            value={filterForm.data.project_id}
                                            onChange={(value) => filterForm.setData('project_id', value)}
                                            ariaLabel="Filter project"
                                            options={[{ value: '', label: 'All projects' }, ...projects.map((project) => ({ value: String(project.id), label: project.name }))]}
                                        />
                                        <NativeSelect
                                            value={filterForm.data.program_id}
                                            onChange={(value) => filterForm.setData('program_id', value)}
                                            ariaLabel="Filter program"
                                            options={[{ value: '', label: 'All programs' }, ...programs.map((program) => ({ value: String(program.id), label: program.title }))]}
                                        />
                                        <NativeSelect
                                            value={filterForm.data.overdue}
                                            onChange={(value) => filterForm.setData('overdue', value)}
                                            ariaLabel="Filter due state"
                                            options={[
                                                { value: '', label: 'All due states' },
                                                { value: '1', label: 'Overdue only' },
                                            ]}
                                        />
                                    </div>
                                ) : null}
                            </form>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-[76rem] w-full border-t border-border dark:border-white/[0.08] text-left">
                                <thead className="bg-muted dark:bg-black/15 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground dark:text-white/62">
                                    <tr>
                                        <th className="w-12 px-4 py-4">
                                            <input type="checkbox" className="size-4 rounded border-border bg-transparent dark:border-white/[0.2]" aria-label="Select all tasks" />
                                        </th>
                                        <th className="px-4 py-4">Task</th>
                                        <th className="px-4 py-4">Priority</th>
                                        <th className="px-4 py-4">Assignee</th>
                                        <th className="px-4 py-4">Department</th>
                                        <th className="px-4 py-4">Due Date</th>
                                        <th className="px-4 py-4">Status</th>
                                        <th className="px-4 py-4">Related To</th>
                                        <th className="px-4 py-4">Created</th>
                                        <th className="px-4 py-4">Actions</th>
                                    </tr>
                                </thead>
                                {tasks.data.length > 0 ? (
                                    <tbody className="divide-y divide-border dark:divide-white/[0.06] text-sm text-muted-foreground dark:text-white/76">
                                        {tasks.data.map((task) => (
                                            <tr key={task.id} className="transition hover:bg-muted/60 dark:hover:bg-white/[0.035]">
                                                <td className="px-4 py-4">
                                                    <input type="checkbox" className="size-4 rounded border-border bg-transparent dark:border-white/[0.2]" aria-label={`Select ${task.title}`} />
                                                </td>
                                                <td className="max-w-[22rem] px-4 py-4">
                                                    <p className="font-semibold text-foreground dark:text-white">{task.title}</p>
                                                    {task.description ? <p className="mt-1 line-clamp-1 text-xs text-muted-foreground dark:text-white/48">{task.description}</p> : null}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold uppercase ${priorityBadgeClass(task.priority)}`}>
                                                        {task.priority}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <span className="inline-flex items-center gap-2">
                                                        <UserRound className="size-4 text-muted-foreground dark:text-white/45" />
                                                        {task.assignee_name ?? 'Queue'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">{task.assigned_department_name ?? '-'}</td>
                                                <td className="px-4 py-4">
                                                    <span className="inline-flex items-center gap-2">
                                                        <CalendarDays className="size-4 text-muted-foreground dark:text-white/45" />
                                                        {formatDate(task.due_date)}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <span className={`rounded-full border px-2.5 py-1 text-xs font-semibold ${statusBadgeClass(task.status)}`}>
                                                        {statusLabels[task.status]}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-4">{task.project_name ?? task.program_title ?? task.context_type ?? '-'}</td>
                                                <td className="px-4 py-4">{task.creator_name ?? '-'}</td>
                                                <td className="px-4 py-4">
                                                    <button
                                                        type="button"
                                                        onClick={() => router.visit(tasksRoutes.show.url(task.id))}
                                                        className="inline-flex items-center gap-2 rounded-lg border border-border dark:border-white/[0.12] px-3 py-2 text-xs font-semibold text-foreground dark:text-white transition hover:border-orange-500/50 hover:bg-orange-500/10"
                                                    >
                                                        Open
                                                        <MoreHorizontal className="size-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                ) : null}
                            </table>
                            {tasks.data.length === 0 ? <EmptyTasks onCreateFocus={focusCreateTask} /> : null}
                        </div>
                    </section>
                </main>
            </AppContent>
        </AppShell>
    );
}
