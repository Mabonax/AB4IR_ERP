import { Form, Head, Link } from '@inertiajs/react';
import {
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
        <main className="relative min-h-svh overflow-hidden bg-background text-foreground dark:bg-[#07090f] dark:text-white">
            <Head title="Log in" />

            <div
                className="absolute inset-0 bg-cover bg-center"
                style={{ backgroundImage: "url('/auth-bg.png')" }}
                aria-hidden="true"
            />
            <div
                className="absolute inset-0 bg-[radial-gradient(circle_at_74%_42%,rgba(255,111,0,0.16),transparent_30%),linear-gradient(90deg,rgba(5,7,12,0.32),rgba(5,7,12,0.58)_50%,rgba(5,7,12,0.24))]"
                aria-hidden="true"
            />

            <section className="relative z-10 grid min-h-svh items-center gap-10 px-6 py-8 lg:grid-cols-[minmax(18rem,0.78fr)_minmax(27rem,1fr)] lg:px-[4.5rem] xl:px-24">
                <div className="hidden max-w-md pl-3 lg:block xl:pl-8">
                    <div className="pt-100">
                        <h1 className="mt-8 text-3xl leading-tight font-semibold text-white xl:text-4xl">
                            Welcome{' '}
                            <span className="bg-gradient-to-r from-red-500 via-orange-500 to-amber-300 bg-clip-text text-transparent">
                                back
                            </span>
                        </h1>
                        <p className="mt-4 max-w-xs text-base leading-7 text-white/[0.68]">
                            Access your dashboard and continue where you left
                            off.
                        </p>

                        <div className="mt-8 space-y-5">
                            <div className="flex items-center gap-5">
                                <span className="flex size-12 items-center justify-center rounded-full border border-white/[0.05] bg-white/[0.07] text-orange-400 shadow-[0_18px_40px_rgba(0,0,0,0.28)] backdrop-blur">
                                    <ShieldCheck className="size-6" />
                                </span>
                                <div>
                                    <p className="text-base font-semibold">
                                        Secure
                                    </p>
                                    <p className="mt-1 text-sm text-white/[0.58]">
                                        Your data is protected
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-5">
                                <span className="flex size-12 items-center justify-center rounded-full border border-white/[0.05] bg-white/[0.07] text-orange-400 shadow-[0_18px_40px_rgba(0,0,0,0.28)] backdrop-blur">
                                    <Zap className="size-6" />
                                </span>
                                <div>
                                    <p className="text-base font-semibold">
                                        Fast
                                    </p>
                                    <p className="mt-1 text-sm text-white/[0.58]">
                                        Quick and seamless access
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-5">
                                <span className="flex size-12 items-center justify-center rounded-full border border-white/[0.05] bg-white/[0.07] text-orange-400 shadow-[0_18px_40px_rgba(0,0,0,0.28)] backdrop-blur">
                                    <Users className="size-6" />
                                </span>
                                <div>
                                    <p className="text-base font-semibold">
                                        Connected
                                    </p>
                                    <p className="mt-1 text-sm text-white/[0.58]">
                                        Built for teams and collaboration
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex w-full justify-center lg:justify-end">
                    <div className="w-full max-w-[36rem] rounded-[1.5rem] border border-border bg-card/95 px-6 py-7 text-card-foreground shadow-[0_30px_90px_rgba(15,23,42,0.18)] backdrop-blur-xl sm:px-8 md:px-10 md:py-9 xl:px-12 dark:border-white/[0.12] dark:bg-[#11151f]/[0.72] dark:text-white dark:shadow-[0_30px_90px_rgba(0,0,0,0.45)]">
                        <Link
                            href={home()}
                            className="mx-auto flex w-fit items-center justify-center"
                            aria-label="AB4IR ERP home"
                        >
                            <img
                                src="/logo.png"
                                alt=""
                                className="size-18 object-contain md:size-20"
                            />
                        </Link>

                        <div className="mt-5 text-center">
                            <h2 className="text-2xl font-semibold tracking-normal text-foreground dark:text-white">
                                Log in to your account
                            </h2>
                            <p className="mx-auto mt-3 max-w-xs text-base leading-7 text-muted-foreground dark:text-white/[0.58]">
                                Enter your email and password to access your
                                account.
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
                            className="mt-7 flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-5">
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="email"
                                                className="text-base font-medium text-foreground dark:text-white"
                                            >
                                                Email address
                                            </Label>
                                            <div className="relative">
                                                <Mail className="pointer-events-none absolute top-1/2 left-5 size-5 -translate-y-1/2 text-muted-foreground dark:text-white/[0.5]" />
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    name="email"
                                                    required
                                                    autoFocus
                                                    tabIndex={1}
                                                    autoComplete="email"
                                                    placeholder="email@example.com"
                                                    className="h-12 rounded-xl border-input bg-background pr-4 pl-14 text-base text-foreground shadow-[inset_0_1px_0_rgba(15,23,42,0.04)] placeholder:text-muted-foreground focus-visible:border-orange-400/[0.7] focus-visible:ring-orange-500/[0.2] dark:border-white/[0.14] dark:bg-white/[0.035] dark:text-white dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] dark:placeholder:text-white/[0.42]"
                                                />
                                            </div>
                                            <InputError
                                                message={errors.email}
                                                className="text-red-300"
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <div className="flex items-center">
                                                <Label
                                                    htmlFor="password"
                                                    className="text-base font-medium text-foreground dark:text-white"
                                                >
                                                    Password
                                                </Label>
                                                {canResetPassword && (
                                                    <TextLink
                                                        href={request()}
                                                        className="ml-auto text-sm font-medium text-orange-400 no-underline hover:text-orange-300"
                                                        tabIndex={5}
                                                    >
                                                        Forgot password?
                                                    </TextLink>
                                                )}
                                            </div>
                                            <div className="relative">
                                                <LockKeyhole className="pointer-events-none absolute top-1/2 left-5 size-5 -translate-y-1/2 text-muted-foreground dark:text-white/[0.5]" />
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
                                                    placeholder="Password"
                                                    className="h-12 rounded-xl border-input bg-background pr-14 pl-14 text-base text-foreground shadow-[inset_0_1px_0_rgba(15,23,42,0.04)] placeholder:text-muted-foreground focus-visible:border-orange-400/[0.7] focus-visible:ring-orange-500/[0.2] dark:border-white/[0.14] dark:bg-white/[0.035] dark:text-white dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.04)] dark:placeholder:text-white/[0.42]"
                                                />
                                                <button
                                                    type="button"
                                                    className="absolute top-1/2 right-5 -translate-y-1/2 rounded-md p-1 text-muted-foreground transition hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-400/[0.4] dark:text-white/[0.55] dark:hover:text-white"
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
                                                className="text-red-300"
                                            />
                                        </div>

                                        <div className="flex items-center space-x-3">
                                            <Checkbox
                                                id="remember"
                                                name="remember"
                                                tabIndex={4}
                                                className="size-5 border-input bg-background data-[state=checked]:border-orange-500 data-[state=checked]:bg-orange-500 dark:border-white/[0.15] dark:bg-white/[0.035]"
                                            />
                                            <Label
                                                htmlFor="remember"
                                                className="text-base font-normal text-foreground dark:text-white"
                                            >
                                                Remember me
                                            </Label>
                                        </div>

                                        <Button
                                            type="submit"
                                            className="mt-3 h-14 w-full rounded-xl bg-gradient-to-r from-red-600 via-orange-600 to-orange-500 text-base font-semibold text-white shadow-[0_18px_50px_rgba(255,71,0,0.26)] hover:from-red-500 hover:via-orange-500 hover:to-amber-500"
                                            tabIndex={6}
                                            disabled={processing}
                                            data-test="login-button"
                                        >
                                            {processing && <Spinner />}
                                            Log in
                                        </Button>
                                    </div>

                                    <div className="flex items-center gap-5 pt-4 text-sm text-muted-foreground dark:text-white/[0.52]">
                                        <div className="h-px flex-1 bg-border dark:bg-white/[0.1]" />
                                        <div className="flex items-center gap-2 whitespace-nowrap">
                                            <ShieldCheck className="size-5" />
                                            <span>
                                                Your data is protected
                                            </span>
                                        </div>
                                        <div className="h-px flex-1 bg-border dark:bg-white/[0.1]" />
                                    </div>

                                    {canRegister && (
                                        <div className="text-center text-sm text-muted-foreground dark:text-white/[0.58]">
                                            Don&apos;t have an account?{' '}
                                            <TextLink
                                                href="/register"
                                                className="text-orange-400 no-underline hover:text-orange-300"
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
