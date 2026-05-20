import { useState } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";

import AppLayout from "@/layouts/app-layout";
import { DomainNav } from "@/components/domain-nav";
import { organizationNavItems } from "@/config/domain-nav/organization";
import { type BreadcrumbItem, type SharedData } from "@/types";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Organization", href: "/organization" },
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
  updated_at?: string | null;
};

const formatMetric = (value?: number | null) => {
  if (value === null || value === undefined) {
    return "-";
  }

  return `${new Intl.NumberFormat().format(value)} +`;
};

const splitLines = (value?: string | null) =>
  (value ?? "")
    .split(/\r?\n/)
    .map((item) => item.trim())
    .filter(Boolean);

export default function OrganizationShow({
  profile,
}: {
  profile: OrganizationProfile;
}) {
  const { auth } = usePage<SharedData>().props;
  const permissions = auth?.user?.permissions ?? [];
  const canManage = permissions.includes("domain.organization.manage");
  const [showEditProfile, setShowEditProfile] = useState(false);
  const [showEditLogos, setShowEditLogos] = useState(false);

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

  const objectives = splitLines(profile.objectives);
  const focusAreas = splitLines(profile.focus_areas);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Organization Profile" />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{profile.name}</h1>
            <p className="text-sm text-muted-foreground">
              Institutional summary, branding, impact, and official reference information
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            {canManage ? (
              <>
                <button
                  type="button"
                  onClick={() => setShowEditProfile((value) => !value)}
                  className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                >
                  {showEditProfile ? "Close Edit Profile" : "Edit Organization Info"}
                </button>
                <button
                  type="button"
                  onClick={() => setShowEditLogos((value) => !value)}
                  className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                  {showEditLogos ? "Close Logo Manager" : "Manage Logos"}
                </button>
              </>
            ) : null}
            <DomainNav items={organizationNavItems} />
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {[
            ["Impact", formatMetric(profile.impact_summary?.total)],
            ["Digital Impact", formatMetric(profile.impact_summary?.digital)],
            ["Physical Impact", formatMetric(profile.impact_summary?.physical)],
            ["Trainings Conducted", formatMetric(profile.impact_summary?.trainings_conducted)],
          ].map(([label, value]) => (
            <div key={String(label)} className="rounded-lg border bg-white p-4">
              <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
              <div className="mt-2 text-2xl font-semibold">{value}</div>
            </div>
          ))}
        </div>

        <div className="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>About AB4IR</CardTitle>
                <CardDescription>Official institutional profile for internal and external reference.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-5 text-sm text-slate-700">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Organization Name</div>
                    <div className="mt-1 font-medium">{profile.name ?? "-"}</div>
                  </div>
                  <div>
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Legal Name</div>
                    <div className="mt-1">{profile.legal_name ?? "-"}</div>
                  </div>
                  <div className="sm:col-span-2">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Tagline</div>
                    <div className="mt-1">{profile.tagline ?? "-"}</div>
                  </div>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Mission</div>
                  <p className="mt-1 whitespace-pre-wrap">{profile.mission ?? "Not yet defined."}</p>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Vision</div>
                  <p className="mt-1 whitespace-pre-wrap">{profile.vision ?? "Not yet defined."}</p>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">About</div>
                  <p className="mt-1 whitespace-pre-wrap">{profile.about ?? "Not yet defined."}</p>
                </div>

                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Service Offering</div>
                  <p className="mt-1 whitespace-pre-wrap">{profile.service_offering ?? "Not yet defined."}</p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Objectives</CardTitle>
                <CardDescription>Institutional objectives that should be quoted consistently.</CardDescription>
              </CardHeader>
              <CardContent>
                {objectives.length ? (
                  <ul className="space-y-3 text-sm text-slate-700">
                    {objectives.map((item) => (
                      <li key={item} className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        {item}
                      </li>
                    ))}
                  </ul>
                ) : (
                  <p className="text-sm text-muted-foreground">No objectives recorded yet.</p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Our Focus Areas</CardTitle>
                <CardDescription>Institutional program and innovation focus areas.</CardDescription>
              </CardHeader>
              <CardContent>
                {focusAreas.length ? (
                  <div className="flex flex-wrap gap-3">
                    {focusAreas.map((item) => (
                      <div key={item} className="rounded-full border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700">
                        {item}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground">No focus areas recorded yet.</p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Our Impact</CardTitle>
                <CardDescription>Public-facing impact channels and audience reach.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {(profile.impact_channels ?? []).map((item) => (
                  <div key={item.label} className="rounded-lg border p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{item.label}</div>
                    <div className="mt-2 text-xl font-semibold">{formatMetric(item.value)}</div>
                  </div>
                ))}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Contact and Address</CardTitle>
                <CardDescription>Use these details in reports, proposals, and event collateral.</CardDescription>
              </CardHeader>
              <CardContent className="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Website</div>
                  <div className="mt-1 break-all">{profile.website ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Email</div>
                  <div className="mt-1 break-all">{profile.email ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Phone</div>
                  <div className="mt-1">{profile.phone ?? "-"}</div>
                </div>
                <div>
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Postal Code</div>
                  <div className="mt-1">{profile.postal_code ?? "-"}</div>
                </div>
                <div className="sm:col-span-2">
                  <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Address</div>
                  <div className="mt-1 whitespace-pre-wrap">
                    {[profile.address_line_1, profile.address_line_2, profile.city, profile.province, profile.country]
                      .filter(Boolean)
                      .join(", ") || "-"}
                  </div>
                </div>
              </CardContent>
            </Card>

            {canManage && showEditProfile ? (
              <Card>
                <CardHeader>
                  <CardTitle>Edit Organization Information</CardTitle>
                  <CardDescription>Manager-only update form for the institutional summary and impact stats.</CardDescription>
                </CardHeader>
                <CardContent>
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      profileForm.put("/organization");
                    }}
                    className="space-y-5"
                  >
                    <div className="grid gap-4 sm:grid-cols-2">
                      {[
                        ["name", "Organization Name"],
                        ["legal_name", "Legal Name"],
                        ["tagline", "Tagline"],
                        ["website", "Website"],
                        ["email", "Email"],
                        ["phone", "Phone"],
                        ["address_line_1", "Address Line 1"],
                        ["address_line_2", "Address Line 2"],
                        ["city", "City"],
                        ["province", "Province"],
                        ["country", "Country"],
                        ["postal_code", "Postal Code"],
                      ].map(([key, label]) => (
                        <div key={key}>
                          <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
                          <input
                            value={(profileForm.data as any)[key] ?? ""}
                            onChange={(e) => profileForm.setData(key as any, e.target.value)}
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                          />
                        </div>
                      ))}
                    </div>

                    {[
                      ["mission", "Mission"],
                      ["vision", "Vision"],
                      ["about", "About the Organization"],
                      ["service_offering", "Service Offering"],
                      ["objectives", "Objectives"],
                      ["focus_areas", "Focus Areas"],
                    ].map(([key, label]) => (
                      <div key={key}>
                        <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
                        <textarea
                          value={(profileForm.data as any)[key] ?? ""}
                          onChange={(e) => profileForm.setData(key as any, e.target.value)}
                          className="mt-1 min-h-28 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                        {key === "objectives" || key === "focus_areas" ? (
                          <p className="mt-1 text-xs text-muted-foreground">Use one item per line.</p>
                        ) : null}
                      </div>
                    ))}

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                      {[
                        ["impact_total", "Impact"],
                        ["impact_digital", "Digital Impact"],
                        ["impact_physical", "Physical Impact"],
                        ["trainings_conducted", "Trainings Conducted"],
                        ["impact_website", "Website"],
                        ["impact_walkins", "Walk-ins"],
                        ["impact_facebook", "Facebook"],
                        ["impact_x", "X / Twitter"],
                        ["impact_linkedin", "LinkedIn"],
                        ["impact_livestreaming", "Livestreaming"],
                        ["impact_instagram", "Instagram"],
                        ["impact_youtube", "YouTube"],
                      ].map(([key, label]) => (
                        <div key={key}>
                          <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
                          <input
                            type="number"
                            min={0}
                            value={(profileForm.data as any)[key] ?? ""}
                            onChange={(e) => profileForm.setData(key as any, e.target.value)}
                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                          />
                        </div>
                      ))}
                    </div>

                    <button
                      type="submit"
                      disabled={profileForm.processing}
                      className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
                    >
                      {profileForm.processing ? "Saving..." : "Save organization profile"}
                    </button>
                  </form>
                </CardContent>
              </Card>
            ) : null}
          </div>

          <div className="space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Logo Library</CardTitle>
                <CardDescription>Approved branding assets for different surfaces and backgrounds.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {[
                  ["Primary Logo", profile.primary_logo_url],
                  ["Light Logo", profile.light_logo_url],
                  ["Dark Logo", profile.dark_logo_url],
                  ["Icon Logo", profile.icon_logo_url],
                ].map(([label, url]) => (
                  <div key={String(label)} className="rounded-lg border p-4">
                    <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</div>
                    {url ? (
                      <div className="mt-3 rounded-md border bg-slate-50 p-3">
                        <img src={String(url)} alt={String(label)} className="max-h-24 w-auto object-contain" />
                      </div>
                    ) : (
                      <p className="mt-2 text-sm text-muted-foreground">No file uploaded yet.</p>
                    )}
                  </div>
                ))}
              </CardContent>
            </Card>

            {canManage && showEditLogos ? (
              <Card>
                <CardHeader>
                  <CardTitle>Upload Logo Versions</CardTitle>
                  <CardDescription>Upload approved files for primary, light, dark, and icon usage.</CardDescription>
                </CardHeader>
                <CardContent>
                  <form
                    onSubmit={(e) => {
                      e.preventDefault();
                      logoForm.post("/organization/logos", { forceFormData: true });
                    }}
                    className="space-y-4"
                  >
                    {[
                      ["primary_logo", "Primary Logo"],
                      ["light_logo", "Light Version"],
                      ["dark_logo", "Dark Version"],
                      ["icon_logo", "Icon Version"],
                    ].map(([key, label]) => (
                      <div key={key}>
                        <label className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{label}</label>
                        <input
                          type="file"
                          accept="image/*"
                          onChange={(e) => logoForm.setData(key as any, e.target.files?.[0] ?? null)}
                          className="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                        />
                      </div>
                    ))}

                    <button
                      type="submit"
                      disabled={logoForm.processing}
                      className="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-60"
                    >
                      {logoForm.processing ? "Uploading..." : "Upload logo files"}
                    </button>
                  </form>
                </CardContent>
              </Card>
            ) : null}

            <Card>
              <CardHeader>
                <CardTitle>Profile Integrity</CardTitle>
                <CardDescription>One source of truth for the institution.</CardDescription>
              </CardHeader>
              <CardContent className="space-y-3 text-sm text-slate-700">
                <p>
                  This page centralizes the organization's mission, vision, objectives, focus areas, impact, contact details, and approved branding assets.
                </p>
                <p>
                  Staff writing proposals, reports, project summaries, event collateral, or institutional profiles should source content from here.
                </p>
                <p className="text-xs text-muted-foreground">
                  Last updated: {profile.updated_at ?? "-"}
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
