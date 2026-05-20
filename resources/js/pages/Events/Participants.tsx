import { Head, router, usePage } from "@inertiajs/react";
import { Pencil, Plus, Upload } from "lucide-react";
import { useMemo, useRef, useState } from "react";

import { DomainNav } from "@/components/domain-nav";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/app-layout";
import { eventWorkflowNav } from "@/pages/Events/navigation";
import { type BreadcrumbItem, type SharedData } from "@/types";

const participantStatusOptions = [
  { value: "registered", label: "Registered" },
  { value: "confirmed", label: "Confirmed" },
  { value: "checked_in", label: "Checked In" },
  { value: "attended", label: "Attended" },
  { value: "cancelled", label: "Cancelled" },
];

const participantCategoryOptions = [
  { value: "speaker", label: "Speaker" },
  { value: "facilitator", label: "Facilitator" },
  { value: "attendee", label: "Attendee" },
  { value: "exhibitor", label: "Exhibitor" },
  { value: "sponsor", label: "Sponsor" },
  { value: "partner", label: "Partner" },
  { value: "media_house", label: "Media House" },
  { value: "vip", label: "VIP" },
  { value: "team_board", label: "Team / Board" },
];

const participantCategoryOrder = participantCategoryOptions.map((option) => option.value);

function statusBadgeClass(status: string): string {
  switch (status) {
    case "attended":
      return "border-green-200 bg-green-50 text-green-700";
    case "confirmed":
    case "checked_in":
      return "border-blue-200 bg-blue-50 text-blue-700";
    case "cancelled":
      return "border-rose-200 bg-rose-50 text-rose-700";
    default:
      return "border-amber-200 bg-amber-50 text-amber-700";
  }
}

function emptyParticipantForm(sortOrder = "1") {
  return {
    id: null as number | null,
    category: "attendee",
    name: "",
    surname: "",
    title: "",
    organization_name: "",
    topic: "",
    bio: "",
    email: "",
    phone: "",
    role: "",
    attendance_type: "",
    attendance_status: "registered",
    notes: "",
    sort_order: sortOrder,
  };
}

function attendanceTypePlaceholder(category: string): string {
  return category === "attendee"
    ? "Required for attendees: in-person, virtual, hybrid"
    : "In-person, virtual, hybrid, exhibitor booth";
}

