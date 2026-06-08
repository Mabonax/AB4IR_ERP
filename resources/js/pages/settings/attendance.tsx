import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

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
import { settingsNavItems } from '@/config/domain-nav/settings';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/settings/attendance' },
];

export default function AttendanceSettings({
    staff,
    today,
    history,
}: {
    staff: {
        id: number;
        name: string;
        department_name: string | null;
        manager_name: string | null;
        status: string;
    };
    today: {
        date: string;
        day_label: string;
        clock_in_cutoff: string;
        auto_clock_out_time: string;
        current_time: string;
        record: {
            clock_in_at: string | null;
            clock_out_at: string | null;
            clock_in_status_label: string;
            clock_out_source: string | null;
            late_override_reason: string | null;
            hours_worked: string | null;
        } | null;
        active_override: {
            opened_by_name: string | null;
            reason: string;
        } | null;
        pending_request: {
            request_reason: string | null;
            requested_by_name: string | null;
        } | null;
        can_clock_in: boolean;
        clock_in_message: string;
        can_clock_out: boolean;
    };
    history: Array<{
        id: number;
        attendance_date: string;
        clock_in_at: string | null;
        clock_out_at: string | null;
        clock_in_status_label: string;
        clock_out_source: string | null;
        hours_worked: string | null;
        late_override_reason: string | null;
    }>;
}) {
    const { props } = usePage<{ flash?: Record<string, string | null> }>();
    const flash = props.flash ?? {};
    const [lateReason, setLateReason] = useState('');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Attendance" />

            <div className="space-y-8 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Attendance</h1>
                        <div className="text-sm text-muted-foreground">
                            {staff.department_name ?? 'No department'} | Manager{' '}
                            {staff.manager_name ?? '-'}
                        </div>
                    </div>
                    <DomainNav items={settingsNavItems} />
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

                <Card>
                    <CardHeader>
                        <CardTitle>Today</CardTitle>
                        <CardDescription>{today.day_label}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">
                                    Current Time
                                </div>
                                <div className="mt-1 text-2xl font-semibold">
                                    {today.current_time}
                                </div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">
                                    Clock-in Cut-off
                                </div>
                                <div className="mt-1 text-2xl font-semibold">
                                    {today.clock_in_cutoff}
                                </div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">
                                    Auto Clock-out
                                </div>
                                <div className="mt-1 text-2xl font-semibold">
                                    {today.auto_clock_out_time}
                                </div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">
                                    Status
                                </div>
                                <div className="mt-1 text-lg font-semibold">
                                    {today.record?.clock_out_at
                                        ? 'Closed'
                                        : today.record?.clock_in_at
                                          ? 'Clocked In'
                                          : 'Not Clocked In'}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4">
                            <div className="text-sm text-muted-foreground">
                                Clocking Rule
                            </div>
                            <div className="mt-1 font-medium">
                                {today.clock_in_message}
                            </div>
                            {today.active_override ? (
                                <div className="mt-2 text-sm text-muted-foreground">
                                    Late clock-in opened by{' '}
                                    {today.active_override.opened_by_name ??
                                        'manager'}
                                    : {today.active_override.reason}
                                </div>
                            ) : null}
                            {today.pending_request ? (
                                <div className="mt-2 text-sm text-muted-foreground">
                                    Waiting for manager approval:{' '}
                                    {today.pending_request.request_reason ??
                                        'Late request submitted.'}
                                </div>
                            ) : null}
                        </div>

                        {!today.can_clock_in &&
                        !today.record?.clock_in_at &&
                        !today.pending_request ? (
                            <div className="space-y-3 rounded-lg border p-4">
                                <div className="text-sm font-medium">
                                    Late clock-in request
                                </div>
                                <textarea
                                    rows={3}
                                    value={lateReason}
                                    onChange={(event) =>
                                        setLateReason(event.currentTarget.value)
                                    }
                                    placeholder="Explain why you are late so your line manager can review it."
                                    className="w-full rounded-md border bg-card px-3 py-2 text-sm"
                                />
                                <div>
                                    <Button
                                        variant="outline"
                                        disabled={lateReason.trim() === ''}
                                        onClick={() =>
                                            router.post(
                                                '/settings/attendance/late-request',
                                                { reason: lateReason },
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        setLateReason(''),
                                                },
                                            )
                                        }
                                    >
                                        Send To Manager
                                    </Button>
                                </div>
                            </div>
                        ) : null}

                        <div className="flex flex-wrap gap-3">
                            <Button
                                disabled={!today.can_clock_in}
                                onClick={() =>
                                    router.post(
                                        '/settings/attendance/clock-in',
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Clock In
                            </Button>
                            <Button
                                variant="outline"
                                disabled={!today.can_clock_out}
                                onClick={() =>
                                    router.post(
                                        '/settings/attendance/clock-out',
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Clock Out
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/settings/profile">
                                    Back to Profile
                                </Link>
                            </Button>
                        </div>

                        {today.record ? (
                            <div className="grid gap-4 md:grid-cols-4">
                                <div className="rounded-lg border p-4">
                                    <div className="text-sm text-muted-foreground">
                                        Clock In
                                    </div>
                                    <div className="mt-1 font-medium">
                                        {today.record.clock_in_at ?? '-'}
                                    </div>
                                </div>
                                <div className="rounded-lg border p-4">
                                    <div className="text-sm text-muted-foreground">
                                        Clock Out
                                    </div>
                                    <div className="mt-1 font-medium">
                                        {today.record.clock_out_at ?? '-'}
                                    </div>
                                </div>
                                <div className="rounded-lg border p-4">
                                    <div className="text-sm text-muted-foreground">
                                        Status
                                    </div>
                                    <div className="mt-1 font-medium">
                                        {today.record.clock_in_status_label}
                                    </div>
                                </div>
                                <div className="rounded-lg border p-4">
                                    <div className="text-sm text-muted-foreground">
                                        Hours
                                    </div>
                                    <div className="mt-1 font-medium">
                                        {today.record.hours_worked ?? '-'}
                                    </div>
                                </div>
                            </div>
                        ) : null}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>My Clocking Records</CardTitle>
                        <CardDescription>
                            Recent attendance logs for your account.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <CustomTable
                            columns={[
                                {
                                    label: 'Date',
                                    key: 'attendance_date',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Clock In',
                                    key: 'clock_in_at',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Clock Out',
                                    key: 'clock_out_at',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Status',
                                    key: 'clock_in_status_label',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Out Source',
                                    key: 'clock_out_source',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Hours',
                                    key: 'hours_worked',
                                    className: 'px-4 py-2 text-left',
                                },
                                {
                                    label: 'Override Reason',
                                    key: 'late_override_reason',
                                    className: 'px-4 py-2 text-left',
                                },
                            ]}
                            data={history.map((item) => ({
                                ...item,
                                clock_out_source: item.clock_out_source ?? '-',
                                hours_worked: item.hours_worked ?? '-',
                                late_override_reason:
                                    item.late_override_reason ?? '-',
                            }))}
                        />
                    </CardContent>
                </Card>

                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <Heading
                        variant="small"
                        title="Attendance policy"
                        description="Daily operating rule for staff clocking"
                    />
                    <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                        <p>Clock-in closes at 09:00 daily.</p>
                        <p>
                            After 09:00, you must submit a reason and your line
                            manager must approve the late clock-in before you
                            can clock in.
                        </p>
                        <p>
                            Clock-out can be done by you at any time before
                            16:30, otherwise the system closes open sessions
                            automatically at 16:30.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
