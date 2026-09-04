import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

import { HorizontalBarChart } from '@/components/charts/dashboard-charts';
import { DomainNav } from '@/components/domain-nav';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { programNavItems } from '@/config/domain-nav/programs';
import AppLayout from '@/layouts/app-layout';
import programs from '@/routes/programs';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Programs', href: programs.index().url },
    { title: 'Program Overview', href: '#' },
];

type ProgramPayload = {
    id: number;
    title: string;
    description?: string | null;
    slug?: string | null;
};

type ProgramProject = {
    id: number;
    name: string;
    status: string;
    year: string;
    period_label: string;
    start_date?: string | null;
    end_date?: string | null;
    description?: string | null;
    project_manager_name?: string | null;
    sponsor_name?: string | null;
    total_locations: number;
    total_beneficiaries: number;
    active_beneficiaries: number;
    completed_beneficiaries: number;
    dropped_beneficiaries: number;
    milestone_completion_rate: number;
    beneficiary_completion_rate: number;
    attendance_rate: number;
    blocked_locations: number;
    registers_captured: number;
    blockers: string[];
};

type YearlyImpact = {
    year: string;
    projects: number;
    beneficiaries: number;
    active_beneficiaries: number;
    locations: number;
    completed_projects: number;
};

export default function ProgramShow({
    program,
    stats,
    projects,
    documentRepository,
    brochureRepository,
}: {
    program: { data: ProgramPayload } | ProgramPayload;
    stats: Record<string, number>;
    yearlyImpact: YearlyImpact[];
    projects: ProgramProject[];
    documentRepository: { folder_id: number; href: string } | null;
    brochureRepository: {
        folder_id: number;
        href: string;
        upload_url: string;
        can_publish_to_vault: boolean;
    } | null;
}) {
    const programData =
        (program as { data?: ProgramPayload }).data ??
        (program as ProgramPayload);
    const [brochureVaultBusy, setBrochureVaultBusy] = useState(false);
    const [brochureUploadProgress, setBrochureUploadProgress] = useState<
        number | null
    >(null);

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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${programData.title} Overview`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {programData.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Program overview and iteration selector. Open a
                            specific iteration to see beneficiaries, locations,
                            attendance, milestones, and delivery performance for
                            that cohort.
                        </p>
                    </div>
                    <div className="flex items-center gap-3">
                        <Link
                            href={programs.index().url}
                            className="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                        >
                            Back to Programs
                        </Link>
                        <DomainNav items={programNavItems} />
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Iterations</CardTitle>
                            <CardDescription>
                                Associated executions
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.total_projects ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Unique Beneficiaries</CardTitle>
                            <CardDescription>People reached</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.unique_beneficiaries ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Tracked Beneficiaries</CardTitle>
                            <CardDescription>
                                Enrollments across iterations
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.tracked_beneficiaries ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Locations</CardTitle>
                            <CardDescription>Delivery sites</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.total_locations ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Active Years</CardTitle>
                            <CardDescription>
                                Program iterations
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.active_years ?? 0}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Active Iterations</CardTitle>
                            <CardDescription>Currently running</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.active_projects ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Completed Iterations</CardTitle>
                            <CardDescription>Closed iterations</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.completed_projects ?? 0}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Milestone Delivery</CardTitle>
                            <CardDescription>
                                Average completion rate
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.average_milestone_completion_rate ?? 0}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Health</CardTitle>
                            <CardDescription>
                                Average attendance rate
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.average_attendance_rate ?? 0}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Blocked Locations</CardTitle>
                            <CardDescription>Need intervention</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.blocked_locations ?? 0}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.2fr,1fr]">
                    <HorizontalBarChart
                        title="Iteration Reach"
                        description="Ranks program iterations by total tracked beneficiaries."
                        items={projects
                            .map((projectRow) => ({
                                label: projectRow.name,
                                value: projectRow.total_beneficiaries,
                                hint: `${projectRow.year} | ${projectRow.total_locations} location${projectRow.total_locations === 1 ? '' : 's'}`,
                                colorClass:
                                    projectRow.blocked_locations > 0
                                        ? 'bg-amber-500'
                                        : 'bg-emerald-500',
                            }))
                            .sort((a, b) => b.value - a.value)}
                        emptyMessage="No associated iteration reach data is available yet."
                    />
                    <Card>
                        <CardHeader>
                            <CardTitle>Program Overview</CardTitle>
                            <CardDescription>
                                Core definition and execution footprint
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4 text-sm">
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Slug
                                </div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {programData.slug ?? '-'}
                                </div>
                            </div>
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Description
                                </div>
                                <div className="mt-1 whitespace-pre-wrap text-slate-700">
                                    {programData.description ??
                                        'No program description has been recorded yet.'}
                                </div>
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Milestone templates
                                    </div>
                                    <div className="mt-1 font-medium text-slate-900">
                                        {stats.milestone_templates_count ?? 0}{' '}
                                        total
                                    </div>
                                    <div className="mt-1 text-xs text-slate-500">
                                        {stats.active_milestone_templates_count ??
                                            0}{' '}
                                        active |{' '}
                                        {stats.required_milestone_templates_count ??
                                            0}{' '}
                                        required
                                    </div>
                                </div>
                                <div>
                                    <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Projects using milestones
                                    </div>
                                    <div className="mt-1 font-medium text-slate-900">
                                        {stats.projects_using_milestones_count ??
                                            0}{' '}
                                        / {stats.total_projects ?? 0}
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <div className="text-xs font-semibold tracking-wide text-amber-800 uppercase">
                                    Program milestone templates
                                </div>
                                <p className="mt-2 text-sm text-amber-800">
                                    Define templates here first, then attach
                                    them to each project as stable project
                                    milestones.
                                </p>
                                <Link
                                    href={`/milestone-templates/programs/${programData.id}`}
                                    className="mt-3 inline-flex rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-medium text-amber-800 hover:bg-amber-100"
                                >
                                    Manage Milestone Templates
                                </Link>
                            </div>
                            <div>
                                <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                    Average beneficiary completion
                                </div>
                                <div className="mt-1 font-medium text-slate-900">
                                    {stats.average_beneficiary_completion_rate ??
                                        0}
                                    %
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Program Repository</CardTitle>
                        <CardDescription>
                            Working library for concept documents, brochures or
                            posters, SLAs, reports, and related files.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <p className="max-w-3xl text-slate-700">
                            Every program keeps an owned repository in the
                            document library so working files stay attached to
                            the program and approved outputs can still be
                            published into the organization vault by reference.
                        </p>
                        {documentRepository ? (
                            <Link
                                href={documentRepository.href}
                                className="rounded-md border border-emerald-200 px-3 py-2 font-medium text-emerald-700 hover:bg-emerald-50"
                            >
                                Open program repository
                            </Link>
                        ) : (
                            <span className="text-muted-foreground">
                                Repository not provisioned.
                            </span>
                        )}
                        {brochureRepository?.can_publish_to_vault ? (
                            <form
                                onSubmit={handleBrochureVaultUpload}
                                className="grid w-full gap-3 rounded-md border border-slate-200 bg-slate-50 p-3"
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
                        <CardTitle>Program Iterations</CardTitle>
                        <CardDescription>
                            Click an iteration to open its dashboard with
                            beneficiaries, locations, attendance, milestones,
                            and all related delivery detail.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {projects.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No iterations have been linked to this program
                                yet.
                            </p>
                        ) : (
                            <div className="overflow-hidden rounded-lg border">
                                <table className="min-w-full divide-y divide-slate-200">
                                    <thead className="bg-slate-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Iteration
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Year
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Beneficiaries
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Locations
                                            </th>
                                            <th className="px-4 py-3 text-left text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                Open
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100 bg-white">
                                        {projects.map((projectRow) => (
                                            <tr
                                                key={projectRow.id}
                                                className="hover:bg-slate-50"
                                            >
                                                <td className="px-4 py-3">
                                                    <div className="font-medium text-slate-900">
                                                        {projectRow.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            projectRow.period_label
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    {projectRow.year}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    {projectRow.status}
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    {
                                                        projectRow.total_beneficiaries
                                                    }
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    {projectRow.total_locations}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/projects/${projectRow.id}`}
                                                        className="text-sm font-semibold text-red-700 hover:text-red-800"
                                                    >
                                                        Open iteration
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
