import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardCheck,
    Filter,
    Save,
    XCircle,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import { DomainNav } from '@/components/domain-nav';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { projectNavItems } from '@/config/domain-nav/projects';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Projects', href: '/projects' },
    { title: 'Locations', href: '/project-locations' },
    { title: 'Progress', href: '#' },
];

type AssessmentStatus = 'completed' | 'failed' | string;

type AssessmentRecord = {
    status: AssessmentStatus;
    score: number | null;
    comments: string | null;
    assessed_at: string | null;
};

type MilestoneOption = {
    id: number;
    title: string;
    description: string | null;
    max_score: number | null;
    pass_mark: number | null;
    is_required: boolean;
    expected_timing: string | null;
};

type MilestoneProgress = MilestoneOption & {
    total: number;
    assessed: number;
    passed: number;
    failed: number;
    pass_rate: number;
};

type BeneficiaryProgress = {
    id: number;
    name: string;
    assessed_milestones: number;
    passed_milestones: number;
    failed_milestones: number;
    overall_progress: number;
    status: string;
    assessments: Record<number, AssessmentRecord>;
};

type LocationSummary = {
    beneficiaries_enrolled: number;
    milestones_attached: number;
    assessments_completed: number;
    passed_assessments: number;
    failed_assessments: number;
    outstanding_assessments: number;
    assessment_coverage_rate: number;
    pass_rate: number;
};

type BulkScoreState = Record<number, { score: string; comments: string }>;

const statusTone = (status: string) => {
    if (status === 'Passed' || status === 'completed') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (status === 'Failed' || status === 'failed') {
        return 'border-red-200 bg-red-50 text-red-700';
    }

    if (status === 'In progress') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    return 'border-slate-200 bg-slate-50 text-slate-600';
};

const assessmentLabel = (assessment?: AssessmentRecord) => {
    if (!assessment) {
        return 'Not assessed';
    }

    return assessment.status === 'completed' ? 'Passed' : 'Failed';
};

const defaultPassMark = (milestone: MilestoneOption | null) => {
    if (!milestone?.max_score) {
        return null;
    }

    return milestone.pass_mark ?? Math.ceil(milestone.max_score * 0.5);
};

