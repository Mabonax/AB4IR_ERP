import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';

import { DomainNav } from '@/components/domain-nav';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { settingsNavItems } from '@/config/domain-nav/settings';
import AppLayout from '@/layouts/app-layout';
import profile from '@/routes/profile';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: profile.edit().url,
    },
];

export default function Profile({
    mustVerifyEmail,
    status,
    staff,
    leaveAccount,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    staff: {
        id: number;
        name: string;
        department: string | null;
        manager: string | null;
        start_date: string | null;
        is_ceo: boolean;
        is_board_member: boolean;
    } | null;
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
}) {
    const { auth } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <div className="p-4 space-y-8">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Profile</h1>
                        <div className="text-sm text-muted-foreground">
                            {staff?.department ?? 'No department'}
                        </div>
                    </div>
                    <DomainNav items={settingsNavItems} />
                </div>

                <div className="space-y-8">
                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <Heading
                            variant="small"
                            title="Leave account"
                            description="Annual and sick leave visibility"
                        />

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Annual Available</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.annual.available}</div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Annual Taken</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.annual.taken}</div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Sick Available</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.sick.available}</div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Sick Taken</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.sick.taken}</div>
                            </div>
                            <div className="rounded-lg border p-4 sm:col-span-2">
                                <div className="text-sm text-muted-foreground">Current Period</div>
                                <div className="mt-1 font-medium">
                                    {leaveAccount.period_start ?? '-'} to {leaveAccount.period_end ?? '-'}
                                </div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Pending Requests</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.pending.count}</div>
                            </div>
                            <div className="rounded-lg border p-4">
                                <div className="text-sm text-muted-foreground">Pending Days</div>
                                <div className="mt-1 text-2xl font-semibold">{leaveAccount.pending.days}</div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border bg-card p-6 shadow-sm">
                        <Heading
                            variant="small"
                            title="Profile information"
                            description="Update your name and email address"
                        />

                        <Form
                            {...profile.update.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            className="mt-6 space-y-6"
                        >
                            {({ processing, recentlySuccessful, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>

                                        <Input
                                            id="name"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user.name}
                                            name="name"
                                            required
                                            autoComplete="name"
                                            placeholder="Full name"
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email address</Label>

                                        <Input
                                            id="email"
                                            type="email"
                                            className="mt-1 block w-full"
                                            defaultValue={auth.user.email}
                                            name="email"
                                            required
                                            autoComplete="username"
                                            placeholder="Email address"
                                        />

                                        <InputError
                                            className="mt-2"
                                            message={errors.email}
                                        />
                                    </div>

                                    {mustVerifyEmail &&
                                        auth.user.email_verified_at === null && (
                                            <div>
                                                <p className="-mt-4 text-sm text-muted-foreground">
                                                    Your email address is
                                                    unverified.{' '}
                                                    <Link
                                                        href={send()}
                                                        as="button"
                                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                                    >
                                                        Click here to resend the
                                                        verification email.
                                                    </Link>
                                                </p>

                                                {status ===
                                                    'verification-link-sent' && (
                                                    <div className="mt-2 text-sm font-medium text-green-600">
                                                        A new verification link has
                                                        been sent to your email
                                                        address.
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                    <div className="flex items-center gap-4">
                                        <Button
                                            disabled={processing}
                                            data-test="update-profile-button"
                                        >
                                            Save
                                        </Button>

                                        <Transition
                                            show={recentlySuccessful}
                                            enter="transition ease-in-out"
                                            enterFrom="opacity-0"
                                            leave="transition ease-in-out"
                                            leaveTo="opacity-0"
                                        >
                                            <p className="text-sm text-neutral-600">
                                                Saved
                                            </p>
                                        </Transition>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
