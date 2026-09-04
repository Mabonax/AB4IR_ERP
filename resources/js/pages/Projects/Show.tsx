import { Head, Link, router } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    CalendarCheck2,
    CheckCircle2,
    ClipboardCheck,
    Flag,
    MapPin,
    Plus,
    UsersRound,
} from 'lucide-react';
import { useState } from 'react';

import {
    ComparisonBarsChart,
    HorizontalBarChart,
    LineTrendChart,
    StackedCompositionChart,
} from '@/components/charts/dashboard-charts';
import { DomainNav } from '@/components/domain-nav';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { projectNavItems } from '@/config/domain-nav/projects';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Project View', href: '#' },
];

const readinessTone = (ready: boolean) =>
    ready
        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
        : 'border-amber-200 bg-amber-50 text-amber-700';

type LearningActionItem = {
    status?: string | null;
};

type LearningActionResponse = {
    offering?: { name?: string | null } | null;
    items?: LearningActionItem[];
    reason?: string | null;
    status?: string | null;
    message?: string | null;
};

type ProjectData = {
    id: number;
    name: string;
    program_id?: number | null;
    status?: string | null;
    status_label?: string | null;
    status_summary?: StatusSummary | null;
    start_date?: string | null;
    project_manager_name?: string | null;
    sponsor_name?: string | null;
    partner_names?: string[];
    contract_reference?: string | null;
    funding_amount?: number | string | null;
    reporting_cadence?: string | null;
    reporting_obligations?: string | null;
};

type ProjectPayload = ProjectData | { data: ProjectData };

type ProjectMilestoneRow = {
    id: number;
    title: string;
    max_score?: number | null;
};

type ProjectProgressSummary = {
    project_manager_name?: string | null;
    total_locations?: number;
    total_milestones?: number;
    required_milestones?: number;
    active_beneficiaries?: number;
    total_beneficiaries?: number;
    completed_beneficiaries?: number;
    dropped_beneficiaries?: number;
    expected_assessments?: number;
    assessed_assessments?: number;
    completed_assessments?: number;
    unassessed_assessments?: number;
    milestone_completion_rate?: number;
    assessment_coverage_rate?: number;
    pass_rate?: number;
    failed_rate?: number;
    beneficiary_completion_rate?: number;
    attendance_rate?: number;
    blocked_locations?: number;
    blockers?: string[];
};

type ProjectProgress = {
    summary?: ProjectProgressSummary;
};

type ProjectLocationProgressRow = {
    id: number;
    location?: string | null;
    facilitator_name?: string | null;
    training_venue_address?: string | null;
    active_beneficiaries?: number;
    milestone_completion_rate?: number;
    beneficiary_completion_rate?: number;
    attendance_rate?: number;
    is_blocked?: boolean;
    blockers?: string[];
    completed_assessments?: number;
    expected_assessments?: number;
};

type StatusTransition = {
    status: string;
    label: string;
    ready: boolean;
    blockers: string[];
};

type StatusSummary = {
    allowed_transitions?: StatusTransition[];
    readiness?: Record<string, { ready: boolean; blockers: string[] }>;
};

type LearningMetrics = {
    mapped_offerings?: number | null;
    lms_learners?: number | null;
    lms_facilitators?: number | null;
    certificates_issued?: number | null;
    average_progress?: number | null;
    average_attendance?: number | null;
    active_learners?: number | null;
    active_teaching_assignments?: number | null;
    active?: number | null;
    enrolled?: number | null;
    cohort_enrollment_pending?: number | null;
    invitation_pending?: number | null;
    invitation_expired?: number | null;
    not_provisioned?: number | null;
};

type LearningSummary = {
    integration_state?: string | null;
    metrics?: LearningMetrics | null;
    message?: string | null;
    reason?: string | null;
    mappings?: {
        id: number;
        status: string;
        lms_offering_id: number | string;
        offering?: LearningOffering | null;
    }[];
};

type LearningOffering = {
    id: number | string;
    name: string;
    display_name?: string | null;
    status?: string | null;
    programme?: { name?: string | null } | null;
    courses?: { id: number | string; title: string }[];
};

type LearnerProvisioningItem = {
    erp_beneficiary_id: number;
    name: string;
    eligible: boolean;
    reason?: string | null;
    lms_status?: string | null;
};

type FacilitatorProvisioningItem = {
    erp_facilitator_id: number;
    name: string;
    eligible: boolean;
    reason?: string | null;
    lms_status?: string | null;
};

type LearningDelivery = {
    summary?: LearningSummary | null;
    availableOfferings?: LearningOffering[];
    learnerProvisioning?: {
        items?: LearnerProvisioningItem[];
        metrics?: LearningMetrics | null;
    } | null;
    facilitatorProvisioning?: {
        items?: FacilitatorProvisioningItem[];
        metrics?: LearningMetrics | null;
    } | null;
};

type HistoryItem = {
    id: number;
    summary: string;
    action: string;
    actor_name?: string | null;
    created_at?: string | null;
};

type MetricCard = [string, string | number, LucideIcon, string];

const learningActionMessage = (data: LearningActionResponse) => {
    if (data?.offering?.name) {
        return `Mapped to LMS offering: ${data.offering.name}.`;
    }

    if (Array.isArray(data?.items)) {
        const counts = data.items.reduce(
            (summary: Record<string, number>, item: LearningActionItem) => {
                const status = String(item.status ?? 'unknown').replaceAll(
                    '_',
                    ' ',
                );
                summary[status] = (summary[status] ?? 0) + 1;
                return summary;
            },
            {},
        );
        const rendered = Object.entries(counts)
            .map(([status, count]) => `${count} ${status}`)
            .join(', ');

        return rendered
            ? `LMS provisioning processed: ${rendered}.`
            : 'LMS provisioning completed with no changed records.';
    }

    return data?.reason || data?.status || 'Learning action completed.';
};

