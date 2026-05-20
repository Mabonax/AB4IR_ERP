import { Head, useForm } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { DomainNav } from '@/components/domain-nav';
import { settingsNavItems } from '@/config/domain-nav/settings';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { CustomTable } from '@/components/custom-table';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Leave', href: '/settings/leave' },
];

export default function LeaveSettings({
    leaveAccount,
    myRequests,
    leaveTypes,
}: {
    leaveAccount: {
        period_start: string | null;
        period_end: string | null;
        annual: {
            accrued: number;
            taken: number;
            available: number;
        };
        sick: {
            entitlement: number;
            taken: number;
            available: number;
        };
        pending: {
            count: number;
            days: number;
        };
    };
    myRequests: any[];
    leaveTypes: { value: string; label: string }[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        leave_type: 'annual',
        start_date: '',
        end_date: '',
        reason: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Leave" />

            <div className="p-4 space-y-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Leave</h1>
                        <div className="text-sm text-muted-foreground">
                            Manage your leave requests
                        </div>
                    </div>
                    <DomainNav items={settingsNavItems} />
                </div>

                <div className="space-y-8">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <Heading
                            variant="small"
                            title="Leave balance"
                            description="Current leave period"
                        />

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Annual Accrued</div>
                                <div className="text-2xl font-semibold">{leaveAccount.annual.accrued}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Annual Taken</div>
                                <div className="text-2xl font-semibold">{leaveAccount.annual.taken}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Annual Available</div>
                                <div className="text-2xl font-semibold">{leaveAccount.annual.available}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Sick Available</div>
                                <div className="text-2xl font-semibold">{leaveAccount.sick.available}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Pending Days</div>
                                <div className="text-2xl font-semibold">{leaveAccount.pending.days}</div>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Sick Entitlement</div>
                                <div className="text-2xl font-semibold">{leaveAccount.sick.entitlement}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Sick Taken</div>
                                <div className="text-2xl font-semibold">{leaveAccount.sick.taken}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Pending Requests</div>
                                <div className="text-2xl font-semibold">{leaveAccount.pending.count}</div>
                            </div>
                            <div className="rounded-lg border bg-card p-4 shadow-sm">
                                <div className="text-sm text-muted-foreground">Period</div>
                                <div className="text-sm">
                                    {leaveAccount.period_start ?? '-'} to {leaveAccount.period_end ?? '-'}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <Heading
                            variant="small"
                            title="Request leave"
                            description="Submit your leave application"
                        />

                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                post('/leave-requests', {
                                    preserveScroll: true,
                                    onSuccess: () => reset(),
                                });
                            }}
                            className="mt-4 grid gap-3 sm:grid-cols-2"
                        >
                            <div className="sm:col-span-2">
                                <select
                                    value={data.leave_type}
                                    onChange={(e) => setData('leave_type', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    {leaveTypes.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError className="mt-1" message={errors.leave_type} />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    required
                                />
                                <InputError className="mt-1" message={errors.start_date} />
                            </div>
                            <div>
                                <Input
                                    type="date"
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    required
                                />
                                <InputError className="mt-1" message={errors.end_date} />
                            </div>
                            <div className="sm:col-span-2">
                                <Input
                                    type="text"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="Reason (optional)"
                                />
                                <InputError className="mt-1" message={errors.reason} />
                            </div>
                            <Button type="submit" className="sm:col-span-2" disabled={processing}>
                                Submit Request
                            </Button>
                        </form>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <Heading
                            variant="small"
                            title="My leave requests"
                            description="Your submitted requests"
                        />

                        <div className="mt-4">
                            <CustomTable
                                columns={[
                                    { label: 'Start', key: 'start_date', className: 'px-4 py-2 text-left' },
                                    { label: 'End', key: 'end_date', className: 'px-4 py-2 text-left' },
                                    { label: 'Type', key: 'leave_type_label', className: 'px-4 py-2 text-left' },
                                    { label: 'Days', key: 'total_days', className: 'px-4 py-2 text-left' },
                                    { label: 'Status', key: 'status', className: 'px-4 py-2 text-left' },
                                    { label: 'Manager', key: 'manager_name', className: 'px-4 py-2 text-left' },
                                ]}
                                data={myRequests.map((item: any) => ({
                                    ...item,
                                    manager_name: item.manager_name ?? '-',
                                }))}
                                actions={[]}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
