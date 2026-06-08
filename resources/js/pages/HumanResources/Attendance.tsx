import { Head, router, useForm, usePage } from '@inertiajs/react';

import { CustomTable } from '@/components/custom-table';
import { DomainNav } from '@/components/domain-nav';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { humanResourcesNavItems } from '@/config/domain-nav/human-resources';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Human Resources', href: '/human-resources' },
    { title: 'Attendance', href: '/human-resources/attendance' },
];

export default function HumanResourcesAttendance({
    filters,
    period,
    departments,
    staffOptions,
    todayStats,
    openOverrides,
    pendingRequests,
    reportRows,
    recentActivities,
}: {
    filters: {
        period: string;
        anchor_date: string;
        department_id: number | null;
        staff_id: number | null;
    };
    period: {
        label: string;
        start: string;
        end: string;
    };
    departments: Array<{ id: number; name: string }>;
    staffOptions: Array<{
        id: number;
        name: string;
        department_name: string | null;
    }>;
    todayStats: {
        staff_scope: number;
        clocked_in: number;
        late_overrides: number;
        open_sessions: number;
        auto_clock_outs: number;
    };
    openOverrides: Array<{
        id: number;
        staff_member_id: number;
        staff_member_name: string;
        department_name: string | null;
        attendance_date: string;
        reason: string;
        request_reason: string | null;
        status: string;
        requested_by_name: string | null;
        opened_by_name: string | null;
        approved_at: string | null;
        used_at: string | null;
    }>;
    pendingRequests: Array<{
        id: number;
        staff_member_id: number;
        staff_member_name: string;
        department_name: string | null;
        request_reason: string | null;
        requested_by_name: string | null;
        attendance_date: string;
    }>;
    reportRows: Array<{
        staff_id: number;
        staff_name: string;
        department_name: string | null;
        manager_name: string | null;
        recorded_days: number;
        present_days: number;
        late_days: number;
        auto_clock_out_days: number;
        total_hours: number;
    }>;
    recentActivities: Array<{
        id: number;
        staff_member_name: string;
        department_name: string | null;
        action_label: string;
        actor_name: string | null;
        reason: string | null;
        occurred_at: string;
    }>;
}) {
    const { props } = usePage<{ flash?: Record<string, string | null> }>();
    const flash = props.flash ?? {};
    const lateOverrideForm = useForm({
        staff_id: filters.staff_id ? String(filters.staff_id) : '',
        reason: '',
    });

    const applyFilters = (next: Partial<typeof filters>) => {
        router.get(
            '/human-resources/attendance',
            {
                ...filters,
                ...next,
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Staff Attendance" />

            <div className="space-y-8 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Staff Attendance
                        </h1>
                        <div className="text-sm text-muted-foreground">
                            Consolidated view for clocking, overrides, audit
                            activity, and printable reports.
                        </div>
                    </div>
                    <DomainNav items={humanResourcesNavItems} />
                </div>

                {flash.success ? (
                    <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
                        {String(flash.success)}
                    </div>
                ) : null}

                {flash.error ? (
                    <div className="rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
                        {String(flash.error)}
                    </div>
                ) : null}

                <div className="grid gap-4 md:grid-cols-5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Staff Scope</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {todayStats.staff_scope}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Clocked In</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {todayStats.clocked_in}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Late Overrides</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {todayStats.late_overrides}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Open Sessions</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {todayStats.open_sessions}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Auto Clock-outs</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold">
                            {todayStats.auto_clock_outs}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filters and Print</CardTitle>
                        <CardDescription>
                            {period.label} report window from {period.start} to{' '}
                            {period.end}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 md:grid-cols-4">
                            <select
                                value={filters.period}
                                onChange={(event) =>
                                    applyFilters({
                                        period: event.currentTarget.value,
                                    })
                                }
                                className="rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option value="week">Weekly</option>
                                <option value="month">Monthly</option>
                                <option value="quarter">Quarterly</option>
                                <option value="year">Annual</option>
                            </select>
                            <input
                                type="date"
                                value={filters.anchor_date}
                                onChange={(event) =>
                                    applyFilters({
                                        anchor_date: event.currentTarget.value,
                                    })
                                }
                                className="rounded-md border bg-card px-3 py-2 text-sm"
                            />
                            <select
                                value={
                                    filters.department_id
                                        ? String(filters.department_id)
                                        : ''
                                }
                                onChange={(event) =>
                                    applyFilters({
                                        department_id: event.currentTarget.value
                                            ? Number(event.currentTarget.value)
                                            : null,
                                        staff_id: null,
                                    })
                                }
                                className="rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option value="">All departments</option>
                                {departments.map((department) => (
                                    <option
                                        key={department.id}
                                        value={department.id}
                                    >
                                        {department.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                value={
                                    filters.staff_id
                                        ? String(filters.staff_id)
                                        : ''
                                }
                                onChange={(event) =>
                                    applyFilters({
                                        staff_id: event.currentTarget.value
                                            ? Number(event.currentTarget.value)
                                            : null,
                                    })
                                }
                                className="rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option value="">All staff</option>
                                {staffOptions.map((staff) => (
                                    <option key={staff.id} value={staff.id}>
                                        {staff.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Button
                                variant="outline"
                                onClick={() =>
                                    applyFilters({
                                        period: 'week',
                                        anchor_date: filters.anchor_date,
                                        department_id: null,
                                        staff_id: null,
                                    })
                                }
                            >
                                Reset Filters
                            </Button>
                            <Button
                                onClick={() => {
                                    const params = new URLSearchParams();
                                    params.set('period', filters.period);
                                    params.set(
                                        'anchor_date',
                                        filters.anchor_date,
                                    );
                                    if (filters.department_id)
                                        params.set(
                                            'department_id',
                                            String(filters.department_id),
                                        );
                                    if (filters.staff_id)
                                        params.set(
                                            'staff_id',
                                            String(filters.staff_id),
                                        );
                                    window.location.assign(
                                        `/human-resources/attendance/report/pdf?${params.toString()}`,
                                    );
                                }}
                            >
                                Print PDF Report
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Open Late Clock-in</CardTitle>
                        <CardDescription>
                            Managers must review the staff reason and then
                            approve the late clock-in with their own reason.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="grid gap-3 md:grid-cols-3"
                            onSubmit={(event) => {
                                event.preventDefault();
                                lateOverrideForm.post(
                                    '/human-resources/attendance/late-overrides',
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            lateOverrideForm.reset('reason'),
                                    },
                                );
                            }}
                        >
                            <select
                                value={lateOverrideForm.data.staff_id}
                                onChange={(event) =>
                                    lateOverrideForm.setData(
                                        'staff_id',
                                        event.currentTarget.value,
                                    )
                                }
                                className="rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option value="">Select staff member</option>
                                {pendingRequests.map((request) => (
                                    <option
                                        key={request.id}
                                        value={request.staff_member_id}
                                    >
                                        {request.staff_member_name}{' '}
                                        {request.department_name
                                            ? `| ${request.department_name}`
                                            : ''}
                                    </option>
                                ))}
                            </select>
                            <input
                                type="text"
                                value={lateOverrideForm.data.reason}
                                onChange={(event) =>
                                    lateOverrideForm.setData(
                                        'reason',
                                        event.currentTarget.value,
                                    )
                                }
                                placeholder="Manager reason for approving the late clock-in"
                                className="rounded-md border bg-card px-3 py-2 text-sm md:col-span-2"
                            />
                            <div className="flex flex-wrap gap-3 md:col-span-3">
                                <Button
                                    type="submit"
                                    disabled={lateOverrideForm.processing}
                                >
                                    Approve Late Clock-in
                                </Button>
                                {lateOverrideForm.errors.staff_id ? (
                                    <div className="text-sm text-red-600">
                                        {lateOverrideForm.errors.staff_id}
                                    </div>
                                ) : null}
                                {lateOverrideForm.errors.reason ? (
                                    <div className="text-sm text-red-600">
                                        {lateOverrideForm.errors.reason}
                                    </div>
                                ) : null}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Report Summary</CardTitle>
                        <CardDescription>
                            Printable staff attendance table for the selected
                            period.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CustomTable
                            columns={[
                                {
                                    label: 'Staff',
                                    key: 'staff_name',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Department',
                                    key: 'department_name',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Manager',
                                    key: 'manager_name',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Recorded Days',
                                    key: 'recorded_days',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Present',
                                    key: 'present_days',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Late',
                                    key: 'late_days',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Auto Out',
                                    key: 'auto_clock_out_days',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Total Hours',
                                    key: 'total_hours',
                                    className: 'px-4 py-2 text-left',
                                    render: (row) =>
                                        Number(row.total_hours).toFixed(2),
                                },
                            ]}
                            data={reportRows.map((row) => ({
                                ...row,
                                department_name: row.department_name ?? '-',
                                manager_name: row.manager_name ?? '-',
                            }))}
                        />
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending Late Requests</CardTitle>
                            <CardDescription>
                                Staff-submitted late reasons waiting for line
                                manager approval.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <CustomTable
                                columns={[
                                    {
                                        label: 'Staff',
                                        key: 'staff_member_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Department',
                                        key: 'department_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Requested By',
                                        key: 'requested_by_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Staff Reason',
                                        key: 'request_reason',
                                        className: 'px-4 py-2 text-left',
                                    },
                                ]}
                                data={pendingRequests.map((row) => ({
                                    ...row,
                                    department_name: row.department_name ?? '-',
                                    requested_by_name:
                                        row.requested_by_name ?? '-',
                                    request_reason: row.request_reason ?? '-',
                                }))}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Approved Late Windows</CardTitle>
                            <CardDescription>
                                Visible late clock-in windows and whether they
                                were used.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <CustomTable
                                columns={[
                                    {
                                        label: 'Staff',
                                        key: 'staff_member_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Department',
                                        key: 'department_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Reason',
                                        key: 'reason',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Staff Reason',
                                        key: 'request_reason',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Opened By',
                                        key: 'opened_by_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Approved At',
                                        key: 'approved_at',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Used At',
                                        key: 'used_at',
                                        className: 'px-4 py-2 text-left',
                                    },
                                ]}
                                data={openOverrides.map((row) => ({
                                    ...row,
                                    department_name: row.department_name ?? '-',
                                    request_reason: row.request_reason ?? '-',
                                    opened_by_name: row.opened_by_name ?? '-',
                                    approved_at: row.approved_at ?? '-',
                                    used_at: row.used_at ?? 'Open',
                                }))}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Attendance Activity</CardTitle>
                            <CardDescription>
                                Administrators and managers can review every
                                recorded attendance action here.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <CustomTable
                                columns={[
                                    {
                                        label: 'When',
                                        key: 'occurred_at',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Staff',
                                        key: 'staff_member_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Department',
                                        key: 'department_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Action',
                                        key: 'action_label',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Actor',
                                        key: 'actor_name',
                                        className: 'px-4 py-2 text-left',
                                    },
                                    {
                                        label: 'Reason',
                                        key: 'reason',
                                        className: 'px-4 py-2 text-left',
                                    },
                                ]}
                                data={recentActivities.map((row) => ({
                                    ...row,
                                    department_name: row.department_name ?? '-',
                                    actor_name: row.actor_name ?? 'System',
                                    reason: row.reason ?? '-',
                                }))}
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Heading
                        variant="small"
                        title="Reporting coverage"
                        description="The same page supports weekly, monthly, quarterly, and annual printing for the full staff scope or a single staff member."
                    />
                </div>
            </div>
        </AppLayout>
    );
}
