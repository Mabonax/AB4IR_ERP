import { Head } from '@inertiajs/react';

import {
    HorizontalBarChart,
    StackedCompositionChart,
} from '@/components/charts/dashboard-charts';
import { CustomTable } from '@/components/custom-table';
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
    {
        title: 'Delivery Locations Dashboard',
        href: '/project-locations/dashboard',
    },
];

type LocationRow = {
    id: number;
    project_name: string | null;
    province: string | null;
    facilitator_name: string | null;
    beneficiaries: number;
    milestones: number;
    completed_assessments: number;
    failed_assessments: number;
    assessed_assessments: number;
    total_assessments: number;
    outstanding_assessments: number;
    milestone_completion_rate: number;
    pass_rate: number;
    delivery_status: string;
};

export default function ProjectLocationsDashboard({
    stats,
    locations,
}: {
    stats: {
        total_locations: number;
        total_beneficiaries: number;
        completed_assessments: number;
        total_assessments: number;
        assessment_coverage_rate: number;
        pass_rate: number;
    };
    locations: LocationRow[];
}) {
    const columns = [
        {
            label: 'Project',
            key: 'project_name',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Location',
            key: 'province',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Facilitator',
            key: 'facilitator_name',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Beneficiaries',
            key: 'beneficiaries',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Milestones',
            key: 'milestones',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Assessments',
            key: 'assessments',
            className: 'px-4 py-2 text-left',
            render: (row: LocationRow) => (
                <span>
                    {row.assessed_assessments}/{row.total_assessments}
                </span>
            ),
        },
        {
            label: 'Outstanding',
            key: 'outstanding_assessments',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Completion',
            key: 'milestone_completion_rate',
            className: 'px-4 py-2 text-left',
            render: (row: LocationRow) => (
                <span>{row.milestone_completion_rate}%</span>
            ),
        },
        {
            label: 'Pass Rate',
            key: 'pass_rate',
            className: 'px-4 py-2 text-left',
            render: (row: LocationRow) => <span>{row.pass_rate}%</span>,
        },
        {
            label: 'Status',
            key: 'delivery_status',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Actions',
            key: 'actions',
            isAction: true,
            className: 'px-4 py-2 text-left',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Delivery Locations Dashboard" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">
                        Delivery Locations Dashboard
                    </h1>
                    <DomainNav items={projectNavItems} />
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Locations</CardTitle>
                            <CardDescription>Total</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.total_locations}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Beneficiaries</CardTitle>
                            <CardDescription>Total</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.total_beneficiaries}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Coverage</CardTitle>
                            <CardDescription>Assessed</CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.assessment_coverage_rate}%
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Pass Rate</CardTitle>
                            <CardDescription>
                                Successful assessments
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {stats.pass_rate}%
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.35fr,1fr]">
                    <HorizontalBarChart
                        title="Assessment Completion by Location"
                        description="Shows which locations are furthest along in completing the expected assessment workload."
                        items={locations
                            .map((location) => ({
                                label: `${location.project_name ?? 'Project'} • ${location.province ?? 'Location'}`,
                                value:
                                    location.total_assessments > 0
                                        ? Math.round(
                                              (location.completed_assessments /
                                                  location.total_assessments) *
                                                  100,
                                          )
                                        : 0,
                                hint: `${location.completed_assessments}/${location.total_assessments} assessments`,
                                colorClass: 'bg-red-500',
                            }))
                            .sort((a, b) => b.value - a.value)}
                        emptyMessage="No assessment workload is available for charting yet."
                    />

                    <StackedCompositionChart
                        title="Assessment Coverage"
                        description="Overall view of completed versus remaining expected assessments across the visible facilitator scope."
                        segments={[
                            {
                                label: 'Completed',
                                value: stats.completed_assessments,
                                colorClass: 'bg-emerald-500',
                            },
                            {
                                label: 'Remaining',
                                value: Math.max(
                                    stats.total_assessments -
                                        stats.completed_assessments,
                                    0,
                                ),
                                colorClass: 'bg-slate-300',
                            },
                        ]}
                        emptyMessage="No assessment totals are available yet."
                    />
                </div>

                <CustomTable
                    columns={columns}
                    data={locations}
                    actions={[
                        {
                            icon: 'CalendarCheck2',
                            onClick: (row) => {
                                window.location.href = `/project-locations/${row.id}/attendance`;
                            },
                        },
                        {
                            icon: 'ClipboardCheck',
                            onClick: (row) => {
                                window.location.href = `/project-locations/${row.id}/progress`;
                            },
                        },
                    ]}
                />

                <div className="text-sm text-muted-foreground">
                    Tip: Use the progress button to assess beneficiaries per
                    milestone.
                </div>
            </div>
        </AppLayout>
    );
}