type LearningActionStatus = {
    message: string;
    phase: string;
    progress: number;
    type: 'idle' | 'running' | 'success' | 'error';
};

export default function ProjectShow({
    project,
    milestones,
    progress,
    locations,
    attendanceTrend,
    history,
    canManageProjects,
    canAttachMilestones,
    milestoneAttachment,
    finalization,
    documentRepository,
    brochureRepository,
    learningDelivery,
}: {
    project: ProjectPayload;
    milestones: ProjectMilestoneRow[];
    progress: ProjectProgress | null;
    locations: ProjectLocationProgressRow[];
    attendanceTrend: { date: string; attendance_rate: number }[];
    history: HistoryItem[];
    canManageProjects: boolean;
    canAttachMilestones: boolean;
    milestoneAttachment: {
        active_program_templates: number;
        attached_milestones: number;
        attached_program_templates: number;
        missing_program_templates: number;
        manage_templates_href: string;
    };
    finalization: {
        href: string;
        is_concluded: boolean;
        closure_date: string | null;
        evidence_count: number;
        report_count: number;
        can_manage: boolean;
    };
    documentRepository: { folder_id: number; href: string } | null;
    brochureRepository: {
        folder_id: number;
        href: string;
        upload_url: string;
        can_publish_to_vault: boolean;
    } | null;
    learningDelivery: LearningDelivery | null;
}) {
    const projectData = 'data' in project ? project.data : project;
    const statusSummary = projectData.status_summary;
    const summary = progress?.summary ?? {};
    const learningSummary = learningDelivery?.summary ?? {};
    const learnerItems = learningDelivery?.learnerProvisioning?.items ?? [];
    const facilitatorItems =
        learningDelivery?.facilitatorProvisioning?.items ?? [];
    const [selectedOfferingId, setSelectedOfferingId] = useState('');
    const [learningStatus, setLearningStatus] =
        useState<LearningActionStatus | null>(null);
    const [learningBusy, setLearningBusy] = useState(false);
    const [brochureVaultBusy, setBrochureVaultBusy] = useState(false);
    const [brochureUploadProgress, setBrochureUploadProgress] = useState<
        number | null
    >(null);

    const handleSyncMilestones = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(`/projects/${projectData.id}/milestones/sync`, {});
    };

    const handleBrochureVaultUpload = (
        event: React.FormEvent<HTMLFormElement>,
    ) => {
        event.preventDefault();

        if (!brochureRepository?.upload_url) {
            return;
        }

        const form = event.currentTarget;
        setBrochureVaultBusy(true);
        setBrochureUploadProgress(0);
        router.post(brochureRepository.upload_url, new FormData(form), {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress) =>
                setBrochureUploadProgress(progress?.percentage ?? 0),
            onFinish: () => {
                setBrochureVaultBusy(false);
                setBrochureUploadProgress(null);
            },
            onSuccess: () => form.reset(),
        });
    };

    const csrfToken =
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    const postLearningAction = async (
        url: string,
        payload: Record<string, unknown>,
    ) => {
        setLearningBusy(true);
        setLearningStatus({
            message: 'Preparing learning delivery request...',
            phase: 'Preparing',
            progress: 15,
            type: 'running',
        });

        try {
            setLearningStatus({
                message: 'Sending request to ERP learning delivery service...',
                phase: 'ERP request',
                progress: 35,
                type: 'running',
            });

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            setLearningStatus({
                message: 'ERP is waiting for the LMS bridge response...',
                phase: 'LMS bridge',
                progress: 70,
                type: 'running',
            });

            const data = (await response.json()) as LearningActionResponse;

            if (!response.ok) {
                throw new Error(
                    data.reason || data.message || 'Learning action failed.',
                );
            }

            setLearningStatus({
                message: learningActionMessage(data),
                phase: 'Completed',
                progress: 100,
                type: 'success',
            });
            router.reload({ only: ['learningDelivery', 'history'] });
        } catch (error) {
            const message =
                error instanceof Error
                    ? error.message
                    : 'Learning action failed.';

            setLearningStatus({
                message:
                    message === 'LMS could not be reached.'
                        ? 'LMS could not be reached. Confirm the LMS server is running on port 8016, then retry.'
                        : message,
                phase: 'Failed',
                progress: 100,
                type: 'error',
            });
        } finally {
            setLearningBusy(false);
        }
    };

    const eligibleBeneficiaryIds = learnerItems
        .filter((item) => item.eligible)
        .map((item) => item.erp_beneficiary_id);
    const eligibleFacilitatorIds = facilitatorItems
        .filter((item) => item.eligible)
        .map((item) => item.erp_facilitator_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project View" />

            <div className="space-y-6 bg-white p-4 text-slate-950 md:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p className="text-sm text-slate-500">
                            Projects / Project Dashboard
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-normal">
                            {projectData.name}
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Delivery governance, learner progress, and project
                            operations.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        {canAttachMilestones ? (
                            <form onSubmit={handleSyncMilestones}>
                                <button
                                    type="submit"
                                    className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700"
                                >
                                    <Plus className="h-4 w-4" />
                                    Add New Milestone
                                </button>
                            </form>
                        ) : null}
                        {canManageProjects ? (
                            <>
                                <Link
                                    href={finalization.href}
                                    className="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                                >
                                    Project Finalization
                                </Link>
                                <Link
                                    href={`/projects/${projectData.id}/edit`}
                                    className="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                                >
                                    Edit Project
                                </Link>
                            </>
                        ) : finalization.can_manage ? (
                            <Link
                                href={finalization.href}
                                className="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                            >
                                Project Finalization
                            </Link>
                        ) : null}
                        <DomainNav items={projectNavItems} />
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                    <Link
                        href={`/projects/${projectData.id}`}
                        className="rounded-md bg-white px-4 py-2 text-sm font-medium text-slate-900 shadow-sm"
                    >
                        Overview
                    </Link>
                    <Link
                        href={finalization.href}
                        className="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900"
                    >
                        Finalization
                    </Link>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {(
                        [
                            [
                                'Status',
                                projectData.status_label ??
                                    projectData.status ??
                                    '-',
                                Flag,
                                'bg-red-50 text-red-600',
                            ],
                            [
                                'Start Date',
                                projectData.start_date ?? '-',
                                CalendarCheck2,
                                'bg-orange-50 text-orange-600',
                            ],
                            [
                                'Locations',
                                summary.total_locations ?? locations.length,
                                MapPin,
                                'bg-blue-50 text-blue-600',
                            ],
                            [
                                'Milestones',
                                summary.total_milestones ?? milestones.length,
                                CheckCircle2,
                                'bg-emerald-50 text-emerald-600',
                            ],
                            [
                                'Active Beneficiaries',
                                summary.active_beneficiaries ?? 0,
                                UsersRound,
                                'bg-violet-50 text-violet-600',
                            ],
                        ] satisfies MetricCard[]
                    ).map(([label, value, Icon, tone]) => (
                        <section
                            key={String(label)}
                            className="rounded-lg border bg-white p-5 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-sm font-medium text-slate-500">
                                        {label}
                                    </p>
                                    <p className="mt-2 truncate text-2xl font-semibold capitalize">
                                        {String(value)}
                                    </p>
                                </div>
                                <span
                                    className={`inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full ${tone}`}
                                >
                                    <Icon className="h-5 w-5" />
                                </span>
                            </div>
                        </section>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle>Milestone Delivery</CardTitle>
                                <CardDescription>
                                    Program templates, project milestone
                                    snapshots, and beneficiary assessment
                                    coverage
                                </CardDescription>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Link
                                    href={
                                        milestoneAttachment.manage_templates_href
                                    }
                                    className="rounded-md border px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    Manage Program Milestones
                                </Link>
                                {canAttachMilestones &&
                                milestoneAttachment.active_program_templates >
                                    0 ? (
                                    <form onSubmit={handleSyncMilestones}>
                                        <button
                                            type="submit"
                                            className="rounded-md bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700"
                                        >
                                            {milestoneAttachment.attached_milestones >
                                            0
                                                ? 'Sync Missing Milestones'
                                                : 'Attach Program Milestones'}
                                        </button>
                                    </form>
                                ) : null}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {milestoneAttachment.active_program_templates === 0 ? (
                            <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                                No program milestones are configured.
                            </div>
                        ) : milestoneAttachment.attached_milestones === 0 ? (
                            <div className="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                                {milestoneAttachment.active_program_templates}{' '}
                                program milestone
                                {milestoneAttachment.active_program_templates ===
                                1
                                    ? ''
                                    : 's'}{' '}
                                available.
                            </div>
                        ) : (
                            <div className="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                {milestoneAttachment.attached_milestones}{' '}
                                milestone
                                {milestoneAttachment.attached_milestones === 1
                                    ? ''
                                    : 's'}{' '}
                                attached.{' '}
                                {milestoneAttachment.attached_program_templates}{' '}
                                of{' '}
                                {milestoneAttachment.active_program_templates}{' '}
                                active program milestone
                                {milestoneAttachment.active_program_templates ===
                                1
                                    ? ''
                                    : 's'}{' '}
                                attached.
                            </div>
                        )}

                        <div className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                            {[
                                ['Attached', summary.total_milestones ?? 0],
                                ['Required', summary.required_milestones ?? 0],
                                [
                                    'Assessed beneficiaries',
                                    summary.assessed_assessments ?? 0,
                                ],
                                [
                                    'Outstanding',
                                    summary.unassessed_assessments ?? 0,
                                ],
                            ].map(([label, value]) => (
                                <div
                                    key={String(label)}
                                    className="rounded-md border bg-white p-3"
                                >
                                    <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        {label}
                                    </div>
                                    <div className="mt-1 text-lg font-semibold text-slate-900">
                                        {String(value)}
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="grid gap-3 lg:grid-cols-4">
                            {[
                                [
                                    'Completion',
                                    summary.milestone_completion_rate ?? 0,
                                    'bg-emerald-500',
                                ],
                                [
                                    'Assessment coverage',
                                    summary.assessment_coverage_rate ?? 0,
                                    'bg-blue-500',
                                ],
                                [
                                    'Passed',
                                    summary.pass_rate ?? 0,
                                    'bg-green-500',
                                ],
                                [
                                    'Failed',
                                    summary.failed_rate ?? 0,
                                    'bg-red-500',
                                ],
                            ].map(([label, value, color]) => (
                                <div key={String(label)} className="space-y-2">
                                    <div className="flex justify-between text-xs text-muted-foreground">
                                        <span>{label}</span>
                                        <span>{String(value)}%</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            className={`h-full rounded-full ${color}`}
                                            style={{
                                                width: `${Math.min(Number(value), 100)}%`,
                                            }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Milestone Delivery</CardTitle>
                            <CardDescription>
                                Completed assessments
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.milestone_completion_rate ?? 0}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Beneficiary Completion</CardTitle>
                            <CardDescription>
                                Completed all milestones
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.beneficiary_completion_rate ?? 0}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Health</CardTitle>
                            <CardDescription>
                                Captured attendance
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.attendance_rate ?? 0}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Blocked Sites</CardTitle>
                            <CardDescription>Need intervention</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.blocked_locations ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Project Manager</CardTitle>
                            <CardDescription>Assigned lead</CardDescription>
                        </CardHeader>
                        <CardContent className="text-base font-semibold">
                            {summary.project_manager_name ??
                                projectData.project_manager_name ??
                                '-'}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle>Learning Delivery</CardTitle>
                                <CardDescription>
                                    ERP project connection to LMS cohort
                                    delivery
                                </CardDescription>
                            </div>
                            <span
                                className={`rounded-full border px-3 py-1 text-xs font-medium ${
                                    learningSummary.integration_state ===
                                    'connected'
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : 'border-amber-200 bg-amber-50 text-amber-700'
                                }`}
                            >
                                {learningSummary.integration_state ===
                                'connected'
                                    ? 'Connected'
                                    : (learningSummary.integration_state ??
                                      'No LMS data')}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        {learningSummary.integration_state === 'connected' ? (
                            <>
                                <div className="grid gap-3 lg:grid-cols-2">
                                    {(learningSummary.mappings ?? []).map((mapping) => (
                                        <div key={mapping.id} className="rounded-md border bg-slate-50 p-3 text-sm">
                                            <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Learning programme
                                            </div>
                                            <div className="mt-1 font-semibold text-slate-900">
                                                {mapping.offering?.programme?.name ?? 'Unavailable'}
                                            </div>
                                            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                                <div>
                                                    <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                        Learning cohort
                                                    </div>
                                                    <div className="mt-1 font-medium text-slate-900">
                                                        {mapping.offering?.name ?? `Cohort ${mapping.lms_offering_id}`}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                        Mapping state
                                                    </div>
                                                    <div className="mt-1 font-medium text-slate-900 capitalize">
                                                        {mapping.status}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="mt-3 text-xs text-muted-foreground">
                                                LMS cohort ID: {mapping.lms_offering_id}
                                            </div>
                                            {(mapping.offering?.courses ?? []).length > 0 ? (
                                                <div className="mt-2 text-xs text-muted-foreground">
                                                    Courses: {(mapping.offering?.courses ?? []).map((course) => course.title).join(', ')}
                                                </div>
                                            ) : null}
                                        </div>
                                    ))}
                                </div>
                                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    {[
                                    [
                                        'Mapped LMS cohorts',
                                        learningSummary.metrics
                                            ?.mapped_offerings,
                                    ],
                                    [
                                        'LMS learners',
                                        learningSummary.metrics?.lms_learners,
                                    ],
                                    [
                                        'LMS facilitators',
                                        learningSummary.metrics
                                            ?.lms_facilitators,
                                    ],
                                    [
                                        'Certificates issued',
                                        learningSummary.metrics
                                            ?.certificates_issued,
                                    ],
                                    [
                                        'Average progress',
                                        learningSummary.metrics
                                            ?.average_progress === null
                                            ? 'Not tracked'
                                            : `${learningSummary.metrics?.average_progress}%`,
                                    ],
                                    [
                                        'Average attendance',
                                        learningSummary.metrics
                                            ?.average_attendance === null
                                            ? 'Not tracked'
                                            : `${learningSummary.metrics?.average_attendance}%`,
                                    ],
                                    [
                                        'Active learners',
                                        learningSummary.metrics
                                            ?.active_learners,
                                    ],
                                    [
                                        'Teaching assignments',
                                        learningSummary.metrics
                                            ?.active_teaching_assignments,
                                    ],
                                    ].map(([label, value]) => (
                                        <div
                                            key={label}
                                            className="rounded-md border bg-slate-50 p-3"
                                        >
                                            <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                {label}
                                            </div>
                                            <div className="mt-1 text-lg font-semibold text-slate-900">
                                                {value ?? 'No data yet'}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </>
                        ) : (
                            <div className="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                {learningSummary.message ??
                                    learningSummary.reason ??
                                    'No LMS learning delivery is currently mapped to this project.'}
                            </div>
                        )}

                        {canManageProjects ? (
                            <div className="grid gap-4 lg:grid-cols-[1fr,auto]">
                                <select
                                    value={selectedOfferingId}
                                    onChange={(event) =>
                                        setSelectedOfferingId(
                                            event.currentTarget.value,
                                        )
                                    }
                                    className="rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">
                                        Select LMS cohort/offering
                                    </option>
                                    {(
                                        learningDelivery?.availableOfferings ??
                                        []
                                    ).map((offering: LearningOffering) => (
                                        <option
                                            key={offering.id}
                                            value={offering.id}
                                        >
                                            {offering.display_name ??
                                                `${offering.programme?.name ?? 'No programme'} - ${offering.name}`}{' '}
                                            · {(offering.courses ?? []).map((course) => course.title).join(', ') || 'No courses'} · {offering.status}
                                        </option>
                                    ))}
                                </select>
                                <button
                                    type="button"
                                    disabled={
                                        !selectedOfferingId || learningBusy
                                    }
                                    onClick={() =>
                                        postLearningAction(
                                            `/projects/${projectData.id}/learning/mappings`,
                                            {
                                                lms_offering_id:
                                                    selectedOfferingId,
                                            },
                                        )
                                    }
                                    className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                >
                                    Configure Learning Delivery
                                </button>
                            </div>
                        ) : null}

                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="rounded-md border p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <div className="font-semibold">
                                            Learner provisioning
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {eligibleBeneficiaryIds.length}{' '}
                                            eligible of {learnerItems.length}{' '}
                                            project beneficiaries
                                        </div>
                                        {learningDelivery?.learnerProvisioning
                                            ?.metrics ? (
                                            <div className="mt-1 text-[11px] text-muted-foreground">
                                                Active{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics.active
                                                }{' '}
                                                · Enrolled{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics.enrolled
                                                }{' '}
                                                · Cohort pending{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics
                                                        .cohort_enrollment_pending
                                                }{' '}
                                                · Pending{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics
                                                        .invitation_pending
                                                }{' '}
                                                · Expired{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics
                                                        .invitation_expired
                                                }{' '}
                                                · Not provisioned{' '}
                                                {
                                                    learningDelivery
                                                        .learnerProvisioning
                                                        .metrics.not_provisioned
                                                }
                                            </div>
                                        ) : null}
                                    </div>
                                    {canManageProjects ? (
                                        <button
                                            type="button"
                                            disabled={
                                                learningBusy ||
                                                eligibleBeneficiaryIds.length ===
                                                    0
                                            }
                                            onClick={() =>
                                                postLearningAction(
                                                    `/projects/${projectData.id}/learning/provision-learners`,
                                                    {
                                                        beneficiary_ids:
                                                            eligibleBeneficiaryIds,
                                                    },
                                                )
                                            }
                                            className="rounded-md border border-emerald-200 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-50"
                                        >
                                            Provision eligible
                                        </button>
                                    ) : null}
                                </div>
                                <div className="mt-3 max-h-56 space-y-2 overflow-y-auto text-xs">
                                    {learnerItems.length === 0 ? (
                                        <p className="text-muted-foreground">
                                            No project beneficiaries available.
                                        </p>
                                    ) : (
                                        learnerItems.map((item) => (
                                            <div
                                                key={item.erp_beneficiary_id}
                                                className="rounded border bg-slate-50 p-2"
                                            >
                                                <div className="font-medium text-slate-900">
                                                    {item.name}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {item.eligible
                                                        ? 'Ready to provision'
                                                        : item.reason}
                                                </div>
                                                <div className="mt-1 text-[11px] font-medium text-slate-600 capitalize">
                                                    LMS:{' '}
                                                    {String(
                                                        item.lms_status ??
                                                            'unknown',
                                                    ).replaceAll('_', ' ')}
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>

                            <div className="rounded-md border p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <div className="font-semibold">
                                            Facilitator provisioning
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            {eligibleFacilitatorIds.length}{' '}
                                            eligible of{' '}
                                            {facilitatorItems.length} project
                                            facilitators
                                        </div>
                                        {learningDelivery
                                            ?.facilitatorProvisioning
                                            ?.metrics ? (
                                            <div className="mt-1 text-[11px] text-muted-foreground">
                                                Active{' '}
                                                {
                                                    learningDelivery
                                                        .facilitatorProvisioning
                                                        .metrics.active
                                                }{' '}
                                                · Pending{' '}
                                                {
                                                    learningDelivery
                                                        .facilitatorProvisioning
                                                        .metrics
                                                        .invitation_pending
                                                }{' '}
                                                · Expired{' '}
                                                {
                                                    learningDelivery
                                                        .facilitatorProvisioning
                                                        .metrics
                                                        .invitation_expired
                                                }{' '}
                                                · Not provisioned{' '}
                                                {
                                                    learningDelivery
                                                        .facilitatorProvisioning
                                                        .metrics.not_provisioned
                                                }
                                            </div>
                                        ) : null}
                                    </div>
                                    {canManageProjects ? (
                                        <button
                                            type="button"
                                            disabled={
                                                learningBusy ||
                                                eligibleFacilitatorIds.length ===
                                                    0
                                            }
                                            onClick={() =>
                                                postLearningAction(
                                                    `/projects/${projectData.id}/learning/provision-facilitators`,
                                                    {
                                                        facilitator_ids:
                                                            eligibleFacilitatorIds,
                                                    },
                                                )
                                            }
                                            className="rounded-md border border-sky-200 px-3 py-2 text-xs font-medium text-sky-700 hover:bg-sky-50 disabled:opacity-50"
                                        >
                                            Provision eligible
                                        </button>
                                    ) : null}
                                </div>
                                <div className="mt-3 max-h-56 space-y-2 overflow-y-auto text-xs">
                                    {facilitatorItems.length === 0 ? (
                                        <p className="text-muted-foreground">
                                            No project facilitators available.
                                        </p>
                                    ) : (
                                        facilitatorItems.map((item) => (
                                            <div
                                                key={item.erp_facilitator_id}
                                                className="flex items-center justify-between gap-2 rounded border bg-slate-50 p-2"
                                            >
                                                <div>
                                                    <div className="font-medium text-slate-900">
                                                        {item.name}
                                                    </div>
                                                    <div className="text-muted-foreground">
                                                        {item.eligible
                                                            ? 'Eligible for LMS teaching'
                                                            : item.reason}
                                                    </div>
                                                    <div className="mt-1 text-[11px] font-medium text-slate-600 capitalize">
                                                        LMS:{' '}
                                                        {String(
                                                            item.lms_status ??
                                                                'unknown',
                                                        ).replaceAll('_', ' ')}
                                                    </div>
                                                </div>
                                                {canManageProjects &&
                                                item.eligible &&
                                                String(
                                                    item.lms_status ?? '',
                                                ) === 'active' ? (
                                                    <button
                                                        type="button"
                                                        disabled={learningBusy}
                                                        onClick={() =>
                                                            postLearningAction(
                                                                `/projects/${projectData.id}/learning/teaching-assignments`,
                                                                {
                                                                    facilitator_id:
                                                                        item.erp_facilitator_id,
                                                                },
                                                            )
                                                        }
                                                        className="rounded-md border px-2 py-1 text-[11px] font-medium hover:bg-white"
                                                    >
                                                        Assign
                                                    </button>
                                                ) : canManageProjects &&
                                                  item.eligible ? (
                                                    <span className="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700">
                                                        {[
                                                            'invitation_pending',
                                                            'invitation_expired',
                                                        ].includes(
                                                            String(
                                                                item.lms_status ??
                                                                    '',
                                                            ),
                                                        )
                                                            ? 'Awaiting LMS activation'
                                                            : 'Provision LMS access first'}
                                                    </span>
                                                ) : null}
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        </div>

                        {learningStatus ? (
                            <div
                                className={`rounded-md border px-3 py-3 text-sm ${
                                    learningStatus.type === 'error'
                                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                                        : learningStatus.type === 'running'
                                          ? 'border-sky-200 bg-sky-50 text-sky-700'
                                          : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                }`}
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="font-medium">
                                        {learningStatus.phase}
                                    </div>
                                    <div className="text-xs">
                                        {learningStatus.progress}%
                                    </div>
                                </div>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/70">
                                    <div
                                        className={`h-full rounded-full transition-all duration-300 ${
                                            learningStatus.type === 'error'
                                                ? 'bg-rose-500'
                                                : learningStatus.type ===
                                                    'running'
                                                  ? 'bg-sky-500'
                                                  : 'bg-emerald-500'
                                        }`}
                                        style={{
                                            width: `${learningStatus.progress}%`,
                                        }}
                                    />
                                </div>
                                <div className="mt-2">
                                    {learningStatus.message}
                                </div>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[1.55fr,1fr]">
                    <ComparisonBarsChart
                        title="Location Delivery Comparison"
                        description="Compares milestone delivery, beneficiary completion, and attendance health across project sites."
                        rows={locations}
                        rowLabel={(location) =>
                            location.location ?? 'Unnamed location'
                        }
                        metrics={[
                            {
                                label: 'Milestones',
                                colorClass: 'bg-red-500',
                                value: (location) =>
                                    location.milestone_completion_rate ?? 0,
                            },
                            {
                                label: 'Completion',
                                colorClass: 'bg-amber-500',
                                value: (location) =>
                                    location.beneficiary_completion_rate ?? 0,
                            },
                            {
                                label: 'Attendance',
                                colorClass: 'bg-sky-500',
                                value: (location) =>
                                    location.attendance_rate ?? 0,
                            },
                        ]}
                        emptyMessage="No project locations are available yet."
                        maxRows={10}
                    />

                    <StackedCompositionChart
                        title="Beneficiary Movement"
                        description="Shows how the tracked beneficiary population is split between active delivery, completed beneficiaries, and dropped beneficiaries."
                        segments={[
                            {
                                label: 'Active',
                                value: summary.active_beneficiaries ?? 0,
                                colorClass: 'bg-sky-500',
                            },
                            {
                                label: 'Completed',
                                value: summary.completed_beneficiaries ?? 0,
                                colorClass: 'bg-emerald-500',
                            },
                            {
                                label: 'Dropped',
                                value: summary.dropped_beneficiaries ?? 0,
                                colorClass: 'bg-amber-500',
                            },
                        ]}
                        emptyMessage="No beneficiary movement data is available yet."
                    />
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.25fr,1fr]">
                    <LineTrendChart
                        title="Attendance Trend"
                        description="Attendance rate by register date across the project, using captured attendance entries only."
                        points={attendanceTrend.map((point) => ({
                            label: point.date.slice(5),
                            value: point.attendance_rate,
                        }))}
                        colorClass="bg-sky-500"
                        emptyMessage="No attendance history has been captured for this project yet."
                    />

                    <HorizontalBarChart
                        title="Completed Assessments by Location"
                        description="Highlights which delivery sites have progressed furthest through expected assessments."
                        items={locations
                            .map((location) => {
                                const expectedAssessments =
                                    location.expected_assessments ?? 0;

                                return {
                                    label:
                                        location.location ?? 'Unnamed location',
                                    value:
                                        expectedAssessments > 0
                                            ? Math.round(
                                                  ((location.completed_assessments ??
                                                      0) /
                                                      expectedAssessments) *
                                                      100,
                                              )
                                            : 0,
                                    hint: `${location.completed_assessments ?? 0}/${expectedAssessments} assessments`,
                                    colorClass: location.is_blocked
                                        ? 'bg-amber-500'
                                        : 'bg-emerald-500',
                                };
                            })
                            .sort((a, b) => b.value - a.value)}
                        emptyMessage="No assessment data is available for project locations yet."
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Project Repository</CardTitle>
                            <CardDescription>
                                Working library for posters, brochures, concept
                                documents, SLAs, reports, and delivery files
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <p className="text-slate-700">
                                This project has a dedicated repository inside
                                the document library so operational files stay
                                attached to the project while approved outputs
                                can still move into the organization vault by
                                reference.
                            </p>
                            {documentRepository ? (
                                <Link
                                    href={documentRepository.href}
                                    className="inline-flex rounded-md border border-emerald-200 px-3 py-2 font-medium text-emerald-700 hover:bg-emerald-50"
                                >
                                    Open project repository
                                </Link>
                            ) : (
                                <p className="text-muted-foreground">
                                    Repository not provisioned.
                                </p>
                            )}
                            {brochureRepository?.can_publish_to_vault ? (
                                <form
                                    onSubmit={handleBrochureVaultUpload}
                                    className="grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-3"
                                >
                                    <input
                                        type="hidden"
                                        name="folder_id"
                                        value={brochureRepository.folder_id}
                                    />
                                    <input
                                        type="hidden"
                                        name="document_type"
                                        value="brochure"
                                    />
                                    <input
                                        type="hidden"
                                        name="audience_scope"
                                        value="all_staff"
                                    />
                                    <input
                                        type="hidden"
                                        name="is_active"
                                        value="1"
                                    />
                                    <div className="font-medium text-slate-900">
                                        Upload Brochure To Vault
                                    </div>
                                    <input
                                        name="title"
                                        placeholder="Brochure title"
                                        className="rounded-md border bg-background px-3 py-2 text-sm"
                                        required
                                    />
                                    <textarea
                                        name="description"
                                        placeholder="Description"
                                        className="min-h-20 rounded-md border bg-background px-3 py-2 text-sm"
                                    />
                                    <input
                                        name="file"
                                        type="file"
                                        className="rounded-md border bg-background px-3 py-2 text-sm"
                                        required
                                    />
                                    {brochureUploadProgress !== null ? (
                                        <div className="rounded-md border bg-white p-2">
                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                <span>Uploading brochure</span>
                                                <span>
                                                    {brochureUploadProgress}%
                                                </span>
                                            </div>
                                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                                                <div
                                                    className="h-full rounded-full bg-red-600 transition-all"
                                                    style={{
                                                        width: `${brochureUploadProgress}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ) : null}
                                    <div className="flex flex-wrap items-center gap-3">
                                        <button
                                            type="submit"
                                            disabled={brochureVaultBusy}
                                            className="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            {brochureVaultBusy
                                                ? 'Uploading...'
                                                : 'Upload And Publish'}
                                        </button>
                                        <Link
                                            href={brochureRepository.href}
                                            className="text-sm font-medium text-slate-700 hover:text-slate-900"
                                        >
                                            Open brochures
                                        </Link>
                                    </div>
                                </form>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Commercial Structure</CardTitle>
                            <CardDescription>
                                Sponsor and implementation partners
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Sponsor
                                </div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {projectData.sponsor_name ??
                                        'No sponsor assigned'}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Implementation partners
                                </div>
                                {projectData.partner_names?.length ? (
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {projectData.partner_names.map(
                                            (partnerName: string) => (
                                                <span
                                                    key={partnerName}
                                                    className="rounded-full border border-slate-300 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700"
                                                >
                                                    {partnerName}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="mt-1 text-muted-foreground">
                                        No implementation partners assigned.
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Governance Metadata</CardTitle>
                            <CardDescription>
                                Funding and reporting obligations
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Contract Reference
                                </div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {projectData.contract_reference ??
                                        'Not recorded'}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Funding Amount
                                </div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {projectData.funding_amount !== null &&
                                    projectData.funding_amount !== undefined
                                        ? Number(
                                              projectData.funding_amount,
                                          ).toLocaleString(undefined, {
                                              minimumFractionDigits: 2,
                                              maximumFractionDigits: 2,
                                          })
                                        : 'Not recorded'}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Reporting Cadence
                                </div>
                                <div className="mt-1 font-medium text-slate-900 capitalize">
                                    {projectData.reporting_cadence
                                        ? String(
                                              projectData.reporting_cadence,
                                          ).replaceAll('_', ' ')
                                        : 'Not recorded'}
                                </div>
                            </div>
                            <div className="sm:col-span-2">
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Reporting Obligations
                                </div>
                                <div className="mt-1 whitespace-pre-wrap text-slate-700">
                                    {projectData.reporting_obligations ??
                                        'No reporting obligations recorded.'}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Transition Readiness</CardTitle>
                            <CardDescription>
                                Current workflow state and blockers
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Allowed transitions
                                </div>
                                {statusSummary?.allowed_transitions?.length ? (
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {statusSummary.allowed_transitions.map(
                                            (transition: StatusTransition) => (
                                                <span
                                                    key={transition.status}
                                                    className={`rounded-full border px-2.5 py-1 text-xs font-medium ${readinessTone(transition.ready)}`}
                                                >
                                                    {transition.label}
                                                    {!transition.ready
                                                        ? ` (${transition.blockers.length} blocker${transition.blockers.length === 1 ? '' : 's'})`
                                                        : ''}
                                                </span>
                                            ),
                                        )}
                                    </div>
                                ) : (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        No further transitions are allowed for
                                        this project.
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-3">
                                {(['active', 'completed'] as const).map(
                                    (statusKey) => {
                                        const readiness =
                                            statusSummary?.readiness?.[
                                                statusKey
                                            ];

                                        if (!readiness) return null;

                                        return (
                                            <div
                                                key={statusKey}
                                                className={`rounded-lg border p-3 ${readinessTone(readiness.ready)}`}
                                            >
                                                <div className="text-xs font-semibold tracking-wide uppercase">
                                                    {statusKey === 'active'
                                                        ? 'Activation readiness'
                                                        : 'Completion readiness'}
                                                </div>
                                                <div className="mt-1 text-sm font-medium">
                                                    {readiness.ready
                                                        ? 'Ready'
                                                        : `${readiness.blockers.length} blocker${readiness.blockers.length === 1 ? '' : 's'}`}
                                                </div>
                                                {!readiness.ready && (
                                                    <ul className="mt-2 space-y-1 text-xs">
                                                        {readiness.blockers.map(
                                                            (
                                                                blocker: string,
                                                            ) => (
                                                                <li
                                                                    key={
                                                                        blocker
                                                                    }
                                                                >
                                                                    {blocker}
                                                                </li>
                                                            ),
                                                        )}
                                                    </ul>
                                                )}
                                            </div>
                                        );
                                    },
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Locations</CardTitle>
                            <CardDescription>Progress by site</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {locations.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No locations added yet.
                                </p>
                            ) : (
                                <div className="space-y-4">
                                    {locations.map((loc) => (
                                        <div
                                            key={loc.id}
                                            className="rounded-md border p-3"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <div className="font-medium">
                                                        {loc.location}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Facilitator:{' '}
                                                        {loc.facilitator_name ??
                                                            '-'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Venue:{' '}
                                                        {loc.training_venue_address ??
                                                            '-'}
                                                    </div>
                                                </div>
                                                <div
                                                    className={`rounded-full border px-2.5 py-1 text-xs font-medium ${loc.is_blocked ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'}`}
                                                >
                                                    {loc.is_blocked
                                                        ? 'Needs intervention'
                                                        : 'On track'}
                                                </div>
                                            </div>

                                            <div className="mt-3 grid gap-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
                                                <div>
                                                    <div className="text-muted-foreground">
                                                        Active beneficiaries
                                                    </div>
                                                    <div className="font-semibold">
                                                        {loc.active_beneficiaries ??
                                                            0}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">
                                                        Milestone delivery
                                                    </div>
                                                    <div className="font-semibold">
                                                        {loc.milestone_completion_rate ??
                                                            0}
                                                        %
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">
                                                        Beneficiary completion
                                                    </div>
                                                    <div className="font-semibold">
                                                        {loc.beneficiary_completion_rate ??
                                                            0}
                                                        %
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-muted-foreground">
                                                        Attendance health
                                                    </div>
                                                    <div className="font-semibold">
                                                        {loc.attendance_rate ??
                                                            0}
                                                        %
                                                    </div>
                                                </div>
                                            </div>

                                            {loc.blockers?.length ? (
                                                <ul className="mt-3 space-y-1 text-xs text-amber-700">
                                                    {loc.blockers.map(
                                                        (blocker: string) => (
                                                            <li key={blocker}>
                                                                {blocker}
                                                            </li>
                                                        ),
                                                    )}
                                                </ul>
                                            ) : null}

                                            <div className="mt-3 text-xs text-muted-foreground">
                                                Completed assessments:{' '}
                                                {loc.completed_assessments ?? 0}
                                                /{loc.expected_assessments ?? 0}
                                            </div>

                                            <div className="mt-4 flex flex-wrap gap-2">
                                                <Link
                                                    href={`/project-locations/${loc.id}/progress`}
                                                    className="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-xs font-medium text-white hover:bg-red-700"
                                                >
                                                    <ClipboardCheck className="h-4 w-4" />
                                                    Record milestone performance
                                                </Link>
                                                <Link
                                                    href={`/project-locations/${loc.id}/attendance`}
                                                    className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                                >
                                                    <CalendarCheck2 className="h-4 w-4" />
                                                    Open attendance
                                                </Link>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Project Risks</CardTitle>
                            <CardDescription>
                                Current blockers and intervention points
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {summary.blockers?.length ? (
                                <ul className="space-y-2">
                                    {summary.blockers.map((blocker: string) => (
                                        <li
                                            key={blocker}
                                            className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800"
                                        >
                                            {blocker}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div className="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800">
                                    No project-level blockers are currently
                                    flagged.
                                </div>
                            )}

                            <div className="rounded-md border p-3">
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Beneficiary movement
                                </div>
                                <div className="mt-2 grid gap-2 sm:grid-cols-3">
                                    <div>
                                        <div className="text-muted-foreground">
                                            Total
                                        </div>
                                        <div className="font-semibold">
                                            {summary.total_beneficiaries ?? 0}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Completed
                                        </div>
                                        <div className="font-semibold">
                                            {summary.completed_beneficiaries ??
                                                0}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-muted-foreground">
                                            Dropped
                                        </div>
                                        <div className="font-semibold">
                                            {summary.dropped_beneficiaries ?? 0}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Project Finalization</CardTitle>
                            <CardDescription>
                                Run project closure in a dedicated section, not
                                on this page
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                <div className="font-semibold text-slate-900">
                                    {finalization.is_concluded
                                        ? 'Project already finalized'
                                        : 'Finalization is managed separately'}
                                </div>
                                <p className="mt-2 text-slate-700">
                                    Use the dedicated finalization workspace to
                                    upload closure evidence, upload registers,
                                    generate reports, capture the closing date,
                                    and complete sign-off in order.
                                </p>
                                <div className="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div className="rounded-md border bg-white p-3">
                                        <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Closure status
                                        </div>
                                        <div className="mt-1 font-medium text-slate-900">
                                            {finalization.is_concluded
                                                ? `Closed ${finalization.closure_date ?? ''}`.trim()
                                                : 'Open'}
                                        </div>
                                    </div>
                                    <div className="rounded-md border bg-white p-3">
                                        <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Evidence files
                                        </div>
                                        <div className="mt-1 font-medium text-slate-900">
                                            {finalization.evidence_count}
                                        </div>
                                    </div>
                                    <div className="rounded-md border bg-white p-3">
                                        <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Reports
                                        </div>
                                        <div className="mt-1 font-medium text-slate-900">
                                            {finalization.report_count}
                                        </div>
                                    </div>
                                </div>
                                <Link
                                    href={finalization.href}
                                    className="mt-4 inline-flex rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                                >
                                    Open finalization workspace
                                </Link>
                            </div>
                            {!finalization.can_manage ? (
                                <p className="text-sm text-muted-foreground">
                                    You can view the finalization workspace, but
                                    only project managers and project
                                    administrators can update it.
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Milestone Register</CardTitle>
                            <CardDescription>
                                Attached to project
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {canAttachMilestones ? (
                                <form
                                    onSubmit={handleSyncMilestones}
                                    className="mb-4"
                                >
                                    <button
                                        type="submit"
                                        className="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                                    >
                                        <Plus className="h-4 w-4" />
                                        Attach program milestones
                                    </button>
                                </form>
                            ) : null}

                            {milestones.length === 0 ? (
                                <div className="space-y-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                                    <p>
                                        No milestones are attached to this
                                        project yet. Program milestones must
                                        exist before they can be attached here.
                                    </p>
                                    {canAttachMilestones &&
                                    projectData.program_id ? (
                                        <Link
                                            href={`/milestone-templates/programs/${projectData.program_id}`}
                                            className="inline-flex rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-medium text-amber-800 hover:bg-amber-100"
                                        >
                                            Manage program milestone templates
                                        </Link>
                                    ) : null}
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    <ul className="space-y-2 text-sm">
                                        {milestones.map((m) => (
                                            <li
                                                key={m.id}
                                                className="flex items-center justify-between rounded-md border px-3 py-2"
                                            >
                                                <span>{m.title}</span>
                                                <span className="text-muted-foreground">
                                                    Max: {m.max_score ?? '-'}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                    <p className="text-xs text-muted-foreground">
                                        Use each location's Record milestone
                                        performance action to score
                                        beneficiaries against these milestones.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Project History</CardTitle>
                            <CardDescription>
                                Audit trail for governance and delivery actions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {history.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No project history has been recorded yet.
                                </p>
                            ) : (
                                <div className="space-y-3">
                                    {history.map((item) => (
                                        <div
                                            key={item.id}
                                            className="rounded-lg border p-3"
                                        >
                                            <div className="flex flex-wrap items-start justify-between gap-3">
                                                <div>
                                                    <div className="font-medium text-slate-900">
                                                        {item.summary}
                                                    </div>
                                                    <div className="text-xs tracking-wide text-muted-foreground uppercase">
                                                        {String(
                                                            item.action,
                                                        ).replaceAll('_', ' ')}
                                                    </div>
                                                </div>
                                                <div className="text-right text-xs text-muted-foreground">
                                                    <div>
                                                        {item.actor_name ??
                                                            'System'}
                                                    </div>
                                                    <div>
                                                        {item.created_at ?? '-'}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
