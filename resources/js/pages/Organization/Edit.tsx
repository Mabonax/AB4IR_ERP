import { Head, Link, useForm } from "@inertiajs/react";
import {
  ArrowLeft,
  Building2,
  CheckCircle2,
  Globe2,
  Mail,
  MapPin,
  Monitor,
  Phone,
  Save,
  Upload,
  UsersRound,
} from "lucide-react";
import { type ComponentType, type ReactNode } from "react";

import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
  { title: "Edit", href: "/organization/edit" },
];

type OrganizationProfile = {
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
};

function Panel({
  title,
  description,
  children,
}: {
  title: string;
  description?: string;
  children: ReactNode;
}) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
      <div className="mb-5">
        <h2 className="text-lg font-bold text-slate-950">{title}</h2>
        {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
      </div>
      {children}
    </section>
  );
}

function Field({
  label,
  children,
}: {
  label: string;
  children: ReactNode;
}) {
  return (
    <label className="block text-sm font-semibold text-slate-700">
      {label}
      {children}
    </label>
  );
}

const inputClass = "mt-2 w-full rounded-md border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal text-slate-900 shadow-sm outline-none transition focus:border-red-300 focus:ring-2 focus:ring-red-100";
const textareaClass = `${inputClass} min-h-28 resize-y`;