export default function ProjectLocationProgress({
    location,
    milestones,
    milestoneOptions,
    beneficiaries,
    totalMilestones,
    summary,
    canAssess,
    assessmentUnavailableMessage,
}: {
    location: {
        id?: number;
        project_name: string | null;
        program_name: string | null;
        project_status: string | null;
        province: string | null;
        facilitator_name: string | null;
    };
    milestones: MilestoneProgress[];
    milestoneOptions: MilestoneOption[];
    beneficiaries: BeneficiaryProgress[];
    totalMilestones: number;
    summary: LocationSummary;
    canAssess: boolean;
    assessmentUnavailableMessage: string | null;
}) {
    const [open, setOpen] = useState(false);
    const [bulkOpen, setBulkOpen] = useState(false);
    const [beneficiaryId, setBeneficiaryId] = useState<number | null>(null);
    const [milestoneId, setMilestoneId] = useState<number | null>(
        milestoneOptions[0]?.id ?? null,
    );
    const [score, setScore] = useState<string>('');
    const [comments, setComments] = useState<string>('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [search, setSearch] = useState('');
    const [bulkScores, setBulkScores] = useState<BulkScoreState>({});

    const selectedBeneficiary = useMemo(
        () => beneficiaries.find((b) => b.id === beneficiaryId) ?? null,
        [beneficiaries, beneficiaryId],
    );

    const selectedMilestone = useMemo(
        () => milestoneOptions.find((m) => m.id === milestoneId) ?? null,
        [milestoneOptions, milestoneId],
    );

    const filteredBeneficiaries = useMemo(() => {
        const term = search.trim().toLowerCase();

        return beneficiaries.filter((beneficiary) => {
            const matchesSearch =
                term === '' || beneficiary.name.toLowerCase().includes(term);
            const matchesStatus =
                statusFilter === 'all' || beneficiary.status === statusFilter;

            return matchesSearch && matchesStatus;
        });
    }, [beneficiaries, search, statusFilter]);

    const assessmentRows = selectedMilestone
        ? filteredBeneficiaries.map((beneficiary) => ({
              beneficiary,
              assessment: beneficiary.assessments?.[selectedMilestone.id],
          }))
        : [];

    const openAssessmentModal = (
        beneficiary: BeneficiaryProgress,
        milestone: MilestoneOption,
    ) => {
        if (!canAssess) {
            return;
        }

        const assessment = beneficiary.assessments?.[milestone.id] ?? null;
        setBeneficiaryId(beneficiary.id);
        setMilestoneId(milestone.id);
        setScore(
            assessment?.score !== undefined && assessment?.score !== null
                ? String(assessment.score)
                : '',
        );
        setComments(assessment?.comments ?? '');
        setOpen(true);
    };

    const openBulkModal = () => {
        if (!canAssess || !selectedMilestone) {
            return;
        }

        setBulkScores(
            assessmentRows.reduce<BulkScoreState>((scores, row) => {
                scores[row.beneficiary.id] = {
                    score:
                        row.assessment?.score !== undefined &&
                        row.assessment?.score !== null
                            ? String(row.assessment.score)
                            : '',
                    comments: row.assessment?.comments ?? '',
                };

                return scores;
            }, {}),
        );
        setBulkOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!location.id || !beneficiaryId || !milestoneId || score === '')
            return;

        router.post(
            `/project-locations/${location.id}/assessments`,
            {
                beneficiary_id: beneficiaryId,
                project_milestone_id: milestoneId,
                score: Number(score),
                comments,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setOpen(false);
                    setScore('');
                    setComments('');
                },
            },
        );
    };

    const handleBulkSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!location.id || !selectedMilestone) return;

        const assessments = Object.entries(bulkScores)
            .filter(([, value]) => value.score !== '')
            .map(([beneficiary_id, value]) => ({
                beneficiary_id: Number(beneficiary_id),
                score: Number(value.score),
                comments: value.comments,
            }));

        if (assessments.length === 0) {
            return;
        }

        router.post(
            `/project-locations/${location.id}/assessments/bulk`,
            {
                project_milestone_id: selectedMilestone.id,
                assessments,
            },
            {
                preserveScroll: true,
                onSuccess: () => setBulkOpen(false),
            },
        );
    };

    const updateBulkScore = (
        beneficiaryIdValue: number,
        field: 'score' | 'comments',
        value: string,
    ) => {
        setBulkScores((current) => ({
            ...current,
            [beneficiaryIdValue]: {
                score: current[beneficiaryIdValue]?.score ?? '',
                comments: current[beneficiaryIdValue]?.comments ?? '',
                [field]: value,
            },
        }));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Location Progress" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">
                            Location Milestone Performance
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {location.project_name ?? 'Project'} •{' '}
                            {location.province ?? 'Location'}
                        </p>
                    </div>
                    <DomainNav items={projectNavItems} />
                </div>

                {assessmentUnavailableMessage ? (
                    <Alert className="border-amber-200 bg-amber-50 text-amber-900">
                        <ClipboardCheck className="h-4 w-4" />
                        <AlertTitle>
                            Assessment workspace unavailable
                        </AlertTitle>
                        <AlertDescription>
                            {assessmentUnavailableMessage}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Beneficiaries', summary.beneficiaries_enrolled],
                        ['Milestones', summary.milestones_attached],
                        ['Coverage', `${summary.assessment_coverage_rate}%`],
                        ['Pass rate', `${summary.pass_rate}%`],
                    ].map(([label, value]) => (
                        <Card key={label}>
                            <CardHeader>
                                <CardTitle>{label}</CardTitle>
                                <CardDescription>
                                    {location.program_name ??
                                        'Program milestone workflow'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="text-2xl font-semibold">
                                {value}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Delivery Context</CardTitle>
                        <CardDescription>
                            Program, project, location, and facilitator
                            currently being assessed.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <div className="text-muted-foreground">Program</div>
                            <div className="font-medium">
                                {location.program_name ?? '-'}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Project status
                            </div>
                            <Badge variant="outline" className="capitalize">
                                {location.project_status ?? '-'}
                            </Badge>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Facilitator
                            </div>
                            <div className="font-medium">
                                {location.facilitator_name ?? '-'}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Outstanding assessments
                            </div>
                            <div className="font-medium">
                                {summary.outstanding_assessments}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-6 xl:grid-cols-[0.9fr,1.1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Milestones</CardTitle>
                            <CardDescription>
                                Attached active milestones copied from the
                                program template set.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            {milestones.length === 0 ? (
                                <p className="text-muted-foreground">
                                    No milestones are attached to this project.
                                </p>
                            ) : (
                                milestones.map((milestone) => (
                                    <div
                                        key={milestone.id}
                                        className="rounded-md border p-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <div className="font-medium">
                                                    {milestone.title}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {milestone.is_required
                                                        ? 'Required'
                                                        : 'Optional'}{' '}
                                                    • Pass mark:{' '}
                                                    {defaultPassMark(
                                                        milestone,
                                                    ) ?? '-'}{' '}
                                                    /{' '}
                                                    {milestone.max_score ?? '-'}
                                                </div>
                                            </div>
                                            <Badge variant="outline">
                                                {milestone.pass_rate}% pass
                                            </Badge>
                                        </div>
                                        {milestone.description ? (
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                {milestone.description}
                                            </p>
                                        ) : null}
                                        <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
                                            <span>
                                                Assessed: {milestone.assessed}/
                                                {milestone.total}
                                            </span>
                                            <span className="text-emerald-700">
                                                Passed: {milestone.passed}
                                            </span>
                                            <span className="text-red-700">
                                                Failed: {milestone.failed}
                                            </span>
                                        </div>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Beneficiary Status</CardTitle>
                            <CardDescription>
                                Per-beneficiary milestone completion for this
                                delivery location.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {beneficiaries.length === 0 ? (
                                <p className="text-muted-foreground">
                                    No beneficiaries are enrolled at this
                                    location.
                                </p>
                            ) : (
                                beneficiaries.map((beneficiary) => (
                                    <div
                                        key={beneficiary.id}
                                        className="flex flex-wrap items-center justify-between gap-2 rounded-md border p-3"
                                    >
                                        <div>
                                            <Link
                                                href={`/beneficiaries/${beneficiary.id}`}
                                                className="font-medium text-red-600 hover:underline"
                                            >
                                                {beneficiary.name}
                                            </Link>
                                            <div className="text-xs text-muted-foreground">
                                                {
                                                    beneficiary.assessed_milestones
                                                }
                                                /{totalMilestones} assessed •{' '}
                                                {beneficiary.overall_progress}%
                                                coverage
                                            </div>
                                        </div>
                                        <Badge
                                            variant="outline"
                                            className={statusTone(
                                                beneficiary.status,
                                            )}
                                        >
                                            {beneficiary.status}
                                        </Badge>
                                    </div>
                                ))
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle>Assessment Matrix</CardTitle>
                                <CardDescription>
                                    Record whether each beneficiary passed or
                                    failed each project milestone.
                                </CardDescription>
                            </div>
                            <Button
                                type="button"
                                onClick={openBulkModal}
                                disabled={
                                    !canAssess ||
                                    !selectedMilestone ||
                                    assessmentRows.length === 0
                                }
                            >
                                <Save className="mr-2 h-4 w-4" />
                                Bulk Score Selected Milestone
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 lg:grid-cols-[1fr,220px,240px]">
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search beneficiary"
                            />
                            <select
                                value={statusFilter}
                                onChange={(e) =>
                                    setStatusFilter(e.target.value)
                                }
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="all">All statuses</option>
                                <option value="Not assessed">
                                    Not assessed
                                </option>
                                <option value="In progress">In progress</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                            </select>
                            <select
                                value={milestoneId ?? ''}
                                onChange={(e) =>
                                    setMilestoneId(Number(e.target.value))
                                }
                                className="rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                {milestoneOptions.map((milestone) => (
                                    <option
                                        key={milestone.id}
                                        value={milestone.id}
                                    >
                                        {milestone.title}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full border text-sm">
                                <thead className="bg-gradient-to-r from-red-600 to-orange-500 text-white">
                                    <tr>
                                        <th className="px-4 py-2 text-left">
                                            Beneficiary
                                        </th>
                                        {milestoneOptions.map((milestone) => (
                                            <th
                                                key={milestone.id}
                                                className="min-w-[170px] px-4 py-2 text-left"
                                            >
                                                <div className="font-semibold">
                                                    {milestone.title}
                                                </div>
                                                <div className="text-xs opacity-90">
                                                    Pass:{' '}
                                                    {defaultPassMark(
                                                        milestone,
                                                    ) ?? '-'}{' '}
                                                    /{' '}
                                                    {milestone.max_score ?? '-'}
                                                </div>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {filteredBeneficiaries.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={
                                                    milestoneOptions.length + 1
                                                }
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                <Filter className="mx-auto mb-2 h-4 w-4" />
                                                No beneficiaries match the
                                                current filter.
                                            </td>
                                        </tr>
                                    ) : (
                                        filteredBeneficiaries.map(
                                            (beneficiary) => (
                                                <tr
                                                    key={beneficiary.id}
                                                    className="border-t"
                                                >
                                                    <td className="min-w-[220px] px-4 py-2 align-top">
                                                        <Link
                                                            href={`/beneficiaries/${beneficiary.id}`}
                                                            className="font-medium text-red-600 hover:underline"
                                                        >
                                                            {beneficiary.name}
                                                        </Link>
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            {beneficiary.status}
                                                        </div>
                                                    </td>
                                                    {milestoneOptions.map(
                                                        (milestone) => {
                                                            const assessment =
                                                                beneficiary
                                                                    .assessments?.[
                                                                    milestone.id
                                                                ];
                                                            const passed =
                                                                assessment?.status ===
                                                                'completed';
                                                            const failed =
                                                                assessment?.status ===
                                                                'failed';

                                                            return (
                                                                <td
                                                                    key={
                                                                        milestone.id
                                                                    }
                                                                    className="px-4 py-2 align-top"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            openAssessmentModal(
                                                                                beneficiary,
                                                                                milestone,
                                                                            )
                                                                        }
                                                                        disabled={
                                                                            !canAssess
                                                                        }
                                                                        className="w-full rounded-md border px-3 py-2 text-left transition hover:border-red-400 disabled:cursor-not-allowed disabled:opacity-70"
                                                                    >
                                                                        <div className="flex items-center justify-between gap-2 text-xs">
                                                                            <span>
                                                                                Score:{' '}
                                                                                {assessment?.score ??
                                                                                    '-'}
                                                                            </span>
                                                                            {passed ? (
                                                                                <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                                                                            ) : null}
                                                                            {failed ? (
                                                                                <XCircle className="h-4 w-4 text-red-600" />
                                                                            ) : null}
                                                                        </div>
                                                                        <Badge
                                                                            variant="outline"
                                                                            className={`mt-2 ${statusTone(assessment?.status ?? '')}`}
                                                                        >
                                                                            {assessmentLabel(
                                                                                assessment,
                                                                            )}
                                                                        </Badge>
                                                                    </button>
                                                                </td>
                                                            );
                                                        },
                                                    )}
                                                </tr>
                                            ),
                                        )
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Assess Beneficiary</DialogTitle>
                        <DialogDescription>
                            {selectedBeneficiary?.name ?? '-'} •{' '}
                            {selectedMilestone?.title ?? '-'}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleSubmit} className="grid gap-3">
                        <div className="rounded-md bg-slate-50 p-3 text-sm text-muted-foreground">
                            Pass mark:{' '}
                            {defaultPassMark(selectedMilestone) ?? '-'} /{' '}
                            {selectedMilestone?.max_score ?? '-'}
                        </div>

                        <Input
                            type="number"
                            min={0}
                            max={selectedMilestone?.max_score ?? undefined}
                            value={score}
                            onChange={(e) => setScore(e.target.value)}
                            placeholder="Score"
                            required
                        />

                        <textarea
                            value={comments}
                            onChange={(e) => setComments(e.target.value)}
                            placeholder="Comments (optional)"
                            className="min-h-24 rounded-md border bg-background px-3 py-2 text-sm"
                        />

                        <Button type="submit">
                            <Save className="mr-2 h-4 w-4" />
                            Save Assessment
                        </Button>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog open={bulkOpen} onOpenChange={setBulkOpen}>
                <DialogContent className="sm:max-w-[760px]">
                    <DialogHeader>
                        <DialogTitle>Bulk Score Milestone</DialogTitle>
                        <DialogDescription>
                            {selectedMilestone?.title ?? '-'} • enter a score
                            only for beneficiaries you want to save.
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={handleBulkSubmit} className="space-y-4">
                        <div className="max-h-[420px] overflow-y-auto rounded-md border">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-3 py-2 text-left">
                                            Beneficiary
                                        </th>
                                        <th className="w-32 px-3 py-2 text-left">
                                            Score
                                        </th>
                                        <th className="px-3 py-2 text-left">
                                            Comments
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {assessmentRows.map(({ beneficiary }) => (
                                        <tr
                                            key={beneficiary.id}
                                            className="border-t"
                                        >
                                            <td className="px-3 py-2 font-medium">
                                                {beneficiary.name}
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    max={
                                                        selectedMilestone?.max_score ??
                                                        undefined
                                                    }
                                                    value={
                                                        bulkScores[
                                                            beneficiary.id
                                                        ]?.score ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        updateBulkScore(
                                                            beneficiary.id,
                                                            'score',
                                                            e.target.value,
                                                        )
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    value={
                                                        bulkScores[
                                                            beneficiary.id
                                                        ]?.comments ?? ''
                                                    }
                                                    onChange={(e) =>
                                                        updateBulkScore(
                                                            beneficiary.id,
                                                            'comments',
                                                            e.target.value,
                                                        )
                                                    }
                                                    placeholder="Optional"
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit">
                                <Save className="mr-2 h-4 w-4" />
                                Save Bulk Scores
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
