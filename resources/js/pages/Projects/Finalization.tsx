import { Head, Link, router } from '@inertiajs/react';
import {
    CalendarCheck2,
    ChevronDown,
    FileCheck2,
    FileText,
    FolderArchive,
    ScrollText,
} from 'lucide-react';
import { useState } from 'react';

import { DomainNav } from '@/components/domain-nav';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { projectNavItems } from '@/config/domain-nav/projects';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Project View', href: '#' },
    { title: 'Project Finalization', href: '#' },
];

const categoryLabel: Record<string, string> = {
    evidence: 'Project Evidence',
    registers: 'Registers',
};

export default function ProjectFinalization({
    project,
    closure,
    closureEvidence,
    reports,
    history,
    canManageProjects,
    canManageGovernance,
}: {
    project: any;
    closure: any | null;
    closureEvidence: any[];
    reports: any[];
    history: any[];
    canManageProjects: boolean;
    canManageGovernance: boolean;
}) {
    const projectData = project?.data ?? project;
    const [openSection, setOpenSection] = useState<string>('evidence');
    const [closureForm, setClosureForm] = useState({
        closure_date:
            projectData.end_date ?? new Date().toISOString().slice(0, 10),
        signoff_notes: closure?.signoff_notes ?? '',
        final_report_summary: closure?.final_report_summary ?? '',
        report_title: `${projectData.name} Final Report`,
        key_findings: '',
        recommendations: '',
    });
    const [reportForm, setReportForm] = useState({
        report_type: projectData.status === 'completed' ? 'final' : 'progress',
        report_date: new Date().toISOString().slice(0, 10),
        title: '',
        executive_summary: '',
        key_findings: '',
        recommendations: '',
    });
    const [evidenceForm, setEvidenceForm] = useState({
        title: '',
        notes: '',
        file: null as File | null,
    });
    const [registerForm, setRegisterForm] = useState({
        title: '',
        notes: '',
        file: null as File | null,
    });

    const evidenceByCategory = {
        evidence: closureEvidence.filter(
            (item) => (item.category ?? 'evidence') === 'evidence',
        ),
        registers: closureEvidence.filter(
            (item) => item.category === 'registers',
        ),
    };

    const submitEvidence = (
        event: React.FormEvent,
        category: 'evidence' | 'registers',
        form: { title: string; notes: string; file: File | null },
    ) => {
        event.preventDefault();

        router.post(
            `/projects/${projectData.id}/closure-evidence`,
            {
                category,
                title: form.title,
                notes: form.notes,
                file: form.file,
            },
            {
                forceFormData: true,
            },
        );
    };

    const submitReport = (event: React.FormEvent) => {
        event.preventDefault();

        router.post(`/projects/${projectData.id}/reports`, reportForm);
    };

    const submitClosure = (event: React.FormEvent) => {
        event.preventDefault();

        router.post(`/projects/${projectData.id}/conclude`, closureForm);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Project Finalization" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <Link
                                href={`/projects/${projectData.id}`}
                                className="inline-flex rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            >
                                Back to project
                            </Link>
                            {canManageProjects ? (
                                <Link
                                    href={`/projects/${projectData.id}/edit`}
                                    className="inline-flex rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50"
                                >
                                    Edit Project
                                </Link>
                            ) : null}
                        </div>
                        <div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                {projectData.name} Finalization
                            </h1>
                            <p className="text-sm text-slate-600">
                                Complete closure in sequence: upload evidence,
                                upload registers, generate reports, then sign
                                off the project.
                            </p>
                        </div>
                    </div>

                    <DomainNav items={projectNavItems} />
                </div>

                <div className="flex flex-wrap gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                    <Link
                        href={`/projects/${projectData.id}`}
                        className="rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:bg-white hover:text-slate-900"
                    >
                        Overview
                    </Link>
                    <Link
                        href={`/projects/${projectData.id}/finalization`}
                        className="rounded-md bg-white px-4 py-2 text-sm font-medium text-slate-900 shadow-sm"
                    >
                        Finalization
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-4">
                    <SummaryCard
                        icon={<FolderArchive className="h-4 w-4" />}
                        title="Status"
                        value={closure ? 'Finalized' : 'Open'}
                        detail={
                            closure?.closure_date
                                ? `Closed ${closure.closure_date}`
                                : 'Awaiting sign-off'
                        }
                    />
                    <SummaryCard
                        icon={<FileCheck2 className="h-4 w-4" />}
                        title="Evidence"
                        value={String(evidenceByCategory.evidence.length)}
                        detail="Closure evidence files"
                    />
                    <SummaryCard
                        icon={<ScrollText className="h-4 w-4" />}
                        title="Registers"
                        value={String(evidenceByCategory.registers.length)}
                        detail="Register uploads"
                    />
                    <SummaryCard
                        icon={<FileText className="h-4 w-4" />}
                        title="Reports"
                        value={String(reports.length)}
                        detail="Generated outputs"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Finalization Checklist</CardTitle>
                        <CardDescription>
                            Work through each section below instead of closing
                            the project on the main project page.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 md:grid-cols-4">
                        <ChecklistItem
                            number="1"
                            title="Project evidence"
                            description="Upload outcome evidence and supporting proof."
                        />
                        <ChecklistItem
                            number="2"
                            title="Registers"
                            description="Upload final registers and attendance records."
                        />
                        <ChecklistItem
                            number="3"
                            title="Reports"
                            description="Generate progress or final reporting outputs."
                        />
                        <ChecklistItem
                            number="4"
                            title="Sign-off"
                            description="Set the closure date and finalize the project."
                        />
                    </CardContent>
                </Card>

                <div className="space-y-4">
                    <SectionCollapsible
                        title="Project Evidence"
                        description="Upload evidence files used to support final project closure."
                        icon={<FileCheck2 className="h-4 w-4" />}
                        open={openSection === 'evidence'}
                        onOpenChange={(open) =>
                            setOpenSection(open ? 'evidence' : '')
                        }
                    >
                        {canManageGovernance ? (
                            <form
                                onSubmit={(event) =>
                                    submitEvidence(
                                        event,
                                        'evidence',
                                        evidenceForm,
                                    )
                                }
                                className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4"
                            >
                                <div className="grid gap-3 md:grid-cols-2">
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Evidence title
                                        </label>
                                        <input
                                            type="text"
                                            value={evidenceForm.title}
                                            onChange={(event) =>
                                                setEvidenceForm((current) => ({
                                                    ...current,
                                                    title: event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                            placeholder="Outcome summary pack"
                                        />
                                    </Field>
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            File
                                        </label>
                                        <input
                                            type="file"
                                            onChange={(event) =>
                                                setEvidenceForm((current) => ({
                                                    ...current,
                                                    file:
                                                        event.target
                                                            .files?.[0] ?? null,
                                                }))
                                            }
                                            className="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                </div>
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Notes
                                    </label>
                                    <textarea
                                        value={evidenceForm.notes}
                                        onChange={(event) =>
                                            setEvidenceForm((current) => ({
                                                ...current,
                                                notes: event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder="Explain what this evidence proves for closure."
                                    />
                                </Field>
                                <Button type="submit" className="w-fit">
                                    Upload project evidence
                                </Button>
                            </form>
                        ) : null}

                        <EvidenceList
                            category="evidence"
                            items={evidenceByCategory.evidence}
                            projectId={projectData.id}
                            canManageGovernance={canManageGovernance}
                        />
                    </SectionCollapsible>

                    <SectionCollapsible
                        title="Registers"
                        description="Upload the final registers separately so closure support stays structured."
                        icon={<ScrollText className="h-4 w-4" />}
                        open={openSection === 'registers'}
                        onOpenChange={(open) =>
                            setOpenSection(open ? 'registers' : '')
                        }
                    >
                        {canManageGovernance ? (
                            <form
                                onSubmit={(event) =>
                                    submitEvidence(
                                        event,
                                        'registers',
                                        registerForm,
                                    )
                                }
                                className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4"
                            >
                                <div className="grid gap-3 md:grid-cols-2">
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Register title
                                        </label>
                                        <input
                                            type="text"
                                            value={registerForm.title}
                                            onChange={(event) =>
                                                setRegisterForm((current) => ({
                                                    ...current,
                                                    title: event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                            placeholder="Final attendance register"
                                        />
                                    </Field>
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            File
                                        </label>
                                        <input
                                            type="file"
                                            onChange={(event) =>
                                                setRegisterForm((current) => ({
                                                    ...current,
                                                    file:
                                                        event.target
                                                            .files?.[0] ?? null,
                                                }))
                                            }
                                            className="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                </div>
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Notes
                                    </label>
                                    <textarea
                                        value={registerForm.notes}
                                        onChange={(event) =>
                                            setRegisterForm((current) => ({
                                                ...current,
                                                notes: event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder="Capture which register this file represents."
                                    />
                                </Field>
                                <Button type="submit" className="w-fit">
                                    Upload register
                                </Button>
                            </form>
                        ) : null}

                        <EvidenceList
                            category="registers"
                            items={evidenceByCategory.registers}
                            projectId={projectData.id}
                            canManageGovernance={canManageGovernance}
                        />
                    </SectionCollapsible>

                    <SectionCollapsible
                        title="Reports"
                        description="Generate progress or final reports inside the closure workflow."
                        icon={<FileText className="h-4 w-4" />}
                        open={openSection === 'reports'}
                        onOpenChange={(open) =>
                            setOpenSection(open ? 'reports' : '')
                        }
                    >
                        {canManageGovernance ? (
                            <form
                                onSubmit={submitReport}
                                className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4"
                            >
                                <div className="grid gap-3 md:grid-cols-2">
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Report type
                                        </label>
                                        <select
                                            value={reportForm.report_type}
                                            onChange={(event) =>
                                                setReportForm((current) => ({
                                                    ...current,
                                                    report_type:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        >
                                            <option value="progress">
                                                Progress
                                            </option>
                                            <option
                                                value="final"
                                                disabled={
                                                    projectData.status !==
                                                    'completed'
                                                }
                                            >
                                                Final
                                            </option>
                                        </select>
                                    </Field>
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Report date
                                        </label>
                                        <input
                                            type="date"
                                            value={reportForm.report_date}
                                            onChange={(event) =>
                                                setReportForm((current) => ({
                                                    ...current,
                                                    report_date:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                </div>

                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Title
                                    </label>
                                    <input
                                        type="text"
                                        value={reportForm.title}
                                        onChange={(event) =>
                                            setReportForm((current) => ({
                                                ...current,
                                                title: event.target.value,
                                            }))
                                        }
                                        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder={`${projectData.name} Progress Report`}
                                    />
                                </Field>

                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Executive summary
                                    </label>
                                    <textarea
                                        value={reportForm.executive_summary}
                                        onChange={(event) =>
                                            setReportForm((current) => ({
                                                ...current,
                                                executive_summary:
                                                    event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                </Field>

                                <div className="grid gap-3 md:grid-cols-2">
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Key findings
                                        </label>
                                        <textarea
                                            value={reportForm.key_findings}
                                            onChange={(event) =>
                                                setReportForm((current) => ({
                                                    ...current,
                                                    key_findings:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Recommendations
                                        </label>
                                        <textarea
                                            value={reportForm.recommendations}
                                            onChange={(event) =>
                                                setReportForm((current) => ({
                                                    ...current,
                                                    recommendations:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                </div>

                                <Button type="submit" className="w-fit">
                                    Generate report
                                </Button>
                            </form>
                        ) : null}

                        {reports.length === 0 ? (
                            <EmptyState message="No reports have been generated yet." />
                        ) : (
                            <div className="space-y-3">
                                {reports.map((report) => (
                                    <div
                                        key={report.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium text-slate-900">
                                                    {report.title}
                                                </div>
                                                <div className="text-xs tracking-wide text-muted-foreground uppercase">
                                                    {report.report_type} report
                                                    |{' '}
                                                    {report.report_date ?? '-'}
                                                </div>
                                                <div className="mt-1 text-xs text-muted-foreground">
                                                    Created by:{' '}
                                                    {report.created_by_name ??
                                                        '-'}
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    window.location.assign(
                                                        `/projects/${projectData.id}/reports/${report.id}/pdf`,
                                                    )
                                                }
                                            >
                                                Download PDF
                                            </Button>
                                        </div>
                                        {report.executive_summary ? (
                                            <p className="mt-3 text-sm text-slate-700">
                                                {report.executive_summary}
                                            </p>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        )}
                    </SectionCollapsible>

                    <SectionCollapsible
                        title="Final Sign-off"
                        description="Capture the closure date and conclude the project after everything else is ready."
                        icon={<CalendarCheck2 className="h-4 w-4" />}
                        open={openSection === 'signoff'}
                        onOpenChange={(open) =>
                            setOpenSection(open ? 'signoff' : '')
                        }
                    >
                        {closure ? (
                            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm">
                                <div className="font-semibold text-emerald-900">
                                    Project finalized
                                </div>
                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                    <div>
                                        <div className="text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                            Closure date
                                        </div>
                                        <div className="mt-1 text-emerald-900">
                                            {closure.closure_date ?? '-'}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                            Concluded by
                                        </div>
                                        <div className="mt-1 text-emerald-900">
                                            {closure.concluded_by_name ?? '-'}
                                        </div>
                                    </div>
                                </div>
                                {closure.final_report_summary ? (
                                    <div className="mt-3">
                                        <div className="text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                            Final summary
                                        </div>
                                        <p className="mt-1 whitespace-pre-wrap text-emerald-900">
                                            {closure.final_report_summary}
                                        </p>
                                    </div>
                                ) : null}
                                {closure.signoff_notes ? (
                                    <div className="mt-3">
                                        <div className="text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                            Sign-off notes
                                        </div>
                                        <p className="mt-1 whitespace-pre-wrap text-emerald-900">
                                            {closure.signoff_notes}
                                        </p>
                                    </div>
                                ) : null}
                            </div>
                        ) : canManageGovernance ? (
                            <form
                                onSubmit={submitClosure}
                                className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4"
                            >
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Closure date
                                    </label>
                                    <input
                                        type="date"
                                        value={closureForm.closure_date}
                                        onChange={(event) =>
                                            setClosureForm((current) => ({
                                                ...current,
                                                closure_date:
                                                    event.target.value,
                                            }))
                                        }
                                        className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                </Field>
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Final summary
                                    </label>
                                    <textarea
                                        value={closureForm.final_report_summary}
                                        onChange={(event) =>
                                            setClosureForm((current) => ({
                                                ...current,
                                                final_report_summary:
                                                    event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder="Summarize the project outcome and closure rationale."
                                    />
                                </Field>
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Sign-off notes
                                    </label>
                                    <textarea
                                        value={closureForm.signoff_notes}
                                        onChange={(event) =>
                                            setClosureForm((current) => ({
                                                ...current,
                                                signoff_notes:
                                                    event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        placeholder="Capture handover notes, final comments, or decision notes."
                                    />
                                </Field>
                                <div className="grid gap-3 md:grid-cols-2">
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Final report title
                                        </label>
                                        <input
                                            type="text"
                                            value={closureForm.report_title}
                                            onChange={(event) =>
                                                setClosureForm((current) => ({
                                                    ...current,
                                                    report_title:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                    <Field>
                                        <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                            Key findings
                                        </label>
                                        <input
                                            type="text"
                                            value={closureForm.key_findings}
                                            onChange={(event) =>
                                                setClosureForm((current) => ({
                                                    ...current,
                                                    key_findings:
                                                        event.target.value,
                                                }))
                                            }
                                            className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                        />
                                    </Field>
                                </div>
                                <Field>
                                    <label className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                        Recommendations
                                    </label>
                                    <textarea
                                        value={closureForm.recommendations}
                                        onChange={(event) =>
                                            setClosureForm((current) => ({
                                                ...current,
                                                recommendations:
                                                    event.target.value,
                                            }))
                                        }
                                        className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                                    />
                                </Field>
                                <Button
                                    type="submit"
                                    className="w-fit bg-emerald-600 hover:bg-emerald-700"
                                >
                                    Finalize project
                                </Button>
                            </form>
                        ) : (
                            <EmptyState message="Only project managers and project administrators can finalize this project." />
                        )}
                    </SectionCollapsible>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Governance History</CardTitle>
                        <CardDescription>
                            Recent finalization and reporting activity.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {history.length === 0 ? (
                            <EmptyState message="No project history has been recorded yet." />
                        ) : (
                            <div className="space-y-3">
                                {history.slice(0, 10).map((item) => (
                                    <div
                                        key={item.id}
                                        className="rounded-lg border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div className="font-medium text-slate-900">
                                                    {item.summary}
                                                </div>
                                                <div className="text-xs tracking-wide text-muted-foreground uppercase">
                                                    {String(
                                                        item.action,
                                                    ).replaceAll('_', ' ')}
                                                </div>
                                            </div>
                                            <div className="text-right text-xs text-muted-foreground">
                                                <div>
                                                    {item.actor_name ??
                                                        'System'}
                                                </div>
                                                <div>
                                                    {item.created_at ?? '-'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function SectionCollapsible({
    title,
    description,
    icon,
    open,
    onOpenChange,
    children,
}: {
    title: string;
    description: string;
    icon: React.ReactNode;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    children: React.ReactNode;
}) {
    return (
        <Collapsible open={open} onOpenChange={onOpenChange}>
            <Card>
                <CardHeader>
                    <CollapsibleTrigger className="flex w-full items-center justify-between gap-4 text-left">
                        <div className="flex items-start gap-3">
                            <div className="mt-0.5 rounded-md border border-slate-200 bg-slate-50 p-2 text-slate-700">
                                {icon}
                            </div>
                            <div>
                                <CardTitle>{title}</CardTitle>
                                <CardDescription className="mt-1">
                                    {description}
                                </CardDescription>
                            </div>
                        </div>
                        <ChevronDown
                            className={cn(
                                'h-4 w-4 text-slate-500 transition-transform',
                                open && 'rotate-180',
                            )}
                        />
                    </CollapsibleTrigger>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent className="space-y-4 text-sm">
                        {children}
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>
    );
}

function EvidenceList({
    category,
    items,
    projectId,
    canManageGovernance,
}: {
    category: 'evidence' | 'registers';
    items: any[];
    projectId: number;
    canManageGovernance: boolean;
}) {
    if (items.length === 0) {
        return (
            <EmptyState
                message={`No ${categoryLabel[category].toLowerCase()} uploaded yet.`}
            />
        );
    }

    return (
        <div className="space-y-3">
            {items.map((item) => (
                <div key={item.id} className="rounded-lg border p-3">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div className="font-medium text-slate-900">
                                {item.title}
                            </div>
                            <div className="text-xs tracking-wide text-muted-foreground uppercase">
                                {categoryLabel[item.category ?? category] ??
                                    'Evidence'}{' '}
                                | {item.file_name}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {item.uploaded_by_name ?? '-'} |{' '}
                                {item.created_at ?? '-'}
                            </div>
                            {item.notes ? (
                                <p className="mt-2 text-sm text-slate-700">
                                    {item.notes}
                                </p>
                            ) : null}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() =>
                                    window.location.assign(
                                        `/projects/${projectId}/closure-evidence/${item.id}`,
                                    )
                                }
                            >
                                Download
                            </Button>
                            {canManageGovernance ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="border-rose-200 text-rose-700 hover:bg-rose-50 hover:text-rose-700"
                                    onClick={() =>
                                        router.delete(
                                            `/projects/${projectId}/closure-evidence/${item.id}`,
                                        )
                                    }
                                >
                                    Remove
                                </Button>
                            ) : null}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}

function SummaryCard({
    icon,
    title,
    value,
    detail,
}: {
    icon: React.ReactNode;
    title: string;
    value: string;
    detail: string;
}) {
    return (
        <Card>
            <CardHeader className="space-y-3">
                <div className="flex items-center justify-between">
                    <CardDescription>{title}</CardDescription>
                    <div className="rounded-md border border-slate-200 bg-slate-50 p-2 text-slate-700">
                        {icon}
                    </div>
                </div>
                <CardTitle className="text-2xl">{value}</CardTitle>
            </CardHeader>
            <CardContent className="text-sm text-slate-600">
                {detail}
            </CardContent>
        </Card>
    );
}

function ChecklistItem({
    number,
    title,
    description,
}: {
    number: string;
    title: string;
    description: string;
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                Step {number}
            </div>
            <div className="mt-2 font-medium text-slate-900">{title}</div>
            <p className="mt-1 text-sm text-slate-600">{description}</p>
        </div>
    );
}

function EmptyState({ message }: { message: string }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-sm text-muted-foreground">
            {message}
        </div>
    );
}

function Field({ children }: { children: React.ReactNode }) {
    return <div>{children}</div>;
}
