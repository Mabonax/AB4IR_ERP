import { Head, Link, useForm } from "@inertiajs/react";
import {
  BriefcaseBusiness,
  CalendarRange,
  Heart,
  Landmark,
  ShieldCheck,
  UserRoundCog,
} from "lucide-react";
import { useEffect, useMemo, type ReactNode } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { staffNavItems } from "@/config/domain-nav/staff";
import AppLayout from "@/layouts/app-layout";
import { cn } from "@/lib/utils";
import { type BreadcrumbItem } from "@/types";

type RouteFn = (...args: any[]) => {
  url: string;
  method: "post" | "put" | "patch";
};

type StaffFormData = {
  "staff.first_name": string;
  "staff.last_name": string;
  "staff.email": string;
  "staff.phone": string;
  "staff.employee_number": string;
  "staff.start_date": string;
  "staff.manager_id": string;
  "staff.is_ceo": string;
  "staff.is_board_member": string;
  "staff.is_manager": string;
  "staff.is_intern": string;
  "staff.intern_sponsor_name": string;
  "staff.internship_start_date": string;
  "staff.internship_end_date": string;
  "staff.department_id": string;
  "staff.status": string;
  "next_of_kin.full_name": string;
  "next_of_kin.relationship": string;
  "next_of_kin.phone": string;
  "next_of_kin.email": string;
};

type StaffOption = {
  id: number;
  name: string;
  department_id?: number | null;
  is_manager?: boolean;
  is_ceo?: boolean;
};

type DepartmentOption = {
  id: number;
  name: string;
  description?: string | null;
};

type StaffMemberFormPageProps = {
  mode: "create" | "edit";
  pageTitle: string;
  title: string;
  description: string;
  breadcrumbs: BreadcrumbItem[];
  submitRoute: RouteFn;
  routeParams?: unknown;
  currentStaffId?: number | null;
  departments: DepartmentOption[];
  managers: StaffOption[];
  initialData: StaffFormData;
  backHref?: string;
};

const buildNestedPayload = (flat: StaffFormData) => {
  const result: Record<string, unknown> = {};

  Object.entries(flat).forEach(([key, value]) => {
    if (!key.includes(".")) {
      result[key] = value;
      return;
    }

    const parts = key.split(".");
    let current = result as Record<string, unknown>;

    for (let i = 0; i < parts.length - 1; i += 1) {
      const part = parts[i];

      if (current[part] === undefined || typeof current[part] !== "object") {
        current[part] = {};
      }

      current = current[part] as Record<string, unknown>;
    }

    current[parts[parts.length - 1]] = value;
  });

  return result;
};

function Section({
  title,
  description,
  icon,
  children,
}: {
  title: string;
  description: string;
  icon: ReactNode;
  children: ReactNode;
}) {
  return (
    <Card className="border-orange-100 shadow-sm">
      <CardHeader className="space-y-3">
        <div className="flex items-start gap-3">
          <div className="rounded-xl bg-orange-50 p-2 text-orange-600">{icon}</div>
          <div>
            <CardTitle className="text-base">{title}</CardTitle>
            <CardDescription>{description}</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>{children}</CardContent>
    </Card>
  );
}