export default function OrganizationEdit({ profile }: { profile: OrganizationProfile }) {
  const profileForm = useForm({
    name: profile.name ?? "",
    legal_name: profile.legal_name ?? "",
    tagline: profile.tagline ?? "",
    mission: profile.mission ?? "",
    vision: profile.vision ?? "",
    objectives: profile.objectives ?? "",
    focus_areas: profile.focus_areas ?? "",
    about: profile.about ?? "",
    service_offering: profile.service_offering ?? "",
    website: profile.website ?? "",
    email: profile.email ?? "",
    phone: profile.phone ?? "",
    address_line_1: profile.address_line_1 ?? "",
    address_line_2: profile.address_line_2 ?? "",
    city: profile.city ?? "",
    province: profile.province ?? "",
    country: profile.country ?? "",
    postal_code: profile.postal_code ?? "",
    impact_total: profile.impact_summary?.total?.toString() ?? "",
    impact_digital: profile.impact_summary?.digital?.toString() ?? "",
    impact_physical: profile.impact_summary?.physical?.toString() ?? "",
    trainings_conducted: profile.impact_summary?.trainings_conducted?.toString() ?? "",
    impact_website: profile.impact_channels?.find((item) => item.label === "Website")?.value?.toString() ?? "",
    impact_walkins: profile.impact_channels?.find((item) => item.label === "Walk-ins")?.value?.toString() ?? "",
    impact_facebook: profile.impact_channels?.find((item) => item.label === "Facebook")?.value?.toString() ?? "",
    impact_x: profile.impact_channels?.find((item) => item.label === "X / Twitter")?.value?.toString() ?? "",
    impact_linkedin: profile.impact_channels?.find((item) => item.label === "LinkedIn")?.value?.toString() ?? "",
    impact_livestreaming: profile.impact_channels?.find((item) => item.label === "Livestreaming")?.value?.toString() ?? "",
    impact_instagram: profile.impact_channels?.find((item) => item.label === "Instagram")?.value?.toString() ?? "",
    impact_youtube: profile.impact_channels?.find((item) => item.label === "YouTube")?.value?.toString() ?? "",
  });
  const impactFields: Array<{
    key: keyof typeof profileForm.data;
    label: string;
    Icon: ComponentType<{ className?: string }>;
  }> = [
    { key: "impact_total", label: "Total Impact", Icon: UsersRound },
    { key: "impact_digital", label: "Digital Impact", Icon: Monitor },
    { key: "impact_physical", label: "Physical Impact", Icon: Building2 },
    { key: "trainings_conducted", label: "Trainings Conducted", Icon: CheckCircle2 },
    { key: "impact_website", label: "Website", Icon: Globe2 },
    { key: "impact_walkins", label: "Walk-ins", Icon: MapPin },
    { key: "impact_facebook", label: "Facebook", Icon: UsersRound },
    { key: "impact_x", label: "X / Twitter", Icon: UsersRound },
    { key: "impact_linkedin", label: "LinkedIn", Icon: UsersRound },
    { key: "impact_livestreaming", label: "Livestreaming", Icon: UsersRound },
    { key: "impact_instagram", label: "Instagram", Icon: UsersRound },
    { key: "impact_youtube", label: "YouTube", Icon: UsersRound },
  ];
  const strategyFields: Array<{
    key: "mission" | "vision" | "service_offering" | "objectives" | "focus_areas";
    label: string;
  }> = [
    { key: "mission", label: "Mission" },
    { key: "vision", label: "Vision" },
    { key: "service_offering", label: "Service Offering" },
    { key: "objectives", label: "Objectives" },
    { key: "focus_areas", label: "Focus Areas" },
  ];
  const logoFields: Array<{
    key: "primary_logo" | "light_logo" | "dark_logo" | "icon_logo";
    label: string;
    url: string | null | undefined;
  }> = [
    { key: "primary_logo", label: "Primary Logo", url: profile.primary_logo_url },
    { key: "light_logo", label: "Light Version", url: profile.light_logo_url },
    { key: "dark_logo", label: "Dark Version", url: profile.dark_logo_url },
    { key: "icon_logo", label: "Icon Version", url: profile.icon_logo_url },
  ];

  const logoForm = useForm<{
    primary_logo: File | null;
    light_logo: File | null;
    dark_logo: File | null;
    icon_logo: File | null;
  }>({
    primary_logo: null,
    light_logo: null,
    dark_logo: null,
    icon_logo: null,
  });

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Edit Organization" />

      <div className="bg-slate-50/40 px-4 py-6 md:px-6 lg:px-8">
        <div className="mx-auto max-w-[1400px] space-y-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <Link href="/organization" className="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-red-600">
                <ArrowLeft className="h-4 w-4" />
                Back to organization
              </Link>
              <h1 className="mt-3 text-2xl font-bold tracking-normal text-slate-950 md:text-3xl">Edit Organization Info</h1>
              <p className="mt-1 text-sm text-slate-500">Update the institutional profile, impact metrics, contact details, and brand assets.</p>
            </div>
          </div>

          <form
            onSubmit={(event) => {
              event.preventDefault();
              profileForm.put("/organization");
            }}
            className="space-y-6"
          >
            <div className="grid gap-5 md:grid-cols-2">
              <Panel title="Institution Profile" description="Core identity and official summary.">
                <div className="grid gap-4">
                  <Field label="Organization Name">
                    <input value={profileForm.data.name} onChange={(event) => profileForm.setData("name", event.target.value)} className={inputClass} />
                  </Field>
                  <Field label="Legal Name">
                    <input value={profileForm.data.legal_name} onChange={(event) => profileForm.setData("legal_name", event.target.value)} className={inputClass} />
                  </Field>
                  <Field label="Tagline">
                    <input value={profileForm.data.tagline} onChange={(event) => profileForm.setData("tagline", event.target.value)} className={inputClass} />
                  </Field>
                  <Field label="About">
                    <textarea value={profileForm.data.about} onChange={(event) => profileForm.setData("about", event.target.value)} className={textareaClass} />
                  </Field>
                </div>
              </Panel>

              <Panel title="Contact & Address" description="Public contact information for reports and collateral.">
                <div className="grid gap-4">
                  <Field label="Website">
                    <div className="relative">
                      <Globe2 className="absolute left-3 top-5 h-4 w-4 text-blue-600" />
                      <input value={profileForm.data.website} onChange={(event) => profileForm.setData("website", event.target.value)} className={`${inputClass} pl-9`} />
                    </div>
                  </Field>
                  <Field label="Email">
                    <div className="relative">
                      <Mail className="absolute left-3 top-5 h-4 w-4 text-blue-600" />
                      <input value={profileForm.data.email} onChange={(event) => profileForm.setData("email", event.target.value)} className={`${inputClass} pl-9`} />
                    </div>
                  </Field>
                  <Field label="Phone">
                    <div className="relative">
                      <Phone className="absolute left-3 top-5 h-4 w-4 text-blue-600" />
                      <input value={profileForm.data.phone} onChange={(event) => profileForm.setData("phone", event.target.value)} className={`${inputClass} pl-9`} />
                    </div>
                  </Field>
                  <div className="grid gap-4 md:grid-cols-2">
                    <Field label="City">
                      <input value={profileForm.data.city} onChange={(event) => profileForm.setData("city", event.target.value)} className={inputClass} />
                    </Field>
                    <Field label="Province">
                      <input value={profileForm.data.province} onChange={(event) => profileForm.setData("province", event.target.value)} className={inputClass} />
                    </Field>
                  </div>
                  <Field label="Address Line 1">
                    <input value={profileForm.data.address_line_1} onChange={(event) => profileForm.setData("address_line_1", event.target.value)} className={inputClass} />
                  </Field>
                  <Field label="Address Line 2">
                    <input value={profileForm.data.address_line_2} onChange={(event) => profileForm.setData("address_line_2", event.target.value)} className={inputClass} />
                  </Field>
                  <div className="grid gap-4 md:grid-cols-2">
                    <Field label="Country">
                      <input value={profileForm.data.country} onChange={(event) => profileForm.setData("country", event.target.value)} className={inputClass} />
                    </Field>
                    <Field label="Postal Code">
                      <input value={profileForm.data.postal_code} onChange={(event) => profileForm.setData("postal_code", event.target.value)} className={inputClass} />
                    </Field>
                  </div>
                </div>
              </Panel>
            </div>

            <div className="grid gap-5 md:grid-cols-2">
              <Panel title="Mission & Strategy" description="One item per line for objectives and focus areas.">
                <div className="space-y-4">
                  {strategyFields.map(({ key, label }) => (
                    <Field key={key} label={label}>
                      <textarea value={profileForm.data[key] ?? ""} onChange={(event) => profileForm.setData(key, event.target.value)} className={textareaClass} />
                    </Field>
                  ))}
                </div>
              </Panel>

              <Panel title="Impact Metrics" description="Numbers shown on the organization dashboard.">
                <div className="grid gap-4 md:grid-cols-2">
                  {impactFields.map(({ key, label, Icon }) => (
                    <Field key={key} label={label}>
                      <div className="relative">
                        <Icon className="absolute left-3 top-5 h-4 w-4 text-red-600" />
                        <input
                          type="number"
                          min={0}
                          value={profileForm.data[key] ?? ""}
                          onChange={(event) => profileForm.setData(key, event.target.value)}
                          className={`${inputClass} pl-9`}
                        />
                      </div>
                    </Field>
                  ))}
                </div>
              </Panel>
            </div>

            <div className="flex justify-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
              <Link href="/organization" className="inline-flex h-10 items-center rounded-md border border-slate-200 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
              </Link>
              <button type="submit" disabled={profileForm.processing} className="inline-flex h-10 items-center gap-2 rounded-md bg-red-600 px-4 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                <Save className="h-4 w-4" />
                {profileForm.processing ? "Saving..." : "Save Organization Info"}
              </button>
            </div>
          </form>

          <Panel title="Logo Library" description="Upload approved files for primary, light, dark, and icon usage.">
            <form
              onSubmit={(event) => {
                event.preventDefault();
                logoForm.post("/organization/logos", { forceFormData: true });
              }}
              className="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
            >
              {logoFields.map(({ key, label, url }) => (
                <label key={key} className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700">
                  {label}
                  <div className="my-3 flex h-24 items-center justify-center rounded-md border border-slate-200 bg-white p-3">
                    {url ? <img src={String(url)} alt={String(label)} className="max-h-16 max-w-full object-contain" /> : <span className="text-xs font-normal text-slate-500">(No file)</span>}
                  </div>
                  <input type="file" accept="image/*" onChange={(event) => logoForm.setData(key, event.target.files?.[0] ?? null)} className="block w-full text-xs font-normal text-slate-500" />
                </label>
              ))}
              <div className="flex items-end">
                <button type="submit" disabled={logoForm.processing} className="inline-flex h-10 items-center gap-2 rounded-md bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                  <Upload className="h-4 w-4" />
                  {logoForm.processing ? "Uploading..." : "Upload Logos"}
                </button>
              </div>
            </form>
          </Panel>
        </div>
      </div>
    </AppLayout>
  );
}
