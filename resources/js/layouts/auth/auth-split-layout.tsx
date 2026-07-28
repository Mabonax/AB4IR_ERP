import { Link, usePage } from '@inertiajs/react';
import { ClipboardList, ShieldCheck, UsersRound, type LucideIcon } from 'lucide-react';
import { type PropsWithChildren } from 'react';

import BrandMark from '@/components/brand-mark';
import { getBrandIdentity } from '@/lib/brand';
import { home } from '@/routes';
import { type SharedData } from '@/types';

type DarkPanelItem = {
    label: string;
    icon: LucideIcon;
};

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

const darkPanelItems: DarkPanelItem[] = [
    {
        label: 'Programme and project visibility',
        icon: ClipboardList,
    },
    {
        label: 'Governance, compliance, and approvals',
        icon: ShieldCheck,
    },
    {
        label: 'Staff, stakeholders, and field operations',
        icon: UsersRound,
    },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    const shared = usePage<SharedData>().props;
    const { name, tagline } = getBrandIdentity(shared);
    const darkPanelLogo = '/logos/POA%20logo%203.png';

    return (
        <div className="relative grid min-h-dvh bg-[#F7F7F7] text-[#111111] [color-scheme:light] lg:max-w-none lg:grid-cols-[1.12fr_minmax(30rem,0.88fr)]">
            <div className="relative hidden min-h-dvh overflow-hidden border-r border-white/10 bg-[#111111] text-white lg:flex">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(215,25,32,0.24),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(200,16,46,0.22),_transparent_36%)]" />
                <div className="absolute inset-0 bg-[linear-gradient(180deg,_rgba(17,17,17,0.7)_0%,_rgba(17,17,17,0.9)_100%)]" />
                <Link
                    href={home()}
                    className="absolute left-10 top-10 z-20 flex items-center text-lg font-medium"
                >
                    <img src={darkPanelLogo} alt={`${name} logo`} className="h-16 w-16 object-contain" />
                </Link>
                <div className="relative z-20 flex h-full flex-col justify-between p-10">
                    <div />
                    <div className="max-w-xl space-y-6">
                        <p className="text-xs font-semibold uppercase tracking-[0.36em] !text-[#FCA5A5]">
                            Programme of Action
                        </p>
                        <h1 className="text-5xl font-semibold leading-[1.05] tracking-[-0.03em] text-white">
                            Internal operations for delivery, governance, and institutional coordination.
                        </h1>
                        <p className="max-w-lg text-base leading-7 !text-white/72">
                            {description ?? tagline}
                        </p>
                    </div>
                    <div className="grid max-w-2xl gap-4 xl:grid-cols-3">
                        {darkPanelItems.map(({ label, icon: Icon }) => (
                            <div
                                key={label}
                                className="border border-white/10 bg-white/5 p-5 backdrop-blur-sm"
                            >
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-white/15 bg-white/8 text-[#FCA5A5]">
                                    <Icon className="h-5 w-5" aria-hidden="true" strokeWidth={2.2} />
                                </div>
                                <p className="text-sm leading-6 !text-white/78">{label}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
            <div className="relative flex min-h-dvh w-full items-center justify-center px-6 py-10 sm:px-8 lg:px-12">
                <div className="absolute inset-x-0 top-0 h-40 bg-[linear-gradient(180deg,_rgba(243,244,246,0.85)_0%,_rgba(247,247,247,0)_100%)]" />
                <div className="relative mx-auto flex w-full max-w-[30rem] flex-col justify-center space-y-7">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <BrandMark
                            iconClassName="h-14 w-auto object-contain"
                            showWordmark={false}
                            variant="horizontal"
                        />
                    </Link>
                    <div className="border border-[#E5E7EB] bg-white p-8 text-[#111111] shadow-[0_24px_80px_rgba(17,17,17,0.08)] sm:p-10">
                        <div className="flex flex-col items-start gap-2 text-left">
                            <p className="text-xs font-semibold uppercase tracking-[0.32em] !text-[#C8102E]">
                                Secure access
                            </p>
                            <h1 className="text-3xl font-semibold tracking-[-0.03em] text-[#111111]">
                                {title}
                            </h1>
                            <p className="text-sm leading-6 text-balance !text-[#4B5563]">
                                {description ?? tagline ?? name}
                            </p>
                        </div>
                        <div className="mt-8">{children}</div>
                        <div className="mt-8 border-t border-[#ECECEC] pt-5 text-xs leading-5 !text-[#6B7280]">
                            Programme of Action ERP is the operational extension of the POA institutional platform.
                        </div>
                    </div>
                    <p className="px-2 text-center text-xs uppercase tracking-[0.22em] !text-[#9CA3AF]">
                        Programme of Action internal workspace
                    </p>
                </div>
            </div>
        </div>
    );
}
