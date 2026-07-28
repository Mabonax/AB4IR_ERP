import { Head, Link, useForm } from "@inertiajs/react";
import { GraduationCap, HeartHandshake, MapPinned, UserRound } from "lucide-react";
import { useMemo } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

type RouteDef = {
  url: string;
  method: "post" | "put" | "patch";
};

type BeneficiaryFormData = {
  member_id: string;
  name: string;
  surname: string;
  dob: string;
  age: string;
  id_number: string;
  email: string;
  phone: string;
  gender: string;
  program_id: string;
  project_id: string;
  project_location_id: string;
  enrolment_date: string;
  exit_date: string;
  participation_status: string;
  placement_status: string;
  member_type: string;
  street_address: string;
  address_line_2: string;
  city: string;
  province_id: string;
  postal_code: string;
  highest_qualification: string;
  attendance_status: string;
  nok_name: string;
  nok_surname: string;
  nok_relationship: string;
  nok_phone: string;
  nok_email: string;
};

type Props = {
  mode: "create" | "edit";
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  submitRoute: RouteDef;
  initialData: BeneficiaryFormData;
  programs: { id: number; title: string }[];
  members: { id: number; name: string; member_type?: string | null; email?: string | null }[];
  projects: { id: number; name: string; program_id?: number | null }[];
  provinces: { id: number; name: string }[];
  projectLocations: { id: number; project_id: number; name: string }[];
  backHref?: string;
};

function Field({
  label,
  error,
  required,
  children,
}: {
  label: string;
  error?: string;
  required?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="grid gap-2">
      <Label className="text-sm font-medium text-slate-700">
        {label}
        {required ? <span className="ml-1 text-red-600">*</span> : null}
      </Label>
      {children}
      {error ? <p className="text-xs text-red-600">{error}</p> : null}
    </div>
  );
}

