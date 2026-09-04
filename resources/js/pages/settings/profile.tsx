import { Transition } from '@headlessui/react';
import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    Clock3,
    Eye,
    EyeOff,
    KeyRound,
    Palette,
    ShieldCheck,
} from 'lucide-react';
import { type Ref, useRef, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { edit as editAppearance } from '@/routes/appearance';
import profile from '@/routes/profile';
import userPassword from '@/routes/user-password';
import { send } from '@/routes/verification';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'My profile',
        href: profile.edit().url,
    },
];

type StaffProfile = {
    id: number;
    name: string;
    department: string | null;
    manager: string | null;
    phone: string | null;
    status: string | null;
    start_date: string | null;
    is_ceo: boolean;
    is_board_member: boolean;
    is_manager: boolean;
    is_intern: boolean;
} | null;

type LeaveAccount = {
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

const cardClass = 'rounded-lg border bg-card p-5 shadow-sm';
const labelClass = 'text-sm font-medium text-foreground';
const inputClass = 'mt-2 h-11 bg-background';

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function titleCase(value: string): string {
    return value
        .replace(/[-_]/g, ' ')
        .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
}

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value.includes('T') ? value : `${value}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return '-';
    }

    return new Intl.DateTimeFormat('en-GB', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(parsed).replace('Sept', 'Sep');
}

function jobTitle(staff: StaffProfile, roles: string[] = []): string {
    if (staff?.is_ceo) {
        return 'CEO';
    }

    if (staff?.is_board_member) {
        return 'Board member';
    }

    if (staff?.is_manager) {
        return 'Manager';
    }

    if (staff?.is_intern) {
        return 'Intern';
    }

    return roles[0] ? titleCase(roles[0]) : 'Administrator';
}

function PasswordDialog() {
    const [open, setOpen] = useState(false);
    const [showCurrent, setShowCurrent] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="border-orange-500 text-orange-600 hover:bg-orange-50 hover:text-orange-700">
                    Change password
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-md gap-0 rounded-lg p-0">
                <DialogHeader className="border-b px-6 py-5">
                    <DialogTitle className="text-base">Change password</DialogTitle>
                </DialogHeader>

                <Form
                    {...userPassword.update.form()}
                    options={{ preserveScroll: true }}
                    resetOnError={['password', 'password_confirmation', 'current_password']}
                    resetOnSuccess
                    onSuccess={() => setOpen(false)}
                    onError={(errors) => {
                        if (errors.password) {
                            passwordInput.current?.focus();
                        }

                        if (errors.current_password) {
                            currentPasswordInput.current?.focus();
                        }
                    }}
                    className="space-y-5 px-6 py-5"
                >
                    {({ errors, processing }) => (
                        <>
                            <PasswordField
                                id="current_password"
                                label="Current password"
                                name="current_password"
                                placeholder="Enter current password"
                                show={showCurrent}
                                setShow={setShowCurrent}
                                inputRef={currentPasswordInput}
                                autoComplete="current-password"
                                error={errors.current_password}
                            />

                            <PasswordField
                                id="password"
                                label="New password"
                                name="password"
                                placeholder="Enter new password"
                                show={showPassword}
                                setShow={setShowPassword}
                                inputRef={passwordInput}
                                autoComplete="new-password"
                                helper="Use at least 8 characters"
                                error={errors.password}
                            />

                            <PasswordField
                                id="password_confirmation"
                                label="Confirm new password"
                                name="password_confirmation"
                                placeholder="Confirm new password"
                                show={showConfirmation}
                                setShow={setShowConfirmation}
                                autoComplete="new-password"
                                error={errors.password_confirmation}
                            />

                            <DialogFooter className="pt-1 sm:justify-between">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing} data-test="update-password-button">
                                    Update password
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function PasswordField({
    id,
    label,
    name,
    placeholder,
    show,
    setShow,
    inputRef,
    autoComplete,
    helper,
    error,
}: {
    id: string;
    label: string;
    name: string;
    placeholder: string;
    show: boolean;
    setShow: (value: boolean) => void;
    inputRef?: Ref<HTMLInputElement>;
    autoComplete: string;
    helper?: string;
    error?: string;
}) {
    const Icon = show ? EyeOff : Eye;

    return (
        <div>
            <Label htmlFor={id}>{label}</Label>
            <div className="relative mt-2">
                <Input
                    id={id}
                    ref={inputRef}
                    name={name}
                    type={show ? 'text' : 'password'}
                    autoComplete={autoComplete}
                    placeholder={placeholder}
                    className="h-11 pr-11"
                />
                <button
                    type="button"
                    onClick={() => setShow(!show)}
                    className="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center text-muted-foreground hover:text-foreground"
                    aria-label={show ? `Hide ${label.toLowerCase()}` : `Show ${label.toLowerCase()}`}
                >
                    <Icon className="size-4" />
                </button>
            </div>
            {helper ? <p className="mt-2 text-sm text-muted-foreground">{helper}</p> : null}
            <InputError className="mt-2" message={error} />
        </div>
    );
}

export default function Profile({
    mustVerifyEmail,
    status,
    staff,
    leaveAccount,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    staff: StaffProfile;
    leaveAccount: LeaveAccount;
}) {
    const { auth, attendancePrompt } = usePage<SharedData>().props;
    const roles = auth.user.roles ?? [];
    const roleLabel = roles[0] ? titleCase(roles[0]) : 'User';
    const staffStatus = staff?.status ? titleCase(staff.status) : 'Active';
    const attendanceStatus = attendancePrompt?.record?.clock_out_at
        ? 'Clocked out'
        : attendancePrompt?.record?.clock_in_at
          ? 'Clocked in'
          : 'Not clocked in';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My profile" />

            <div className="space-y-7 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal text-foreground">
                        My profile & account
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Manage your personal information, security, and preferences.
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-4">
                    <div className="flex size-16 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xl font-semibold text-white">
                        {initials(auth.user.name)}
                    </div>
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-3">
                            <h2 className="text-lg font-semibold text-foreground">{auth.user.name}</h2>
                            <span className="rounded-md border border-orange-300 bg-orange-50 px-2 py-1 text-xs font-medium text-orange-700">
                                {roleLabel}
                            </span>
                            <span className="rounded-md border border-emerald-300 bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                {staffStatus}
                            </span>
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {staff?.department ?? 'No department'} <span className="px-2">-</span> {auth.user.email}
                        </p>
                    </div>
                </div>

                <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,0.64fr)]">
                    <div className="space-y-5">
                        <section className={cardClass}>
                            <h2 className="text-base font-semibold">Personal information</h2>

                            <Form
                                {...profile.update.form()}
                                options={{ preserveScroll: true }}
                                className="mt-5 space-y-4"
                            >
                                {({ processing, recentlySuccessful, errors }) => (
                                    <>
                                        <div className="grid gap-x-6 gap-y-4 md:grid-cols-[9rem_minmax(0,1fr)] md:items-center">
                                            <Label htmlFor="name" className={labelClass}>
                                                Full name
                                            </Label>
                                            <div>
                                                <Input
                                                    id="name"
                                                    className={inputClass}
                                                    defaultValue={auth.user.name}
                                                    name="name"
                                                    required
                                                    autoComplete="name"
                                                    placeholder="Full name"
                                                />
                                                <InputError className="mt-2" message={errors.name} />
                                            </div>

                                            <Label htmlFor="email" className={labelClass}>
                                                Email address
                                            </Label>
                                            <div>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    className={inputClass}
                                                    defaultValue={auth.user.email}
                                                    name="email"
                                                    required
                                                    autoComplete="username"
                                                    placeholder="Email address"
                                                />
                                                <InputError className="mt-2" message={errors.email} />
                                            </div>

                                            <Label htmlFor="phone" className={labelClass}>
                                                Phone number
                                            </Label>
                                            <Input id="phone" className={inputClass} value={staff?.phone ?? '+27 00 000 0000'} readOnly />

                                            <Label htmlFor="department" className={labelClass}>
                                                Department
                                            </Label>
                                            <Input id="department" className={inputClass} value={staff?.department ?? 'No department'} readOnly />

                                            <Label htmlFor="job_title" className={labelClass}>
                                                Job title
                                            </Label>
                                            <Input id="job_title" className={inputClass} value={jobTitle(staff, roles)} readOnly />
                                        </div>

                                        {mustVerifyEmail && auth.user.email_verified_at === null ? (
                                            <div className="rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-sm text-orange-800">
                                                Your email address is unverified.{' '}
                                                <Link href={send()} as="button" className="font-medium underline underline-offset-4">
                                                    Resend verification email.
                                                </Link>
                                                {status === 'verification-link-sent' ? (
                                                    <div className="mt-1 font-medium">
                                                        A new verification link has been sent to your email address.
                                                    </div>
                                                ) : null}
                                            </div>
                                        ) : null}

                                        <div className="flex items-center gap-4">
                                            <Button disabled={processing} data-test="update-profile-button">
                                                Save changes
                                            </Button>
                                            <Transition
                                                show={recentlySuccessful}
                                                enter="transition ease-in-out"
                                                enterFrom="opacity-0"
                                                leave="transition ease-in-out"
                                                leaveTo="opacity-0"
                                            >
                                                <p className="text-sm text-muted-foreground">Saved</p>
                                            </Transition>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </section>

                        <section className={cardClass}>
                            <h2 className="text-base font-semibold">Account security</h2>
                            <div className="mt-5 divide-y">
                                <div className="flex flex-wrap items-center justify-between gap-4 pb-5">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-12 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                                            <KeyRound className="size-5" />
                                        </div>
                                        <div>
                                            <div className="font-semibold">Password</div>
                                            <div className="text-sm text-muted-foreground">
                                                Last updated {formatDate(auth.user.updated_at)}
                                            </div>
                                        </div>
                                    </div>
                                    <PasswordDialog />
                                </div>

                                <div className="flex flex-wrap items-center justify-between gap-4 pt-5">
                                    <div className="flex items-center gap-4">
                                        <div className="flex size-12 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                                            <ShieldCheck className="size-5" />
                                        </div>
                                        <div>
                                            <div className="font-semibold">Two-factor authentication</div>
                                            <div className="text-sm text-muted-foreground">
                                                {auth.user.two_factor_enabled ? 'Enabled' : 'Not enabled'}
                                            </div>
                                        </div>
                                    </div>
                                    <Button asChild variant="outline" className="border-orange-500 text-orange-600 hover:bg-orange-50 hover:text-orange-700">
                                        <Link href="/settings/two-factor">
                                            {auth.user.two_factor_enabled ? 'Manage 2FA' : 'Set up 2FA'}
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside className="space-y-5">
                        <section className={cardClass}>
                            <h2 className="text-base font-semibold">Leave balance</h2>
                            <div className="mt-5 grid gap-6 sm:grid-cols-2">
                                <Metric label="Annual leave" value={`${leaveAccount.annual.available} days`} />
                                <Metric label="Sick leave" value={`${leaveAccount.sick.available} days`} />
                                <Metric label="Current period" value={`${formatDate(leaveAccount.period_start)} - ${formatDate(leaveAccount.period_end)}`} compact />
                                <Metric label="Pending requests" value={String(leaveAccount.pending.count)} />
                            </div>
                            <Button asChild variant="outline" className="mt-5 border-orange-500 text-orange-600 hover:bg-orange-50 hover:text-orange-700">
                                <Link href="/settings/leave">
                                    <CalendarDays className="size-4" />
                                    View leave
                                </Link>
                            </Button>
                        </section>

                        <section className={cardClass}>
                            <h2 className="text-base font-semibold">Attendance</h2>
                            <div className="mt-5 flex items-center gap-4">
                                <div className="flex size-12 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                                    <Clock3 className="size-5" />
                                </div>
                                <div>
                                    <div className="font-semibold">{attendanceStatus}</div>
                                    <div className="text-sm text-muted-foreground">
                                        Today, {formatDate(attendancePrompt?.date ?? null)}
                                    </div>
                                </div>
                            </div>
                            <Button asChild variant="outline" className="mt-5 border-orange-500 text-orange-600 hover:bg-orange-50 hover:text-orange-700">
                                <Link href="/settings/attendance">
                                    <Clock3 className="size-4" />
                                    View attendance
                                </Link>
                            </Button>
                        </section>

                        <section className={cardClass}>
                            <h2 className="text-base font-semibold">Appearance</h2>
                            <div className="mt-5 flex items-center gap-4">
                                <div className="flex size-12 items-center justify-center rounded-full bg-orange-50 text-orange-600">
                                    <Palette className="size-5" />
                                </div>
                                <div>
                                    <div className="font-semibold">Theme</div>
                                    <div className="text-sm text-muted-foreground">System default</div>
                                </div>
                            </div>
                            <Button asChild variant="outline" className="mt-5 border-orange-500 text-orange-600 hover:bg-orange-50 hover:text-orange-700">
                                <Link href={editAppearance()}>
                                    <Palette className="size-4" />
                                    Change appearance
                                </Link>
                            </Button>
                        </section>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}

function Metric({
    label,
    value,
    compact = false,
}: {
    label: string;
    value: string;
    compact?: boolean;
}) {
    return (
        <div>
            <div className="text-sm text-muted-foreground">{label}</div>
            <div className={compact ? 'mt-2 text-base font-semibold' : 'mt-2 text-xl font-semibold'}>
                {value}
            </div>
        </div>
    );
}
