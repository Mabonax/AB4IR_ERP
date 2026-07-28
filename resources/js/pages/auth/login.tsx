import { Form, Head, usePage } from '@inertiajs/react';
import { ClipboardList, ShieldCheck, UsersRound, type LucideIcon } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitLayout from '@/layouts/auth/auth-split-layout';
import { getBrandIdentity } from '@/lib/brand';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { type SharedData } from '@/types';

type HighlightPanel = {
    label: string;
    icon: LucideIcon;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}

export default function Login({
    status,
    canResetPassword,
    canRegister,
}: LoginProps) {
    const shared = usePage<SharedData>().props;
    const { tagline } = getBrandIdentity(shared);
    const supportEmail = shared.brand?.support_email ?? shared.organization?.email ?? 'support@poa.org.za';
    const highlightPanels: HighlightPanel[] = [
        {
            label: 'Programme delivery oversight',
            icon: ClipboardList,
        },
        {
            label: 'Governance and compliance execution',
            icon: ShieldCheck,
        },
        {
            label: 'Field and staff coordination',
            icon: UsersRound,
        },
    ];

    return (
        <>
            <Head title="Log in" />
            <AuthSplitLayout
                title="Welcome back"
                description={tagline}
            >
                {status ? (
                    <div className="mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium !text-emerald-700">
                        {status}
                    </div>
                ) : null}

                <div className="mb-6 grid gap-3 sm:grid-cols-3">
                    {highlightPanels.map(({ label, icon: Icon }) => (
                        <div key={label} className="border border-[#ECECEC] bg-[#F7F7F7] p-4">
                            <div className="mb-3 flex h-11 w-11 items-center justify-center rounded-full border border-[#F3CDD3] bg-[#C8102E]/8 text-[#C8102E]">
                                <Icon className="h-5 w-5" aria-hidden="true" strokeWidth={2.2} />
                            </div>
                            <p className="text-xs font-medium leading-5 !text-[#374151]">{label}</p>
                        </div>
                    ))}
                </div>

                <Form
                    {...store.form()}
                    resetOnSuccess={['password']}
                    className="flex flex-col gap-6 !bg-transparent"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-5">
                                <div className="grid gap-2">
                                    <Label htmlFor="email" className="text-sm font-medium text-[#111111]">
                                        Email address
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="admin@poa.org.za"
                                        className="h-12 rounded-none border-[#D1D5DB] bg-white text-[#111111] shadow-none focus-visible:border-[#D71920] focus-visible:ring-[#D71920]/15"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <div className="flex items-center">
                                        <Label htmlFor="password" className="text-sm font-medium text-[#111111]">
                                            Password
                                        </Label>
                                        {canResetPassword ? (
                                            <TextLink
                                                href={request()}
                                                className="ml-auto text-sm text-[#C8102E] hover:text-[#D71920]"
                                                tabIndex={5}
                                            >
                                                Forgot password?
                                            </TextLink>
                                        ) : null}
                                    </div>
                                    <Input
                                        id="password"
                                        type="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Enter your password"
                                        className="h-12 rounded-none border-[#D1D5DB] bg-white text-[#111111] shadow-none focus-visible:border-[#D71920] focus-visible:ring-[#D71920]/15"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex items-center space-x-3 border-y border-[#ECECEC] py-4">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                        className="rounded-none border-[#9CA3AF] data-[state=checked]:border-[#C8102E] data-[state=checked]:bg-[#C8102E]"
                                    />
                                    <Label htmlFor="remember" className="text-sm text-[#374151]">
                                        Remember me on this device
                                    </Label>
                                </div>

                                <Button
                                    type="submit"
                                    className="h-12 w-full rounded-none bg-[#C8102E] text-white hover:bg-[#D71920]"
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-button"
                                >
                                    {processing ? <Spinner className="text-white" /> : null}
                                    Log in to ERP
                                </Button>
                            </div>

                            {canRegister ? (
                                <div className="text-center text-sm !text-[#6B7280]">
                                    Don&apos;t have an account?{' '}
                                    <TextLink href="/register" tabIndex={5} className="text-[#C8102E]">
                                        Sign up
                                    </TextLink>
                                </div>
                            ) : (
                                <div className="border border-[#ECECEC] bg-[#F7F7F7] px-4 py-4 text-sm leading-6 !text-[#4B5563]">
                                    Access is provisioned by administrators. For account support, contact{' '}
                                    <a
                                        href={`mailto:${supportEmail}`}
                                        className="font-medium text-[#C8102E]"
                                    >
                                        {supportEmail}
                                    </a>
                                    .
                                </div>
                            )}
                        </>
                    )}
                </Form>
            </AuthSplitLayout>
        </>
    );
}
