import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Eye,
    EyeOff,
    LockKeyhole,
    Mail,
    ShieldCheck,
    Users,
    Zap,
} from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

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
    const [showPassword, setShowPassword] = useState(false);

    return (
        <main className="relative min-h-svh overflow-hidden bg-[#f8f8f7] text-slate-950">
            <Head title="Log in" />

            <div
                className="absolute inset-0 bg-cover bg-center"
                style={{ backgroundImage: "url('/auth-bg.png')" }}
                aria-hidden="true"
            />
            <style>{`
                .ab4ir-login-field:-webkit-autofill,
                .ab4ir-login-field:-webkit-autofill:hover,
                .ab4ir-login-field:-webkit-autofill:focus {
                    background-color: #ffffff !important;
                    background-image: none !important;
                    -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
                    box-shadow: 0 0 0 1000px #ffffff inset !important;
                    -webkit-text-fill-color: #0f172a !important;
                    caret-color: #0f172a !important;
                    color: #0f172a !important;
                }

                .ab4ir-login-field {
                    background-color: #ffffff !important;
                    color: #0f172a !important;
                    border-color: #e2e8f0 !important;
                }

                .ab4ir-login-field::placeholder {
                    color: #94a3b8 !important;
                    opacity: 1;
                }
            `}</style>

            <section className="relative z-10 grid min-h-svh items-center gap-8 px-5 py-8 md:px-10 lg:grid-cols-[minmax(31rem,0.84fr)_minmax(34rem,1fr)] lg:px-16 xl:px-20 2xl:px-24">
                <div className="hidden h-full min-h-[46rem] max-w-[34rem] flex-col py-9 lg:flex">
                    <Link
                        href={home()}
                        className="flex w-fit items-center gap-6"
                        aria-label="AB4IR ERP home"
                    >
                        <img
                            src="/logo.png"
                            alt=""
                            className="h-20 w-20 object-contain"
                        />
                        <div>
                            <div className="text-3xl font-bold tracking-normal text-white">
                                AB4IR ERP
                            </div>
                        <div className="mt-2 text-sm font-medium uppercase tracking-[0.24em] text-white/[0.7]">
                                Empowering Impact.
                            </div>
                        </div>
                    </Link>

                    <div className="mt-28 pb-4 xl:mt-32">
                        <h1 className="text-[5.35rem] font-bold leading-[0.98] tracking-normal text-white xl:text-[5.8rem]">
                            Welcome
                            <span className="mt-2 block bg-gradient-to-r from-[#ff3d00] via-[#ff7600] to-[#ff9d00] bg-clip-text text-transparent">
                                back
                            </span>
                        </h1>
                        <div className="mt-7 h-1 w-28 rounded-full bg-gradient-to-r from-[#ff3d00] to-[#ff8a00]" />
                        <p className="mt-7 max-w-[24rem] text-[1.55rem] leading-[1.45] text-white/[0.82]">
                            Access your dashboard and continue where you left
                            off.
                        </p>

                        <div className="mt-8 space-y-4">
                            <div className="flex items-center gap-6">
                                <span className="flex size-[4.25rem] items-center justify-center rounded-full border border-white/[0.1] bg-white/[0.08] text-[#ff7900] shadow-[0_22px_50px_rgba(0,0,0,0.32)] backdrop-blur">
                                    <ShieldCheck className="size-8" />
                                </span>
                                <div className="border-b border-white/[0.1] pb-4">
                                    <p className="text-lg font-semibold text-white">
                                        Your data is protected
                                    </p>
                                    <p className="mt-1 text-base text-white/[0.66]">
                                        Enterprise-grade security
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-6">
                                <span className="flex size-[4.25rem] items-center justify-center rounded-full border border-white/[0.1] bg-white/[0.08] text-[#ff7900] shadow-[0_22px_50px_rgba(0,0,0,0.32)] backdrop-blur">
                                    <Zap className="size-8" />
                                </span>
                                <div className="border-b border-white/[0.1] pb-4">
                                    <p className="text-lg font-semibold text-white">
                                        Quick and seamless access
                                    </p>
                                    <p className="mt-1 text-base text-white/[0.66]">
                                        Everything you need, in one place
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-6">
                                <span className="flex size-[4.25rem] items-center justify-center rounded-full border border-white/[0.1] bg-white/[0.08] text-[#ff7900] shadow-[0_22px_50px_rgba(0,0,0,0.32)] backdrop-blur">
                                    <Users className="size-8" />
                                </span>
                                <div>
                                    <p className="text-lg font-semibold text-white">
                                        Built for teams and collaboration
                                    </p>
                                    <p className="mt-1 text-base text-white/[0.66]">
                                        Work together, achieve more
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex w-full justify-center lg:justify-start xl:pl-2">
                    <div className="w-full max-w-[38.75rem] rounded-[1.7rem] border border-white/[0.8] bg-white/[0.92] px-7 py-10 text-slate-950 shadow-[0_30px_90px_rgba(15,23,42,0.12)] backdrop-blur-xl sm:px-10 md:px-12 md:py-12 xl:px-14">
                        <Link
                            href={home()}
                            className="mx-auto flex w-fit items-center justify-center"
                            aria-label="AB4IR ERP home"
                        >
                            <img
                                src="/logo.png"
                                alt=""
                                className="size-24 object-contain md:size-[6.5rem]"
                            />
                        </Link>

                        <div className="mt-8 text-center">
                            <h2 className="text-3xl font-bold tracking-normal text-slate-950">
                                Sign in to your account
                            </h2>
                            <p className="mx-auto mt-4 max-w-[25rem] text-xl leading-8 text-slate-500">
                                Enter your email and password to securely access
                                your account.
                            </p>
                        </div>

                        {status && (
                            <div className="mt-6 rounded-lg border border-emerald-400/[0.2] bg-emerald-400/[0.1] px-4 py-3 text-center text-sm font-medium text-emerald-200">
                                {status}
                            </div>
                        )}

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="mt-11 flex flex-col gap-5 rounded-2xl bg-white/[0.98] dark:bg-white/[0.98]"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-7">
                                        <div className="grid gap-3">
                                            <Label
                                                htmlFor="email"
                                                className="text-base font-semibold text-slate-950"
                                            >
                                                Email address
                                            </Label>
                                            <div className="relative">
                                                <Mail className="pointer-events-none absolute top-1/2 left-5 size-6 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="email"
                                                    placeholder="you@example.com"
                                                    className="ab4ir-login-field h-[4.25rem] rounded-2xl border-slate-200 bg-white pr-5 pl-16 text-xl text-slate-950 shadow-[0_1px_1px_rgba(15,23,42,0.03),inset_0_1px_0_rgba(255,255,255,0.8)] placeholder:text-slate-400 focus-visible:border-orange-400 focus-visible:ring-orange-500/20 dark:border-slate-200 dark:bg-white dark:text-slate-950 dark:placeholder:text-slate-400"
                                                />
                                            </div>
                                            <InputError
                                                message={errors.email}
                                                className="text-red-600"
                                            />
                                        </div>

                                        <div className="grid gap-3">
                                            <div className="flex items-center">
                                                <Label
                                                    htmlFor="password"
                                                    className="text-base font-semibold text-slate-950"
                                                >
                                                    Password
                                                </Label>
                                                {canResetPassword && (
                                                    <TextLink
                                                        href={request()}
                                                        className="ml-auto text-base font-medium text-[#ff3d00] no-underline hover:text-[#e63600]"
                                                        tabIndex={5}
                                                    >
                                                        Forgot password?
                                                    </TextLink>
                                                )}
                                            </div>
                                            <div className="relative">
                                                <LockKeyhole className="pointer-events-none absolute top-1/2 left-5 size-6 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    id="password"
                                                    type={
                                                        showPassword
                                                            ? 'text'
                                                            : 'password'
                                                    }
                                                    name="password"
                                                    required
                                                    tabIndex={2}
                                                    autoComplete="current-password"
                                                    placeholder="Enter your password"
                                                    className="ab4ir-login-field h-[4.25rem] rounded-2xl border-slate-200 bg-white pr-16 pl-16 text-xl text-slate-950 shadow-[0_1px_1px_rgba(15,23,42,0.03),inset_0_1px_0_rgba(255,255,255,0.8)] placeholder:text-slate-400 focus-visible:border-orange-400 focus-visible:ring-orange-500/20 dark:border-slate-200 dark:bg-white dark:text-slate-950 dark:placeholder:text-slate-400"
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute top-1/2 right-5 -translate-y-1/2 rounded-md p-1 text-slate-400 transition hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400/40"
                                                    onClick={() =>
                                                        setShowPassword(
                                                            (visible) =>
                                                                !visible,
                                                        )
                                                    }
                                                    tabIndex={3}
                                                    aria-label={
                                                        showPassword
                                                            ? 'Hide password'
                                                            : 'Show password'
                                                    }
                                                >
                                                    {showPassword ? (
                                                        <EyeOff className="size-5" />
                                                    ) : (
                                                        <Eye className="size-5" />
                                                    )}
                                                </button>
                                            </div>
                                            <InputError
                                                message={errors.password}
                                                className="text-red-600"
                                            />
                                        </div>

                                        <div className="flex items-center space-x-4">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={4}
                                                className="size-6 rounded-md border-slate-200 bg-white data-[state=checked]:border-orange-500 data-[state=checked]:bg-orange-500 dark:border-slate-200 dark:bg-white"
                                            />
                                            <Label
                                                htmlFor="remember"
                                                className="text-base font-medium text-slate-950"
                                            >
                                                Remember me
                                            </Label>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="mt-2 h-[4.25rem] w-full rounded-xl bg-gradient-to-r from-[#ed0018] via-[#ff3d00] to-[#ff7900] text-xl font-bold text-white shadow-[0_18px_45px_rgba(255,71,0,0.22)] hover:from-[#d90016] hover:via-[#f03800] hover:to-[#f37000]"
                                            tabIndex={6}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing && <Spinner />}
                                            <span>Sign in</span>
                                            <ArrowRight className="size-6" />
                                        </Button>
                                    </div>

                                    <div className="flex items-center gap-5 pt-7 text-base text-slate-400">
                                        <div className="h-px flex-1 bg-slate-200" />
                                        <div className="flex items-center gap-2 whitespace-nowrap">
                                            <ShieldCheck className="size-5" />
                                            <span>
                                                Your data is protected
                                            </span>
                                        </div>
                                        <div className="h-px flex-1 bg-slate-200" />
                                    </div>

                                    {canRegister && (
                                        <div className="text-center text-sm text-slate-500">
                                            Don&apos;t have an account?{' '}
                                            <TextLink
                                                href="/register"
                                                className="text-[#ff3d00] no-underline hover:text-[#e63600]"
                                                tabIndex={7}
                                            >
                                                Sign up
                                            </TextLink>
                                        </div>
                                    )}
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </section>
        </main>
    );
}
