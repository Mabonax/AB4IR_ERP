import { Head, Link, useForm } from "@inertiajs/react";
import { Download } from "lucide-react";
import { FormEvent } from "react";

import { CustomTable } from "@/components/custom-table";
import { ServiceDeliveryNav } from "@/components/service-delivery-nav";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Service Delivery", href: "/service-delivery" },
  { title: "Documents", href: "/service-delivery/documents" },
];

const columns = [
  { label: "Programme", key: "program_title", className: "px-4 py-2 text-left" },
  { label: "Project", key: "project_name", className: "px-4 py-2 text-left" },
  { label: "Category", key: "category", className: "px-4 py-2 text-left" },
  { label: "Name", key: "name", className: "px-4 py-2 text-left" },
  { label: "Uploaded By", key: "uploaded_by_name", className: "px-4 py-2 text-left" },
  {
    label: "Download",
    key: "download",
    className: "px-4 py-2 text-left",
    render: (row: any) => (
      <Link href={row.download_url} className="inline-flex items-center gap-2 rounded-md border border-red-500 px-3 py-2 text-sm text-red-600 hover:bg-red-500 hover:text-white">
        <Download className="h-4 w-4" />
        Download
      </Link>
    ),
  },
];

export default function DocumentsPage({ programs, projects, documents }: any) {
  const form = useForm({
    program_id: "",
    project_id: "",
    category: "",
    name: "",
    file: null as File | null,
  });

  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.post("/service-delivery/documents", { forceFormData: true });
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Programme Documents" />
      <div className="space-y-6 p-4">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h1 className="text-2xl font-semibold text-slate-900">Programme documents</h1>
            <p className="mt-2 text-sm text-slate-600">Upload attendance registers, reports, photos, agreements, certificates, and other programme evidence.</p>
          </div>
          <ServiceDeliveryNav />
        </div>

        <form onSubmit={submit} className="grid gap-4 rounded-2xl border border-red-100 bg-white p-5 shadow-sm md:grid-cols-5">
          <div className="grid gap-2">
            <Label>Programme</Label>
            <select className="rounded-md border px-3 py-2 text-sm" value={form.data.program_id} onChange={(e) => form.setData("program_id", e.target.value)}>
              <option value="">Select programme</option>
              {programs.map((program: any) => <option key={program.id} value={program.id}>{program.title}</option>)}
            </select>
          </div>
          <div className="grid gap-2">
            <Label>Project</Label>
            <select className="rounded-md border px-3 py-2 text-sm" value={form.data.project_id} onChange={(e) => form.setData("project_id", e.target.value)}>
              <option value="">Optional project</option>
              {projects.map((project: any) => <option key={project.id} value={project.id}>{project.name}</option>)}
            </select>
          </div>
          <div className="grid gap-2">
            <Label>Category</Label>
            <Input value={form.data.category} onChange={(e) => form.setData("category", e.target.value)} placeholder="Attendance Register" />
          </div>
          <div className="grid gap-2">
            <Label>Name</Label>
            <Input value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} placeholder="June Workshop Register" />
          </div>
          <div className="grid gap-2">
            <Label>File</Label>
            <Input type="file" onChange={(e) => form.setData("file", e.target.files?.[0] ?? null)} />
          </div>
          <div className="md:col-span-5 flex justify-end">
            <Button type="submit" disabled={form.processing} className="bg-red-600 text-white hover:bg-red-700">
              {form.processing ? "Uploading..." : "Upload document"}
            </Button>
          </div>
        </form>
        <CustomTable columns={columns} data={documents} />
      </div>
    </AppLayout>
  );
}