export function BeneficiaryFormPage({
  mode,
  pageTitle,
  title,
  description,
  breadcrumbs,
  submitRoute,
  initialData,
  programs,
  members,
  projects,
  provinces,
  projectLocations,
  backHref = "/beneficiaries",
}: Props) {
  const form = useForm<BeneficiaryFormData>(initialData);

  const filteredLocations = useMemo(() => {
    if (!form.data.project_id) {
      return [];
    }

    return projectLocations.filter(
      (location) => String(location.project_id) === form.data.project_id,
    );
  }, [form.data.project_id, projectLocations]);

  const selectedProject = projects.find((project) => String(project.id) === form.data.project_id);
  const selectedProgram = programs.find(
    (program) => String(program.id) === (form.data.program_id || String(selectedProject?.program_id ?? "")),
  );
  const selectedLocation = filteredLocations.find((location) => String(location.id) === form.data.project_location_id);
  const selectedMember = members.find((member) => String(member.id) === form.data.member_id);

  const handleProjectChange = (value: string) => {
    const projectChanged = form.data.project_id !== value;
    const project = projects.find((candidate) => String(candidate.id) === value);

    form.setData("project_id", value);
    form.setData("program_id", project?.program_id ? String(project.program_id) : "");

    if (!value || projectChanged) {
      form.setData("project_location_id", "");
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="space-y-6 p-4">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {mode === "create" ? "Create Beneficiary" : "Edit Beneficiary"}
            </div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-3xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>
          <Link href={backHref}>
            <Button variant="outline">Back to Beneficiaries</Button>
          </Link>
        </div>

        <form
          onSubmit={(event) => {
            event.preventDefault();
            form.submit(submitRoute.method, submitRoute.url, {
              preserveScroll: true,
            });
          }}
          className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]"
        >
          <div className="space-y-5">
            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-red-50 p-2 text-red-600">
                    <UserRound className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Beneficiary Identity</CardTitle>
                    <CardDescription>Core profile, demographic, and verification details.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Linked Member" error={form.errors.member_id}>
                  <select
                    value={form.data.member_id}
                    onChange={(event) => form.setData("member_id", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Create or auto-link member</option>
                    {members.map((member) => (
                      <option key={member.id} value={member.id}>
                        {member.name}
                      </option>
                    ))}
                  </select>
                </Field>
                <Field label="First Name" required error={form.errors.name}>
                  <Input value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} />
                </Field>
                <Field label="Surname" required error={form.errors.surname}>
                  <Input value={form.data.surname} onChange={(event) => form.setData("surname", event.target.value)} />
                </Field>
                <Field label="Date of Birth" error={form.errors.dob}>
                  <Input type="date" value={form.data.dob} onChange={(event) => form.setData("dob", event.target.value)} />
                </Field>
                <Field label="Age" error={form.errors.age}>
                  <Input type="number" min={0} value={form.data.age} onChange={(event) => form.setData("age", event.target.value)} />
                </Field>
                <Field label="ID Number" error={form.errors.id_number}>
                  <Input value={form.data.id_number} onChange={(event) => form.setData("id_number", event.target.value)} />
                </Field>
                <Field label="Gender" error={form.errors.gender}>
                  <select
                    value={form.data.gender}
                    onChange={(event) => form.setData("gender", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Select gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                </Field>
                <Field label="Email" error={form.errors.email}>
                  <Input type="email" value={form.data.email} onChange={(event) => form.setData("email", event.target.value)} />
                </Field>
                <Field label="Phone Number" error={form.errors.phone}>
                  <Input type="tel" value={form.data.phone} onChange={(event) => form.setData("phone", event.target.value)} />
                </Field>
                <Field label="Attendance Status" required error={form.errors.attendance_status}>
                  <select
                    value={form.data.attendance_status}
                    onChange={(event) => form.setData("attendance_status", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="active">Active</option>
                    <option value="dropout">Dropout</option>
                  </select>
                </Field>
              </CardContent>
            </Card>

            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-red-50 p-2 text-red-600">
                    <GraduationCap className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Programme Placement</CardTitle>
                    <CardDescription>Assign the beneficiary to the correct project iteration and delivery site.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Programme" error={form.errors.program_id}>
                  <select
                    value={form.data.program_id}
                    onChange={(event) => form.setData("program_id", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Select programme</option>
                    {programs.map((program) => (
                      <option key={program.id} value={program.id}>
                        {program.title}
                      </option>
                    ))}
                  </select>
                </Field>
                <Field label="Project" required error={form.errors.project_id}>
                  <select
                    value={form.data.project_id}
                    onChange={(event) => handleProjectChange(event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Select project</option>
                    {projects.map((project) => (
                      <option key={project.id} value={project.id}>
                        {project.name}
                      </option>
                    ))}
                  </select>
                </Field>
                <Field label="Project Location" required error={form.errors.project_location_id}>
                  <select
                    value={form.data.project_location_id}
                    onChange={(event) => form.setData("project_location_id", event.target.value)}
                    disabled={!form.data.project_id}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">
                      {form.data.project_id ? "Select location" : "Select project first"}
                    </option>
                    {filteredLocations.map((location) => (
                      <option key={location.id} value={location.id}>
                        {location.name}
                      </option>
                    ))}
                  </select>
                </Field>
                <Field label="Highest Qualification" error={form.errors.highest_qualification}>
                  <Input value={form.data.highest_qualification} onChange={(event) => form.setData("highest_qualification", event.target.value)} />
                </Field>
                <Field label="Enrolment Date" error={form.errors.enrolment_date}>
                  <Input type="date" value={form.data.enrolment_date} onChange={(event) => form.setData("enrolment_date", event.target.value)} />
                </Field>
                <Field label="Exit Date" error={form.errors.exit_date}>
                  <Input type="date" value={form.data.exit_date} onChange={(event) => form.setData("exit_date", event.target.value)} />
                </Field>
                <Field label="Participation Status" error={form.errors.participation_status}>
                  <select
                    value={form.data.participation_status}
                    onChange={(event) => form.setData("participation_status", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="registered">Registered</option>
                    <option value="enrolled">Enrolled</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="withdrawn">Withdrawn</option>
                    <option value="suspended">Suspended</option>
                  </select>
                </Field>
                <Field label="Placement Status" error={form.errors.placement_status}>
                  <Input value={form.data.placement_status} onChange={(event) => form.setData("placement_status", event.target.value)} placeholder="Placed, pending, completed" />
                </Field>
                <Field label="Member Type" error={form.errors.member_type}>
                  <Input value={form.data.member_type} onChange={(event) => form.setData("member_type", event.target.value)} placeholder="Beneficiary" />
                </Field>
              </CardContent>
            </Card>

            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-red-50 p-2 text-red-600">
                    <MapPinned className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Address and Contact</CardTitle>
                    <CardDescription>Optional address fields for existing or partially imported records.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Street Address" error={form.errors.street_address}>
                  <textarea
                    rows={3}
                    value={form.data.street_address}
                    onChange={(event) => form.setData("street_address", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  />
                </Field>
                <Field label="Address Line 2" error={form.errors.address_line_2}>
                  <Input value={form.data.address_line_2} onChange={(event) => form.setData("address_line_2", event.target.value)} />
                </Field>
                <Field label="City" error={form.errors.city}>
                  <Input value={form.data.city} onChange={(event) => form.setData("city", event.target.value)} />
                </Field>
                <Field label="Province" error={form.errors.province_id}>
                  <select
                    value={form.data.province_id}
                    onChange={(event) => form.setData("province_id", event.target.value)}
                    className="rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                  >
                    <option value="">Select province</option>
                    {provinces.map((province) => (
                      <option key={province.id} value={province.id}>
                        {province.name}
                      </option>
                    ))}
                  </select>
                </Field>
                <Field label="Postal Code" error={form.errors.postal_code}>
                  <Input value={form.data.postal_code} onChange={(event) => form.setData("postal_code", event.target.value)} />
                </Field>
              </CardContent>
            </Card>

            <Card className="border-red-100 shadow-sm">
              <CardHeader>
                <div className="flex items-start gap-3">
                  <div className="rounded-xl bg-red-50 p-2 text-red-600">
                    <HeartHandshake className="h-4 w-4" />
                  </div>
                  <div>
                    <CardTitle className="text-base">Next of Kin</CardTitle>
                    <CardDescription>Optional for imported records. If you provide one detail set, add the identifying fields as well.</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="First Name" error={form.errors.nok_name}>
                  <Input value={form.data.nok_name} onChange={(event) => form.setData("nok_name", event.target.value)} />
                </Field>
                <Field label="Surname" error={form.errors.nok_surname}>
                  <Input value={form.data.nok_surname} onChange={(event) => form.setData("nok_surname", event.target.value)} />
                </Field>
                <Field label="Relationship" error={form.errors.nok_relationship}>
                  <Input value={form.data.nok_relationship} onChange={(event) => form.setData("nok_relationship", event.target.value)} />
                </Field>
                <Field label="Phone" error={form.errors.nok_phone}>
                  <Input type="tel" value={form.data.nok_phone} onChange={(event) => form.setData("nok_phone", event.target.value)} />
                </Field>
                <Field label="Email" error={form.errors.nok_email}>
                  <Input type="email" value={form.data.nok_email} onChange={(event) => form.setData("nok_email", event.target.value)} />
                </Field>
              </CardContent>
            </Card>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={backHref}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button type="submit" disabled={form.processing} className="bg-red-600 text-white hover:bg-red-700">
                {form.processing ? "Saving..." : mode === "create" ? "Create Beneficiary" : "Update Beneficiary"}
              </Button>
            </div>
          </div>

          <div className="space-y-5">
            <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
              <CardHeader>
                <CardTitle className="text-base">Placement Snapshot</CardTitle>
                <CardDescription className="text-slate-300">
                  Quick view of the current beneficiary assignment while you complete the record.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm text-slate-200">
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Linked Member</div>
                  <div className="mt-1 font-medium text-white">{selectedMember?.name ?? "Will be created or auto-linked"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Programme</div>
                  <div className="mt-1 font-medium text-white">{selectedProgram?.title ?? "Not selected"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Project</div>
                  <div className="mt-1 font-medium text-white">{selectedProject?.name ?? "Not selected"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Location</div>
                  <div className="mt-1 font-medium text-white">{selectedLocation?.name ?? "Not selected"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Attendance Status</div>
                  <div className="mt-1 font-medium capitalize text-white">
                    {form.data.attendance_status || "Not selected"}
                  </div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Participation Status</div>
                  <div className="mt-1 font-medium capitalize text-white">
                    {form.data.participation_status || "Not selected"}
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