function Field({
  label,
  required,
  error,
  children,
}: {
  label: string;
  required?: boolean;
  error?: string;
  children: ReactNode;
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

export function StaffMemberFormPage({
  mode,
  pageTitle,
  title,
  description,
  breadcrumbs,
  submitRoute,
  routeParams,
  currentStaffId = null,
  departments,
  managers,
  initialData,
  backHref = "/staff",
}: StaffMemberFormPageProps) {
  const form = useForm<StaffFormData>(initialData);
  const { data, setData, processing, errors } = form;

  useEffect(() => {
    setData(initialData);
  }, [initialData, setData]);

  const updateField = (field: keyof StaffFormData, value: string) => {
    setData(field as any, value);
  };

  const departmentId = data["staff.department_id"]
    ? Number(data["staff.department_id"])
    : null;

  const selectedDepartment = departments.find(
    (department) => department.id === departmentId
  );
  const isIntern = data["staff.is_intern"] === "1";
  const isManager = data["staff.is_manager"] === "1";
  const isCeo = data["staff.is_ceo"] === "1";

  const managerOptions = useMemo(() => {
    const filtered = managers.filter((manager) => manager.id !== currentStaffId);

    if (isCeo) {
      return [];
    }

    if (isManager) {
      return filtered.filter((manager) => manager.is_ceo);
    }

    if (!departmentId) {
      return filtered.filter((manager) => manager.is_manager || manager.is_ceo);
    }

    return filtered.filter((manager) => {
      if (manager.is_ceo) {
        return true;
      }

      if (!manager.is_manager) {
        return false;
      }

      if (manager.department_id === undefined || manager.department_id === null) {
        return true;
      }

      return manager.department_id === departmentId;
    });
  }, [currentStaffId, departmentId, isCeo, isManager, managers]);

  const departmentManager = useMemo(
    () =>
      !isManager && !isCeo
        ? managerOptions.find(
        (manager) =>
          manager.is_manager &&
          manager.department_id === departmentId &&
          manager.id !== currentStaffId
      ) ?? null
        : null,
    [currentStaffId, departmentId, isCeo, isManager, managerOptions]
  );

  const internshipDurationLabel = useMemo(() => {
    if (!data["staff.internship_start_date"] || !data["staff.internship_end_date"]) {
      return null;
    }

    const start = new Date(data["staff.internship_start_date"]);
    const end = new Date(data["staff.internship_end_date"]);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
      return null;
    }

    const diffDays = Math.floor((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    const months = Math.floor(diffDays / 30);
    const weeks = Math.floor((diffDays % 30) / 7);
    const days = diffDays % 7;

    const parts = [
      months > 0 ? `${months} month${months === 1 ? "" : "s"}` : null,
      weeks > 0 ? `${weeks} week${weeks === 1 ? "" : "s"}` : null,
      days > 0 ? `${days} day${days === 1 ? "" : "s"}` : null,
    ].filter(Boolean);

    return parts.length > 0 ? `${parts.join(", ")} (${diffDays} days)` : `${diffDays} days`;
  }, [data]);

  useEffect(() => {
    if (isCeo) {
      if (data["staff.manager_id"]) {
        updateField("staff.manager_id", "");
      }
      if (data["staff.is_manager"] !== "1") {
        updateField("staff.is_manager", "1");
      }
      return;
    }

    const currentManagerId = data["staff.manager_id"];
    const exists = currentManagerId
      ? managerOptions.some((manager) => String(manager.id) === String(currentManagerId))
      : false;

    if (!currentManagerId) {
      if (isManager && managerOptions.length > 0) {
        updateField("staff.manager_id", String(managerOptions[0].id));
        return;
      }

      if (departmentManager) {
        updateField("staff.manager_id", String(departmentManager.id));
      }
      return;
    }

    if (!exists) {
      updateField("staff.manager_id", departmentManager ? String(departmentManager.id) : "");
      return;
    }

    const currentManager = managerOptions.find(
      (manager) => String(manager.id) === String(currentManagerId)
    );

    if (
      departmentManager &&
      !isManager &&
      currentManager &&
      !currentManager.is_manager
    ) {
      updateField("staff.manager_id", String(departmentManager.id));
    }
  }, [data, departmentManager, isCeo, isManager, managerOptions]);

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    const payload = buildNestedPayload({
      ...data,
      "staff.start_date": data["staff.start_date"]
        ? data["staff.start_date"].slice(0, 10)
        : "",
    });

    const routeDef = submitRoute(routeParams);

    form.transform(() => payload);
    form.submit(routeDef.method, routeDef.url, {
      preserveScroll: true,
    });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={pageTitle} />

      <div className="p-4 space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant="outline">{mode === "create" ? "Create Staff" : "Edit Staff"}</Badge>
              {selectedDepartment ? <Badge variant="secondary">{selectedDepartment.name}</Badge> : null}
            </div>
            <div>
              <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
              <p className="max-w-2xl text-sm text-muted-foreground">{description}</p>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-3">
            <DomainNav items={staffNavItems} />
            <Link href={backHref}>
              <Button variant="outline">Back to Staff</Button>
            </Link>
          </div>
        </div>

        <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_300px]">
          <form onSubmit={handleSubmit} className="space-y-5">
            <Section
              title="Staff Identity"
              description="Basic staff details used for access, contact, and employment records."
              icon={<UserRoundCog className="h-4 w-4" />}
            >
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="First Name" required error={errors["staff.first_name"]}>
                  <Input value={data["staff.first_name"]} onChange={(event) => updateField("staff.first_name", event.target.value)} />
                </Field>
                <Field label="Last Name" required error={errors["staff.last_name"]}>
                  <Input value={data["staff.last_name"]} onChange={(event) => updateField("staff.last_name", event.target.value)} />
                </Field>
                <Field label="Email" required error={errors["staff.email"]}>
                  <Input type="email" value={data["staff.email"]} onChange={(event) => updateField("staff.email", event.target.value)} />
                </Field>
                <Field label="Phone" error={errors["staff.phone"]}>
                  <Input type="tel" value={data["staff.phone"]} onChange={(event) => updateField("staff.phone", event.target.value)} />
                </Field>
                <Field label="Employee Number" required error={errors["staff.employee_number"]}>
                  <Input value={data["staff.employee_number"]} onChange={(event) => updateField("staff.employee_number", event.target.value)} />
                </Field>
                <Field label="Start Date" required error={errors["staff.start_date"]}>
                  <Input type="date" value={data["staff.start_date"]} onChange={(event) => updateField("staff.start_date", event.target.value)} />
                </Field>
              </div>
            </Section>

            <Section
              title="Department Assignment"
              description="Set the reporting line, operational placement, and governance flags."
              icon={<BriefcaseBusiness className="h-4 w-4" />}
            >
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Department" required error={errors["staff.department_id"]}>
                  <Select value={data["staff.department_id"] || undefined} onValueChange={(value) => updateField("staff.department_id", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select department" />
                    </SelectTrigger>
                    <SelectContent>
                      {departments.map((department) => (
                        <SelectItem key={department.id} value={String(department.id)}>
                          {department.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </Field>

                <Field label="Manager" error={errors["staff.manager_id"]}>
                  <Select value={data["staff.manager_id"] || undefined} onValueChange={(value) => updateField("staff.manager_id", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder={isCeo ? "CEO does not require a manager" : "Select manager"} />
                    </SelectTrigger>
                    <SelectContent>
                      {managerOptions.map((manager) => (
                        <SelectItem key={manager.id} value={String(manager.id)}>
                          {manager.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </Field>

                <Field label="Status" required error={errors["staff.status"]}>
                  <Select value={data["staff.status"] || undefined} onValueChange={(value) => updateField("staff.status", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="active">Active</SelectItem>
                      <SelectItem value="inactive">Inactive</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
              </div>

              <Separator className="my-5" />

              <div className="grid gap-4 md:grid-cols-3">
                <Field label="Manager Flag" error={errors["staff.is_manager"]}>
                  <Select value={data["staff.is_manager"] || "0"} onValueChange={(value) => updateField("staff.is_manager", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select manager flag" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No</SelectItem>
                      <SelectItem value="1">Yes</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>

                <Field label="CEO Flag" error={errors["staff.is_ceo"]}>
                  <Select value={data["staff.is_ceo"] || "0"} onValueChange={(value) => updateField("staff.is_ceo", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select CEO flag" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No</SelectItem>
                      <SelectItem value="1">Yes</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>

                <Field label="Board Member Flag" error={errors["staff.is_board_member"]}>
                  <Select value={data["staff.is_board_member"] || "0"} onValueChange={(value) => updateField("staff.is_board_member", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select board member flag" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No</SelectItem>
                      <SelectItem value="1">Yes</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>
              </div>

              {isManager && !isCeo ? (
                <p className="mt-4 text-sm text-muted-foreground">
                  Managers can only report directly to the CEO.
                </p>
              ) : null}
            </Section>

            <Section
              title="Internship Details"
              description="Track sponsored interns, their placement period, and calculated duration."
              icon={<CalendarRange className="h-4 w-4" />}
            >
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Field label="Intern Flag" error={errors["staff.is_intern"]}>
                  <Select value={data["staff.is_intern"] || "0"} onValueChange={(value) => updateField("staff.is_intern", value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select intern flag" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="0">No</SelectItem>
                      <SelectItem value="1">Yes</SelectItem>
                    </SelectContent>
                  </Select>
                </Field>

                {isIntern ? (
                  <>
                    <Field label="Sponsor Company" required error={errors["staff.intern_sponsor_name"]}>
                      <Input
                        value={data["staff.intern_sponsor_name"]}
                        onChange={(event) => updateField("staff.intern_sponsor_name", event.target.value)}
                        placeholder='Mirror Consulting or YES Youth'
                      />
                    </Field>

                    <Field label="Internship Start Date" required error={errors["staff.internship_start_date"]}>
                      <Input
                        type="date"
                        value={data["staff.internship_start_date"]}
                        onChange={(event) => updateField("staff.internship_start_date", event.target.value)}
                      />
                    </Field>

                    <Field label="Internship End Date" required error={errors["staff.internship_end_date"]}>
                      <Input
                        type="date"
                        value={data["staff.internship_end_date"]}
                        onChange={(event) => updateField("staff.internship_end_date", event.target.value)}
                      />
                    </Field>
                  </>
                ) : null}
              </div>

              {isIntern ? (
                <div className="mt-5 rounded-xl border border-orange-100 bg-orange-50 px-4 py-3 text-sm text-orange-900">
                  <div className="font-medium">Calculated Duration</div>
                  <div className="mt-1 text-orange-800">
                    {internshipDurationLabel ?? "Select the internship start and end dates to calculate duration."}
                  </div>
                </div>
              ) : null}
            </Section>

            <Section
              title="Next Of Kin"
              description="Emergency contact details required for staff welfare and escalation."
              icon={<Heart className="h-4 w-4" />}
            >
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Field label="Full Name" required error={errors["next_of_kin.full_name"]}>
                  <Input value={data["next_of_kin.full_name"]} onChange={(event) => updateField("next_of_kin.full_name", event.target.value)} />
                </Field>
                <Field label="Relationship" required error={errors["next_of_kin.relationship"]}>
                  <Input value={data["next_of_kin.relationship"]} onChange={(event) => updateField("next_of_kin.relationship", event.target.value)} />
                </Field>
                <Field label="Phone" required error={errors["next_of_kin.phone"]}>
                  <Input type="tel" value={data["next_of_kin.phone"]} onChange={(event) => updateField("next_of_kin.phone", event.target.value)} />
                </Field>
                <Field label="Email" error={errors["next_of_kin.email"]}>
                  <Input type="email" value={data["next_of_kin.email"]} onChange={(event) => updateField("next_of_kin.email", event.target.value)} />
                </Field>
              </div>
            </Section>

            <div className="flex flex-wrap items-center justify-end gap-3">
              <Link href={backHref}>
                <Button type="button" variant="outline">Cancel</Button>
              </Link>
              <Button
                type="submit"
                disabled={processing}
                className={cn("bg-red-600 text-white hover:bg-red-700", processing && "opacity-70")}
              >
                {processing ? "Saving..." : mode === "create" ? "Create Staff" : "Update Staff"}
              </Button>
            </div>
          </form>

          <div className="space-y-5">
            <Card className="border-slate-200 bg-slate-900 text-white shadow-sm">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base">
                  <Landmark className="h-4 w-4 text-orange-300" />
                  Assignment Snapshot
                </CardTitle>
                <CardDescription className="text-slate-300">
                  Live summary of the current form selection.
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4 text-sm text-slate-200">
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Department</div>
                  <div className="mt-1 font-medium text-white">{selectedDepartment?.name ?? "Not selected"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Available Managers</div>
                  <div className="mt-1 font-medium text-white">{managerOptions.length}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Department Manager</div>
                  <div className="mt-1 font-medium text-white">{departmentManager?.name ?? "Not assigned"}</div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Internship</div>
                  <div className="mt-1 font-medium text-white">
                    {isIntern ? (internshipDurationLabel ?? "Dates pending") : "Not an intern"}
                  </div>
                </div>
                <div>
                  <div className="text-xs uppercase tracking-wide text-slate-400">Status</div>
                  <div className="mt-1 font-medium capitalize text-white">{data["staff.status"] || "active"}</div>
                </div>
              </CardContent>
            </Card>

            <Card className="border-orange-100 bg-orange-50 shadow-sm">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-base text-orange-900">
                  <ShieldCheck className="h-4 w-4" />
                  Capture Notes
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2 text-sm text-orange-800">
                <p>Choose the department first so the manager list stays relevant.</p>
                <p>Keep employee numbers unique and stable before saving.</p>
                <p>Emergency contact details should be complete before onboarding is finished.</p>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
