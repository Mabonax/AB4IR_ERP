import { Head, Link, router } from '@inertiajs/react';
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

const learningActionMessage = (data: any) => {
    if (data?.offering?.name) {
        return `Mapped to LMS offering: ${data.offering.name}.`;
    }

    if (Array.isArray(data?.items)) {
        const counts = data.items.reduce((summary: Record<string, number>, item: any) => {
            const status = String(item.status ?? 'unknown').replaceAll('_', ' ');
            summary[status] = (summary[status] ?? 0) + 1;
            return summary;
        }, {});
        const rendered = Object.entries(counts)
            .map(([status, count]) => `${count} ${status}`)
            .join(', ');

        return rendered ? `LMS provisioning processed: ${rendered}.` : 'LMS provisioning completed with no changed records.';
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
    finalization,
    documentRepository,
    brochureRepository,
    learningDelivery,
}: {
    project: any;
    milestones: any[];
    progress: any;
    locations: any[];
    attendanceTrend: { date: string; attendance_rate: number }[];
    history: any[];
    canManageProjects: boolean;
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
    learningDelivery: any;
}) {
    const projectData = project?.data ?? project;
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
    const [brochureUploadProgress, setBrochureUploadProgress] = useState<number | null>(null);

    const handleSyncMilestones = (e: React.FormEvent) => {
        e.preventDefault();

        router.post(`/projects/${projectData.id}/milestones/sync`, {});
    };

    const handleBrochureVaultUpload = (event: React.FormEvent<HTMLFormElement>) => {
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
            onProgress: (progress) => setBrochureUploadProgress(progress?.percentage ?? 0),
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

    const postLearningAction = async (url: string, payload: any) => {
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

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.reason || data.message || 'Learning action failed.');
            }

            setLearningStatus({
                message: learningActionMessage(data),
                phase: 'Completed',
                progress: 100,
                type: 'success',
            });
            router.reload({ only: ['learningDelivery', 'history'] });
        } catch (error) {
            const message = error instanceof Error
                ? error.message
                : 'Learning action failed.';

            setLearningStatus({
                message: message === 'LMS could not be reached.'
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
        .filter((item: any) => item.eligible)
        .map((item: any) => item.erp_beneficiary_id);
    const eligibleFacilitatorIds = facilitatorItems
        .filter((item: any) => item.eligible)
        .map((item: any) => item.erp_facilitator_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project View" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-xl font-semibold">
                            {projectData.name}
                        </h1>
                        {canManageProjects ? (
                            <div className="flex flex-wrap gap-2">
                                <Link
                                    href={finalization.href}
                                    className="rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                                >
                                    Project Finalization
                                </Link>
                                <Link
                                    href={`/projects/${projectData.id}/edit`}
                                    className="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                                >
                                    Edit Project
                                </Link>
                            </div>
                        ) : finalization.can_manage ? (
                            <Link
                                href={finalization.href}
                                className="rounded-md border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                            >
                                Project Finalization
                            </Link>
                        ) : null}
                    </div>
                    <DomainNav items={projectNavItems} />
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
                    <Card>
                        <CardHeader>
                            <CardTitle>Status</CardTitle>
                            <CardDescription>Current</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {projectData.status_label ??
                                projectData.status ??
                                '-'}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Start Date</CardTitle>
                            <CardDescription>Project start</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {projectData.start_date ?? '-'}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Locations</CardTitle>
                            <CardDescription>Delivery sites</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.total_locations ?? locations.length}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Milestones</CardTitle>
                            <CardDescription>Delivery units</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.total_milestones ?? milestones.length}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Active Beneficiaries</CardTitle>
                            <CardDescription>In delivery</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {summary.active_beneficiaries ?? 0}
                        </CardContent>
                    </Card>
                </div>

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
                                    ERP project connection to LMS cohort delivery
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
                                    : learningSummary.integration_state ??
                                      'No LMS data'}
                            </span>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        {learningSummary.integration_state === 'connected' ? (
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
                                        <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                            {label}
                                        </div>
                                        <div className="mt-1 text-lg font-semibold text-slate-900">
                                            {value ?? 'No data yet'}
                                        </div>
                                    </div>
                                ))}
                            </div>
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
                                    ).map((offering: any) => (
                                        <option
                                            key={offering.id}
                                            value={offering.id}
                                        >
                                            {offering.name} ·{' '}
                                            {offering.programme?.name ??
                                                'No programme'}{' '}
                                            · {offering.status}
                                        </option>
                                    ))}
                                </select>
                                <button
                                    type="button"
                                    disabled={!selectedOfferingId || learningBusy}
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
                                        {learningDelivery?.learnerProvisioning?.metrics ? (
                                            <div className="mt-1 text-[11px] text-muted-foreground">
                                                Active {learningDelivery.learnerProvisioning.metrics.active} · Pending {learningDelivery.learnerProvisioning.metrics.invitation_pending} · Expired {learningDelivery.learnerProvisioning.metrics.invitation_expired} · Not provisioned {learningDelivery.learnerProvisioning.metrics.not_provisioned}
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
                                        learnerItems.map((item: any) => (
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
                                                <div className="mt-1 text-[11px] font-medium capitalize text-slate-600">
                                                    LMS: {String(item.lms_status ?? 'unknown').replaceAll('_', ' ')}
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
                                            eligible of {facilitatorItems.length}{' '}
                                            project facilitators
                                        </div>
                                        {learningDelivery?.facilitatorProvisioning?.metrics ? (
                                            <div className="mt-1 text-[11px] text-muted-foreground">
                                                Active {learningDelivery.facilitatorProvisioning.metrics.active} · Pending {learningDelivery.facilitatorProvisioning.metrics.invitation_pending} · Expired {learningDelivery.facilitatorProvisioning.metrics.invitation_expired} · Not provisioned {learningDelivery.facilitatorProvisioning.metrics.not_provisioned}
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
                                        facilitatorItems.map((item: any) => (
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
                                                    <div className="mt-1 text-[11px] font-medium capitalize text-slate-600">
                                                        LMS: {String(item.lms_status ?? 'unknown').replaceAll('_', ' ')}
                                                    </div>
                                                </div>
                                                {canManageProjects &&
                                                item.eligible &&
                                                String(item.lms_status ?? '') === 'active' ? (
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
                                                ) : canManageProjects && item.eligible ? (
                                                    <span className="rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700">
                                                        {['invitation_pending', 'invitation_expired'].includes(String(item.lms_status ?? ''))
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
                            <div className={`rounded-md border px-3 py-3 text-sm ${
                                learningStatus.type === 'error'
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : learningStatus.type === 'running'
                                        ? 'border-sky-200 bg-sky-50 text-sky-700'
                                        : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            }`}>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <div className="font-medium">{learningStatus.phase}</div>
                                    <div className="text-xs">{learningStatus.progress}%</div>
                                </div>
                                <div className="mt-2 h-2 overflow-hidden rounded-full bg-white/70">
                                    <div
                                        className={`h-full rounded-full transition-all duration-300 ${
                                            learningStatus.type === 'error'
                                                ? 'bg-rose-500'
                                                : learningStatus.type === 'running'
                                                    ? 'bg-sky-500'
                                                    : 'bg-emerald-500'
                                        }`}
                                        style={{ width: `${learningStatus.progress}%` }}
                                    />
                                </div>
                                <div className="mt-2">{learningStatus.message}</div>
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
                            .map((location) => ({
                                label: location.location ?? 'Unnamed location',
                                value:
                                    location.expected_assessments > 0
                                        ? Math.round(
                                              ((location.completed_assessments ??
                                                  0) /
                                                  location.expected_assessments) *
                                                  100,
                                          )
                                        : 0,
                                hint: `${location.completed_assessments ?? 0}/${location.expected_assessments ?? 0} assessments`,
                                colorClass: location.is_blocked
                                    ? 'bg-amber-500'
                                    : 'bg-emerald-500',
                            }))
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
                                    <input type="hidden" name="folder_id" value={brochureRepository.folder_id} />
                                    <input type="hidden" name="document_type" value="brochure" />
                                    <input type="hidden" name="audience_scope" value="all_staff" />
                                    <input type="hidden" name="is_active" value="1" />
                                    <div className="font-medium text-slate-900">Upload Brochure To Vault</div>
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
                                                <span>{brochureUploadProgress}%</span>
                                            </div>
                                            <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                                                <div
                                                    className="h-full rounded-full bg-red-600 transition-all"
                                                    style={{ width: `${brochureUploadProgress}%` }}
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
                                            {brochureVaultBusy ? 'Uploading...' : 'Upload And Publish'}
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
                                            (transition: any) => (
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
                            {canManageProjects ? (
                                <form
                                    onSubmit={handleSyncMilestones}
                                    className="mb-4"
                                >
                                    <button
                                        type="submit"
                                        className="rounded-md bg-red-600 px-3 py-2 text-sm text-white hover:bg-red-700"
                                    >
                                        Attach program milestones
                                    </button>
                                </form>
                            ) : null}

                            {milestones.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No milestones attached yet.
                                </p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {milestones.map((m) => (
                                        <li
                                            key={m.id}
                                            className="flex items-center justify-between"
                                        >
                                            <span>{m.title}</span>
                                            <span className="text-muted-foreground">
                                                Max: {m.max_score ?? '-'}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
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
