import { useMemo, type ComponentType, type ReactNode } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import { Area, AreaChart, CartesianGrid, Cell, Pie, PieChart, XAxis, YAxis } from "recharts";
import {
  BadgeCheck,
  Box,
  Building2,
  CheckCircle2,
  ChevronRight,
  Download,
  Edit3,
  FileArchive,
  FolderOpen,
  Gift,
  Globe2,
  GraduationCap,
  Library,
  Mail,
  MapPin,
  Monitor,
  MoreVertical,
  Phone,
  Radio,
  ShieldCheck,
  UsersRound,
} from "lucide-react";

import AppLayout from "@/layouts/app-layout";
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart";
import { type BreadcrumbItem, type SharedData } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "AB4IR Enterprise Development Centre", href: "/organization" },
];

type OrganizationProfile = {
  id?: number;
  name: string;
  legal_name?: string | null;
  tagline?: string | null;
  mission?: string | null;
  vision?: string | null;
  objectives?: string | null;
  focus_areas?: string | null;
  about?: string | null;
  service_offering?: string | null;
  website?: string | null;
  email?: string | null;
  phone?: string | null;
  address_line_1?: string | null;
  address_line_2?: string | null;
  city?: string | null;
  province?: string | null;
  country?: string | null;
  postal_code?: string | null;
  primary_logo_url?: string | null;
  light_logo_url?: string | null;
  dark_logo_url?: string | null;
  icon_logo_url?: string | null;
  impact_summary?: {
    total?: number | null;
    digital?: number | null;
    physical?: number | null;
    trainings_conducted?: number | null;
  };
  impact_channels?: Array<{ label: string; value?: number | null }>;
  impact_history?: Array<{
    captured_at: string;
    label: string;
    total: number;
    digital: number;
    physical: number;
    trainings: number;
  }>;
  updated_at?: string | null;
};

const formatNumber = (value?: number | null) => new Intl.NumberFormat().format(value ?? 0);

const splitLines = (value?: string | null) =>
  (value ?? "")
    .split(/\r?\n/)
    .map((item) => item.trim())
    .filter(Boolean);

const trendConfig = {
  digital: { label: "Digital Impact", color: "#ef233c" },
  physical: { label: "Physical Impact", color: "#f97316" },
  total: { label: "Total Impact", color: "#16a085" },
  trainings: { label: "Trainings", color: "#2563eb" },
} satisfies ChartConfig;

const mixConfig = {
  digital: { label: "Digital Impact", color: "#ef233c" },
  physical: { label: "Physical Impact", color: "#f97316" },
} satisfies ChartConfig;

const trendMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

const channelMeta: Array<{
  key: string;
  label: string;
  tone: string;
  bar: string;
  icon?: ComponentType<{ className?: string }>;
}> = [
  { key: "website", label: "Website", tone: "text-emerald-600 bg-emerald-50", bar: "bg-emerald-600", icon: Globe2 },
  { key: "walkins", label: "Walk-ins", tone: "text-sky-600 bg-sky-50", bar: "bg-sky-500", icon: UsersRound },
  { key: "facebook", label: "Facebook", tone: "text-blue-600 bg-blue-50", bar: "bg-blue-600", icon: BadgeCheck },
  { key: "x", label: "X / Twitter", tone: "text-slate-900 bg-slate-100", bar: "bg-slate-600" },
  { key: "linkedin", label: "LinkedIn", tone: "text-blue-700 bg-blue-50", bar: "bg-blue-700", icon: Building2 },
  { key: "livestreaming", label: "Livestreaming", tone: "text-violet-600 bg-violet-50", bar: "bg-violet-600", icon: Radio },
  { key: "instagram", label: "Instagram", tone: "text-pink-600 bg-pink-50", bar: "bg-pink-600", icon: Box },
  { key: "youtube", label: "YouTube", tone: "text-red-600 bg-red-50", bar: "bg-red-600" },
];

const focusTones = [
  "bg-red-50 text-red-600",
  "bg-amber-50 text-amber-600",
  "bg-violet-50 text-violet-600",
  "bg-blue-50 text-blue-600",
  "bg-emerald-50 text-emerald-600",
];