export default function EventParticipants({
  event,
}: {
  event: any;
}) {
  const initialCategory = event.participant_categories?.[0]?.key ?? "attendee";
  const breadcrumbs: BreadcrumbItem[] = [
    { title: "Events", href: "/events" },
    { title: event.title, href: `/events/${event.id}` },
    { title: "Participants", href: `/events/${event.id}/participants` },
  ];

  const { auth, flash } = usePage<SharedData>().props as SharedData & {
    flash?: Record<string, unknown>;
  };
  const canManage = (auth?.user?.permissions ?? []).includes("domain.events.manage");
  const importErrors = Array.isArray(flash?.import_errors) ? (flash?.import_errors as string[]) : [];
  const nextSortOrder = String((event.participants?.length ?? 0) + 1);
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const [participantDialogOpen, setParticipantDialogOpen] = useState(false);
  const [importDialogOpen, setImportDialogOpen] = useState(false);
  const [participantDialogMode, setParticipantDialogMode] = useState<"create" | "update">("create");
  const [selectedCategory, setSelectedCategory] = useState(initialCategory);
  const [importCategory, setImportCategory] = useState(initialCategory);
  const [currentPage, setCurrentPage] = useState(1);
  const [participantForm, setParticipantForm] = useState(emptyParticipantForm(nextSortOrder));
  const pageSize = 10;

  const participantsByCategory = useMemo(() => {
    const groups = new Map<string, any[]>();

    for (const category of event.participant_categories ?? []) {
      groups.set(category.key, []);
    }

    for (const participant of event.participants ?? []) {
      const bucket = groups.get(participant.category) ?? [];
      bucket.push(participant);
      groups.set(participant.category, bucket);
    }

    return Array.from(groups.entries()).map(([key, participants]) => ({
      key,
      label:
        (event.participant_categories ?? []).find((category: any) => category.key === key)?.label ??
        key.replaceAll("_", " "),
      participants,
    })).sort((left, right) => participantCategoryOrder.indexOf(left.key) - participantCategoryOrder.indexOf(right.key));
  }, [event.participant_categories, event.participants]);

  const activeGroup =
    participantsByCategory.find((group) => group.key === selectedCategory) ??
    participantsByCategory[0] ?? {
      key: initialCategory,
      label: "Participants",
      participants: [],
    };
  const totalPages = Math.max(1, Math.ceil(activeGroup.participants.length / pageSize));
  const safeCurrentPage = Math.min(currentPage, totalPages);
  const paginatedParticipants = activeGroup.participants.slice((safeCurrentPage - 1) * pageSize, safeCurrentPage * pageSize);

  const openCreateParticipantDialog = (category = selectedCategory) => {
    setParticipantDialogMode("create");
    setParticipantForm({
      ...emptyParticipantForm(nextSortOrder),
      category,
    });
    setParticipantDialogOpen(true);
  };

  const openUpdateParticipantDialog = (participant: any) => {
    setParticipantDialogMode("update");
    setParticipantForm({
      id: participant.id,
      category: participant.category ?? "attendee",
      name: participant.name ?? "",
      surname: participant.surname ?? "",
      title: participant.title ?? "",
      organization_name: participant.organization_name ?? "",
      topic: participant.topic ?? "",
      bio: participant.bio ?? "",
      email: participant.email ?? "",
      phone: participant.phone ?? "",
      role: participant.role ?? "",
      attendance_type: participant.attendance_type ?? "",
      attendance_status: participant.attendance_status ?? "registered",
      notes: participant.notes ?? "",
      sort_order: String(participant.sort_order ?? 1),
    });
    setParticipantDialogOpen(true);
  };

  const submitParticipant = () => {
    const payload = {
      category: participantForm.category,
      name: participantForm.name,
      surname: participantForm.surname || null,
      title: participantForm.title || null,
      organization_name: participantForm.organization_name || null,
      topic: participantForm.topic || null,
      bio: participantForm.bio || null,
      email: participantForm.email || null,
      phone: participantForm.phone || null,
      role: participantForm.role || null,
      attendance_type: participantForm.attendance_type || null,
      attendance_status: participantForm.attendance_status,
      notes: participantForm.notes || null,
      sort_order: Number(participantForm.sort_order) || 1,
    };

    const options = {
      onSuccess: () => {
        setParticipantDialogOpen(false);
        setParticipantForm(emptyParticipantForm(nextSortOrder));
      },
    };

    if (participantDialogMode === "update" && participantForm.id) {
      router.put(`/events/${event.id}/participants/${participantForm.id}`, payload, options);
      return;
    }

    router.post(`/events/${event.id}/participants`, payload, options);
  };

  const submitImport = () => {
    const file = fileInputRef.current?.files?.[0];
    if (!file) return;

    router.post(
      `/events/${event.id}/participants/import`,
      { file, category_context: importCategory },
      {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          setImportDialogOpen(false);
          if (fileInputRef.current) {
            fileInputRef.current.value = "";
          }
        },
      }
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`${event.title} Participants`} />

      <div className="space-y-6 p-4">
        {flash?.success ? (
          <div className="rounded-md border border-green-300 bg-green-50 px-3 py-2 text-sm text-green-800">
            {String(flash.success)}
          </div>
        ) : null}

        {importErrors.length > 0 ? (
          <div className="rounded-md border border-amber-300 bg-amber-50 px-3 py-3 text-sm text-amber-800">
            <div className="font-semibold">Import errors</div>
            <ul className="mt-2 list-disc space-y-1 pl-5">
              {importErrors.map((error) => (
                <li key={error}>{error}</li>
              ))}
            </ul>
          </div>
        ) : null}

        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">{event.title} Participants</h1>
            <p className="text-sm text-muted-foreground">
              Manage each participant class separately so the event register stays focused and operationally clear.
            </p>
          </div>
          <DomainNav items={eventWorkflowNav(event.id)} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
              {(event.participant_categories ?? []).map((category: any) => (
            <Card key={category.key} className="border-slate-200 shadow-sm">
              <CardHeader className="pb-3">
                <CardTitle className="text-sm">{category.label}</CardTitle>
              </CardHeader>
              <CardContent className="text-3xl font-semibold text-slate-950">{category.count}</CardContent>
            </Card>
          ))}
        </div>

        {canManage ? (
          <Card className="border-slate-200 shadow-sm">
            <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <CardTitle>Participant Actions</CardTitle>
                <CardDescription>Keep the register readable and open short modal flows only when you need to add or import.</CardDescription>
              </div>
              <div className="flex flex-wrap gap-2">
                <Button variant="outline" onClick={() => setImportDialogOpen(true)}>
                  <Upload className="h-4 w-4" />
                  Import Spreadsheet
                </Button>
                <Button onClick={() => openCreateParticipantDialog()}>
                  <Plus className="h-4 w-4" />
                  Add {activeGroup.label.replaceAll(" / ", " ")}
                </Button>
              </div>
            </CardHeader>
          </Card>
        ) : null}

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle>Participant Categories</CardTitle>
            <CardDescription>Select one participant class at a time to manage its register and updates.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex flex-wrap gap-2">
              {participantsByCategory.map((group) => (
                <button
                  key={group.key}
                  type="button"
                  onClick={() => {
                    setSelectedCategory(group.key);
                    setImportCategory(group.key);
                    setCurrentPage(1);
                  }}
                  className={`inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition ${
                    selectedCategory === group.key
                      ? "border-red-200 bg-red-50 text-red-700"
                      : "border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50"
                  }`}
                >
                  <span>{group.label}</span>
                  <span className="rounded-full bg-white/80 px-2 py-0.5 text-xs">{group.participants.length}</span>
                </button>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <CardTitle>{activeGroup.label}</CardTitle>
              <CardDescription>{activeGroup.participants.length} participants in this category</CardDescription>
            </div>
            {canManage ? (
              <Button onClick={() => openCreateParticipantDialog(activeGroup.key)}>
                <Plus className="h-4 w-4" />
                Add {activeGroup.label.replaceAll(" / ", " ")}
              </Button>
            ) : null}
          </CardHeader>
          <CardContent>
            {activeGroup.participants.length === 0 ? (
              <div className="rounded-md border border-dashed bg-white px-3 py-4 text-sm text-muted-foreground">
                No participants recorded in this category yet.
              </div>
            ) : (
              <div className="space-y-4">
                <div className="overflow-hidden rounded-lg border border-slate-200">
                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-slate-200 text-sm">
                      <thead className="bg-slate-50">
                        <tr>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Name</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Organisation / Role</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Attendance Type</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Checked In</th>
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100 bg-white">
                        {paginatedParticipants.map((participant: any) => (
                          <tr key={participant.id}>
                            <td className="px-4 py-3 align-top">
                              <div className="font-medium text-slate-900">{[participant.name, participant.surname].filter(Boolean).join(" ")}</div>
                              <div className="mt-1 text-xs text-slate-500">
                                {[participant.title, participant.topic].filter(Boolean).join(" | ") || "-"}
                              </div>
                            </td>
                            <td className="px-4 py-3 align-top text-slate-700">
                              {[participant.organization_name, participant.role].filter(Boolean).join(" | ") || "-"}
                            </td>
                            <td className="px-4 py-3 align-top text-slate-700">
                              <div>{participant.email ?? "-"}</div>
                              <div className="mt-1 text-xs text-slate-500">{participant.phone ?? "-"}</div>
                            </td>
                            <td className="px-4 py-3 align-top text-slate-700">{participant.attendance_type ?? "-"}</td>
                            <td className="px-4 py-3 align-top">
                              <span className={`rounded-full border px-2 py-1 text-[11px] font-medium ${statusBadgeClass(participant.attendance_status)}`}>
                                {String(participant.attendance_status).replaceAll("_", " ")}
                              </span>
                            </td>
                            <td className="px-4 py-3 align-top text-slate-700">{participant.checked_in_at ?? "-"}</td>
                            <td className="px-4 py-3 align-top">
                              {canManage ? (
                                <div className="flex flex-wrap gap-2">
                                  <select
                                    value={participant.attendance_status}
                                    onChange={(e) =>
                                      router.post(`/events/${event.id}/participants/${participant.id}/status`, {
                                        attendance_status: e.target.value,
                                      })
                                    }
                                    className="rounded-md border border-slate-300 px-3 py-2 text-xs"
                                  >
                                    {participantStatusOptions.map((option) => (
                                      <option key={option.value} value={option.value}>
                                        {option.label}
                                      </option>
                                    ))}
                                  </select>
                                  <Button type="button" variant="outline" size="sm" onClick={() => openUpdateParticipantDialog(participant)}>
                                    <Pencil className="h-4 w-4" />
                                    Edit
                                  </Button>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      if (!window.confirm(`Remove ${participant.name} from this event?`)) return;
                                      router.delete(`/events/${event.id}/participants/${participant.id}`);
                                    }}
                                    className="rounded-md border border-red-600 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-600 hover:text-white"
                                  >
                                    Remove
                                  </button>
                                </div>
                              ) : (
                                <div className="text-xs text-slate-500">{participant.notes ?? "-"}</div>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="text-sm text-slate-500">
                    Showing {(safeCurrentPage - 1) * pageSize + 1} to {Math.min(safeCurrentPage * pageSize, activeGroup.participants.length)} of{" "}
                    {activeGroup.participants.length} {activeGroup.label.toLowerCase()}
                  </div>
                  <div className="flex items-center gap-2">
                    <Button variant="outline" size="sm" onClick={() => setCurrentPage((page) => Math.max(1, page - 1))} disabled={safeCurrentPage === 1}>
                      Previous
                    </Button>
                    <div className="text-sm text-slate-600">
                      Page {safeCurrentPage} of {totalPages}
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
                      disabled={safeCurrentPage === totalPages}
                    >
                      Next
                    </Button>
                  </div>
                </div>
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Dialog open={participantDialogOpen} onOpenChange={setParticipantDialogOpen}>
        <DialogContent className="sm:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{participantDialogMode === "update" ? "Quick Update Participant" : "Add Participant"}</DialogTitle>
            <DialogDescription>
              Use this short modal to capture or update participant details without leaving the register view.
            </DialogDescription>
          </DialogHeader>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="participant-category">Category</Label>
              <select
                id="participant-category"
                value={participantForm.category}
                onChange={(e) => setParticipantForm((current) => ({ ...current, category: e.target.value }))}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
              >
                {participantCategoryOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-status">Attendance status</Label>
              <select
                id="participant-status"
                value={participantForm.attendance_status}
                onChange={(e) => setParticipantForm((current) => ({ ...current, attendance_status: e.target.value }))}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
              >
                {participantStatusOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-name">Participant name</Label>
              <Input
                id="participant-name"
                value={participantForm.name}
                onChange={(e) => setParticipantForm((current) => ({ ...current, name: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-surname">Surname</Label>
              <Input
                id="participant-surname"
                value={participantForm.surname}
                onChange={(e) => setParticipantForm((current) => ({ ...current, surname: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-title">Title</Label>
              <Input
                id="participant-title"
                value={participantForm.title}
                onChange={(e) => setParticipantForm((current) => ({ ...current, title: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-organization">Organization</Label>
              <Input
                id="participant-organization"
                value={participantForm.organization_name}
                onChange={(e) => setParticipantForm((current) => ({ ...current, organization_name: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-role">Role</Label>
              <Input
                id="participant-role"
                value={participantForm.role}
                onChange={(e) => setParticipantForm((current) => ({ ...current, role: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-attendance-type">Attendance type</Label>
              <Input
                id="participant-attendance-type"
                value={participantForm.attendance_type}
                onChange={(e) => setParticipantForm((current) => ({ ...current, attendance_type: e.target.value }))}
                placeholder={attendanceTypePlaceholder(participantForm.category)}
              />
              {participantForm.category === "attendee" ? (
                <p className="text-xs text-slate-500">Attendance type is required for attendees so event registers can distinguish physical, virtual, and hybrid presence.</p>
              ) : null}
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-topic">Topic / session</Label>
              <Input
                id="participant-topic"
                value={participantForm.topic}
                onChange={(e) => setParticipantForm((current) => ({ ...current, topic: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-sort-order">Sort order</Label>
              <Input
                id="participant-sort-order"
                type="number"
                min={1}
                value={participantForm.sort_order}
                onChange={(e) => setParticipantForm((current) => ({ ...current, sort_order: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-email">Email</Label>
              <Input
                id="participant-email"
                value={participantForm.email}
                onChange={(e) => setParticipantForm((current) => ({ ...current, email: e.target.value }))}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-phone">Phone</Label>
              <Input
                id="participant-phone"
                value={participantForm.phone}
                onChange={(e) => setParticipantForm((current) => ({ ...current, phone: e.target.value }))}
              />
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="participant-bio">Bio / profile context</Label>
              <textarea
                id="participant-bio"
                value={participantForm.bio}
                onChange={(e) => setParticipantForm((current) => ({ ...current, bio: e.target.value }))}
                className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
              />
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="participant-notes">Operational notes</Label>
              <textarea
                id="participant-notes"
                value={participantForm.notes}
                onChange={(e) => setParticipantForm((current) => ({ ...current, notes: e.target.value }))}
                className="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setParticipantDialogOpen(false)}>
              Cancel
            </Button>
            <Button
              onClick={submitParticipant}
              disabled={!participantForm.name.trim() || (participantForm.category === "attendee" && !participantForm.attendance_type.trim())}
            >
              {participantDialogMode === "update" ? "Save Update" : "Add Participant"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
        <DialogContent className="sm:max-w-xl">
          <DialogHeader>
            <DialogTitle>Import Participants</DialogTitle>
            <DialogDescription>
              Upload the external registration export as CSV, TXT, or XLSX. Choose the category this file belongs to so collaborator records are sorted correctly, for example into Facilitators or Partners.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
              Imported fields are mapped into: name, surname, title, email, contact number, attendance type, plus any available organisation and role fields.
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-import-category">Import into category</Label>
              <select
                id="participant-import-category"
                value={importCategory}
                onChange={(e) => setImportCategory(e.target.value)}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
              >
                {participantCategoryOptions.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="participant-import-file">Spreadsheet file</Label>
              <Input id="participant-import-file" ref={fileInputRef} type="file" accept=".csv,.txt,.xlsx" />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setImportDialogOpen(false)}>
              Cancel
            </Button>
            <Button onClick={submitImport}>Import Spreadsheet</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}