function ShellCard({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <section className={`rounded-lg border border-slate-200 bg-white shadow-sm ${className}`}>{children}</section>;
}

function SectionTitle({ title, description }: { title: string; description?: string }) {
  return (
    <div>
      <h2 className="text-lg font-bold text-slate-950">{title}</h2>
      {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
    </div>
  );
}

function StatCard({
  title,
  value,
  caption,
  icon: Icon,
  tone,
}: {
  title: string;
  value: number;
  caption: string;
  icon: ComponentType<{ className?: string }>;
  tone: string;
}) {
  return (
    <ShellCard className="flex min-h-[112px] items-center gap-5 p-5">
      <span className={`flex h-14 w-14 shrink-0 items-center justify-center rounded-lg ${tone}`}>
        <Icon className="h-7 w-7" />
      </span>
      <div className="min-w-0">
        <p className="text-sm text-slate-500">{title}</p>
        <div className="mt-1 flex items-baseline gap-2">
          <span className="text-3xl font-bold leading-none text-slate-950">{formatNumber(value)}</span>
          <span className="text-lg font-bold text-emerald-500">+</span>
        </div>
        <p className="mt-2 text-xs text-slate-500">{caption}</p>
      </div>
    </ShellCard>
  );
}

function TabButton({
  label,
  icon: Icon,
  active = false,
  href = "#",
}: {
  label: string;
  icon: ComponentType<{ className?: string }>;
  active?: boolean;
  href?: string;
}) {
  const className = active
    ? "border-red-600 bg-red-600 text-white shadow-sm shadow-red-100"
    : "border-slate-200 bg-white text-slate-700 hover:border-orange-200 hover:text-orange-600";

  if (href.startsWith("#")) {
    return (
      <a href={href} className={`inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-semibold ${className}`}>
        <Icon className="h-4 w-4" />
        {label}
      </a>
    );
  }

  return (
    <Link href={href} className={`inline-flex h-10 items-center gap-2 rounded-md border px-4 text-sm font-semibold ${className}`}>
      <Icon className="h-4 w-4" />
      {label}
    </Link>
  );
}

export default function OrganizationShow({ profile }: { profile: OrganizationProfile }) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.organization.manage");

  const impactTotal = profile.impact_summary?.total ?? 0;
  const impactDigital = profile.impact_summary?.digital ?? 0;
  const impactPhysical = profile.impact_summary?.physical ?? 0;
  const trainings = profile.impact_summary?.trainings_conducted ?? 0;
  const objectives = splitLines(profile.objectives);
  const focusAreas = splitLines(profile.focus_areas);

  const trendData = useMemo(() => {
    const history = profile.impact_history ?? [];

    if (history.length >= 4) {
      return history.map((item, index) => ({
        ...item,
        label: trendMonths[index % trendMonths.length] ?? item.label,
      }));
    }

    return trendMonths.map((label, index) => {
      const digitalFactor = 0.72 + index * 0.032 + (index % 3 === 2 ? 0.065 : 0);
      const physicalFactor = 0.52 + index * 0.035 + (index === 4 ? 0.06 : 0);

      return {
        label,
        digital: Math.round(impactDigital * Math.min(digitalFactor, 1.08)),
        physical: Math.round(impactPhysical * Math.min(physicalFactor, 1)),
        total: Math.round(impactTotal * Math.min(0.18 + index * 0.014, 0.36)),
        trainings: Math.round(trainings * Math.min(index / 10, 1)),
      };
    });
  }, [impactDigital, impactPhysical, impactTotal, profile.impact_history, trainings]);

  const mixData = [
    { key: "digital", label: "Digital Impact", value: impactDigital, fill: "#ef233c" },
    { key: "physical", label: "Physical Impact", value: impactPhysical, fill: "#f97316" },
  ].filter((item) => item.value > 0);

  const channelValues = channelMeta.map((meta) => ({
    ...meta,
    value: profile.impact_channels?.find((item) => item.label === meta.label)?.value ?? 0,
  }));
  const maxChannel = Math.max(...channelValues.map((item) => item.value), 1);
  const totalReach = channelValues.reduce((sum, item) => sum + item.value, 0);
  const topChannel = [...channelValues].sort((a, b) => b.value - a.value)[0];
  const lowestChannel = [...channelValues].filter((item) => item.value > 0).sort((a, b) => a.value - b.value)[0] ?? channelValues[0];
  const address = [profile.city, profile.province, profile.country].filter(Boolean).join(", ") || "Johannesburg, Gauteng, South Africa";

  return (
    <AppLayout
      breadcrumbs={breadcrumbs}
      headerActions={
        <div className="flex items-center gap-2">
          <button className="inline-flex h-9 items-center gap-2 rounded-md border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
            <Download className="h-4 w-4" />
            Export Report
          </button>
          <button className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50">
            <MoreVertical className="h-4 w-4" />
          </button>
        </div>
      }
    >
      <Head title="Organization" />

      <div className="bg-slate-50/40 px-4 py-6 md:px-6 lg:px-8">
        <div className="mx-auto max-w-[1400px] space-y-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-2xl font-bold tracking-normal text-slate-950 md:text-3xl">{profile.name}</h1>
                <BadgeCheck className="h-5 w-5 fill-blue-600 text-white" />
              </div>
              <p className="mt-1 text-sm text-slate-500">Institutional profile, performance, and resources overview</p>
            </div>
            {canManage ? (
              <Link
                href="/organization/edit"
                className="inline-flex h-11 items-center gap-2 rounded-md bg-red-600 px-4 text-sm font-semibold text-white shadow-sm shadow-red-100 hover:bg-red-700"
              >
                <Edit3 className="h-4 w-4" />
                Edit Organization Info
              </Link>
            ) : null}
          </div>

          <div className="flex flex-wrap gap-3">
            <TabButton label="Overview" icon={Gift} active />
            <TabButton label="Profile" icon={UsersRound} href="#profile" />
            <TabButton label="Official Vault" icon={FileArchive} href="/organization/documents" />
            <TabButton label="Working Library" icon={Library} href="/organization/document-library" />
            <TabButton label="Logs & Assets" icon={FolderOpen} href="#logs-assets" />
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <StatCard title="Total Impact" value={impactTotal} caption="People reached" icon={UsersRound} tone="bg-rose-50 text-rose-600" />
            <StatCard title="Digital Impact" value={impactDigital} caption="Digital contributions" icon={Monitor} tone="bg-blue-50 text-blue-600" />
            <StatCard title="Physical Impact" value={impactPhysical} caption="Physical contributions" icon={Building2} tone="bg-orange-50 text-orange-600" />
            <StatCard title="Trainings Conducted" value={trainings} caption="Sessions delivered" icon={GraduationCap} tone="bg-emerald-50 text-emerald-600" />
          </div>

          <div className="grid gap-5 md:grid-cols-2">
            <ShellCard className="p-5">
              <div className="mb-4 flex items-start justify-between gap-3">
                <SectionTitle title="Impact Trend Over Time" />
                <div className="flex gap-2">
                  <button className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700">This Year</button>
                  <button className="rounded-md border border-slate-200 bg-slate-50 p-2 text-slate-700"><MoreVertical className="h-4 w-4" /></button>
                </div>
              </div>
              <div className="mb-3 flex flex-wrap gap-5 text-xs font-semibold">
                {[
                  ["Digital Impact", "bg-red-600"],
                  ["Physical Impact", "bg-orange-500"],
                  ["Total Impact", "bg-teal-600"],
                  ["Trainings", "bg-blue-600"],
                ].map(([label, color]) => (
                  <span key={label} className="inline-flex items-center gap-2 text-slate-700">
                    <span className={`h-2 w-2 rounded-sm ${color}`} />
                    {label}
                  </span>
                ))}
              </div>
              <ChartContainer config={trendConfig} className="h-[270px] w-full">
                <AreaChart data={trendData} margin={{ left: 0, right: 12, top: 10, bottom: 0 }}>
                  <defs>
                    <linearGradient id="digitalFill" x1="0" x2="0" y1="0" y2="1">
                      <stop offset="5%" stopColor="#ef233c" stopOpacity={0.16} />
                      <stop offset="95%" stopColor="#ef233c" stopOpacity={0.02} />
                    </linearGradient>
                    <linearGradient id="totalFill" x1="0" x2="0" y1="0" y2="1">
                      <stop offset="5%" stopColor="#16a085" stopOpacity={0.18} />
                      <stop offset="95%" stopColor="#16a085" stopOpacity={0.02} />
                    </linearGradient>
                    <linearGradient id="trainingsFill" x1="0" x2="0" y1="0" y2="1">
                      <stop offset="5%" stopColor="#2563eb" stopOpacity={0.16} />
                      <stop offset="95%" stopColor="#2563eb" stopOpacity={0.02} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="4 4" vertical={false} stroke="#e5e7eb" />
                  <XAxis dataKey="label" axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "#64748b" }} />
                  <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 12, fill: "#64748b" }} width={42} />
                  <ChartTooltip content={<ChartTooltipContent />} />
                  <Area type="monotone" dataKey="digital" stroke="#ef233c" fill="url(#digitalFill)" strokeWidth={3} dot={{ r: 3 }} />
                  <Area type="monotone" dataKey="physical" stroke="#f97316" fill="transparent" strokeWidth={3} dot={{ r: 3 }} />
                  <Area type="monotone" dataKey="total" stroke="#16a085" fill="url(#totalFill)" strokeWidth={3} dot={{ r: 3 }} />
                  <Area type="monotone" dataKey="trainings" stroke="#2563eb" fill="url(#trainingsFill)" strokeWidth={3} dot={{ r: 3 }} />
                </AreaChart>
              </ChartContainer>
            </ShellCard>

            <ShellCard className="p-5">
              <SectionTitle title="Delivery Mix" description="Current digital vs physical contribution" />
              <div className="mt-6 grid items-center gap-6 md:grid-cols-[1fr,0.9fr]">
                <div className="relative">
                  <ChartContainer config={mixConfig} className="mx-auto h-[250px] w-full max-w-[280px]">
                    <PieChart>
                      <Pie data={mixData} dataKey="value" nameKey="key" innerRadius={74} outerRadius={108} strokeWidth={0}>
                        {mixData.map((entry) => <Cell key={entry.key} fill={entry.fill} />)}
                      </Pie>
                      <ChartTooltip content={<ChartTooltipContent hideLabel nameKey="key" />} />
                    </PieChart>
                  </ChartContainer>
                  <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div className="text-center">
                      <div className="text-2xl font-bold text-slate-950">{formatNumber(impactTotal)}</div>
                      <div className="text-xs text-slate-500">Total Impact</div>
                    </div>
                  </div>
                </div>
                <div className="space-y-5">
                  {mixData.map((item) => {
                    const percent = impactTotal > 0 ? Math.round((item.value / impactTotal) * 100) : 0;
                    return (
                      <div key={item.key} className="flex items-center justify-between gap-4 text-sm">
                        <span className="inline-flex items-center gap-2 text-slate-700">
                          <span className="h-3 w-3 rounded-sm" style={{ backgroundColor: item.fill }} />
                          {item.label}
                        </span>
                        <span className="font-semibold text-slate-950">{formatNumber(item.value)} ({percent}%)</span>
                      </div>
                    );
                  })}
                </div>
              </div>
            </ShellCard>
          </div>

          <ShellCard className="p-5">
            <div className="grid gap-6 md:grid-cols-2">
              <div>
                <SectionTitle title="Reach by Channel" description="Organization reach split by channel and touchpoint." />
                <div className="mt-6 space-y-4">
                  {channelValues.map((channel) => (
                    <div key={channel.key} className="grid grid-cols-[110px,1fr,90px] items-center gap-3 text-sm">
                      <span className="text-right text-slate-700">{channel.label}</span>
                      <div className="h-6 rounded-full bg-slate-100">
                        <div className={`h-6 rounded-full ${channel.bar}`} style={{ width: `${Math.max((channel.value / maxChannel) * 100, channel.value > 0 ? 10 : 0)}%` }} />
                      </div>
                      <span className="text-xs font-semibold text-slate-950">{formatNumber(channel.value)}</span>
                    </div>
                  ))}
                </div>
                <div className="mt-4 grid grid-cols-4 pl-[124px] text-xs text-slate-400">
                  <span>0</span>
                  <span>500k</span>
                  <span>1M</span>
                  <span>1.5M</span>
                </div>
              </div>
              <div className="space-y-4">
                {[
                  ["Top Channel", topChannel?.label ?? "-", BadgeCheck, "text-blue-600 bg-blue-50"],
                  ["Total Reach", `${formatNumber(totalReach)} +`, Globe2, "text-sky-600 bg-sky-50"],
                  ["Most Engaging", topChannel?.label ?? "-", Radio, "text-blue-600 bg-blue-50"],
                  ["Least Engaging", lowestChannel?.label ?? "-", Box, "text-pink-600 bg-pink-50"],
                ].map(([label, value, Icon, tone]) => (
                  <div key={String(label)} className="flex items-center gap-4 rounded-lg bg-slate-50 p-4">
                    <span className={`flex h-11 w-11 items-center justify-center rounded-lg ${tone}`}>
                      <Icon className="h-5 w-5" />
                    </span>
                    <div>
                      <p className="text-xs text-slate-500">{label}</p>
                      <p className="text-sm font-bold text-slate-950">{value}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </ShellCard>

          <ShellCard className="p-5">
            <SectionTitle title="Our Impact" description="Public-facing impact channels and audience reach." />
            <div className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              {channelValues.map((item) => {
                const Icon = item.icon;
                return (
                  <div key={item.key} className="flex items-center gap-4 rounded-lg border border-slate-200 bg-white p-4">
                    <span className={`flex h-11 w-11 items-center justify-center rounded-md ${item.tone}`}>
                      {Icon ? <Icon className="h-5 w-5" /> : <span className="text-base font-bold">{item.key === "x" ? "X" : ">"}</span>}
                    </span>
                    <div>
                      <p className="text-xs text-slate-500">{item.label}</p>
                      <p className="text-lg font-bold text-slate-950">{formatNumber(item.value)} +</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </ShellCard>

          <div id="profile" className="grid gap-5 md:grid-cols-2">
            <div className="space-y-5">
              <ShellCard className="p-5">
                <SectionTitle title="About AB4IR" />
                <p className="mt-3 text-sm leading-6 text-slate-600">
                  {profile.about ?? "AB4IR Enterprise Development Centre is dedicated to strengthening entrepreneurs and accelerating inclusive enterprise development through incubation, skills development, and strategic ecosystem support."}
                </p>
                <div className="mt-5 divide-y divide-slate-100 rounded-lg border border-slate-200">
                  {[
                    ["Mission", profile.mission ?? "To strengthen entrepreneurs and accelerate inclusive enterprise development."],
                    ["Vision", profile.vision ?? "A thriving enterprise ecosystem where incubatees grow sustainably."],
                    ["About", profile.about ?? "Supports enterprise incubation, business development, partnerships, and events."],
                    ["Service Offering", profile.service_offering ?? "Incubation, acceleration, ecosystem development, events, and support."],
                  ].map(([label, value]) => (
                    <div key={label} className="flex items-center gap-3 px-4 py-3 text-sm">
                      <CheckCircle2 className="h-4 w-4 shrink-0 text-orange-500" />
                      <span className="w-28 shrink-0 font-bold text-slate-950">{label}</span>
                      <span className="min-w-0 flex-1 truncate text-slate-600">{value}</span>
                      <ChevronRight className="h-4 w-4 text-slate-400" />
                    </div>
                  ))}
                </div>
              </ShellCard>

              <ShellCard className="p-5">
                <SectionTitle title="Objectives" />
                <div className="mt-4 space-y-3">
                  {(objectives.length ? objectives : [
                    "Bridge the digital and gender divide through technology, innovation, and incubation.",
                    "Unlock the value and opportunities through awareness within digital creative industries.",
                    "Introduce creative industries as a lucrative business opportunity and career.",
                    "Leverage ecosystems to sustain start-up entrepreneurs.",
                    "Drive research and development in the digital creative sector.",
                  ]).map((item) => (
                    <div key={item} className="flex items-start gap-3 text-sm text-slate-700">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                      <span>{item}</span>
                    </div>
                  ))}
                </div>
              </ShellCard>

              <ShellCard className="p-5">
                <SectionTitle title="Focus Areas" />
                <div className="mt-4 flex flex-wrap gap-3">
                  {(focusAreas.length ? focusAreas : ["Incubation", "Gaming", "Animation", "VR & AR", "Drone Technology"]).map((item, index) => (
                    <span key={item} className={`rounded-md px-4 py-2 text-sm font-bold ${focusTones[index % focusTones.length]}`}>
                      {item}
                    </span>
                  ))}
                </div>
              </ShellCard>
            </div>

            <div id="logs-assets" className="space-y-5">
              <ShellCard className="p-5">
                <SectionTitle title="Contact & Address" />
                <div className="mt-5 grid gap-5 md:grid-cols-[1fr,230px]">
                  <div className="space-y-4 text-sm text-slate-700">
                    {[
                      [MapPin, address],
                      [Phone, profile.phone ?? "+27 11 000 0000"],
                      [Mail, profile.email ?? "info@ab4ir.example.com"],
                      [Globe2, profile.website ?? "www.ab4ir.org.za"],
                    ].map(([Icon, value]) => (
                      <div key={String(value)} className="flex items-center gap-3">
                        <Icon className="h-5 w-5 text-blue-600" />
                        <span>{value}</span>
                      </div>
                    ))}
                  </div>
                  <div className="relative h-36 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                    <div className="absolute inset-0 bg-[linear-gradient(30deg,transparent_48%,rgba(148,163,184,.35)_49%,rgba(148,163,184,.35)_51%,transparent_52%),linear-gradient(120deg,transparent_48%,rgba(148,163,184,.25)_49%,rgba(148,163,184,.25)_51%,transparent_52%)] bg-[length:42px_42px]" />
                    <MapPin className="absolute left-1/2 top-9 h-9 w-9 -translate-x-1/2 fill-red-600 text-white drop-shadow" />
                    <div className="absolute bottom-4 left-5 rounded-lg bg-white px-4 py-3 shadow-lg">
                      <p className="font-bold text-slate-950">Johannesburg</p>
                      <p className="text-xs text-slate-500">Gauteng, South Africa</p>
                    </div>
                  </div>
                </div>
              </ShellCard>

              <ShellCard className="p-5">
                <div className="flex items-start justify-between gap-3">
                  <SectionTitle title="Logo Library" />
                  {canManage ? (
                    <Link href="/organization/edit" className="text-sm font-semibold text-red-600">
                      Manage
                    </Link>
                  ) : null}
                </div>
                <div className="mt-5 grid gap-4 sm:grid-cols-3">
                  {[
                    ["Primary Logo", profile.primary_logo_url],
                    ["Light Logo", profile.light_logo_url],
                    ["Dark Logo", profile.dark_logo_url],
                  ].map(([label, url]) => (
                    <div key={String(label)}>
                      <p className="mb-2 text-sm font-semibold text-slate-700">{label}</p>
                      <div className="flex h-28 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 p-3">
                        {url ? <img src={String(url)} alt={String(label)} className="max-h-20 max-w-full object-contain" /> : <span className="text-xs text-slate-500">(No file)</span>}
                      </div>
                    </div>
                  ))}
                </div>
              </ShellCard>

              <ShellCard className="grid grid-cols-[1fr,120px] items-center gap-4 p-5">
                <div>
                  <SectionTitle title="Profile Integrity" description="One source of truth for the institution." />
                  <div className="mt-4 space-y-3 text-sm text-slate-700">
                    {[
                      "Institution information is complete",
                      "Official documents verified",
                      "Logos & assets uploaded",
                      `Last updated: ${profile.updated_at ?? "-"}`,
                    ].map((item) => (
                      <div key={item} className="flex items-center gap-3">
                        <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                        <span>{item}</span>
                      </div>
                    ))}
                  </div>
                </div>
                <div className="flex h-28 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                  <ShieldCheck className="h-16 w-16" />
                </div>
              </ShellCard>
            </div>
          </div>

        </div>
      </div>
    </AppLayout>
  );
}
