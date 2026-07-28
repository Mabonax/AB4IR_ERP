<?php

namespace App\Domains\ServiceDelivery\Services;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Models\ProgrammeOutcome;
use App\Domains\Programs\Models\ProgrammePartnership;
use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectActivity;
use App\Domains\ServiceDelivery\Models\BeneficiaryPlacement;
use App\Domains\ServiceDelivery\Models\ServiceAttendance;
use Illuminate\Support\Collection;

class ServiceDeliveryDashboardService
{
    public function dashboard(): array
    {
        $beneficiaries = Beneficiary::query()
            ->with(['member.province', 'member.township', 'member.branch', 'program', 'project'])
            ->get();

        return [
            'programmes' => [
                'total' => Program::query()->count(),
                'active' => Program::query()->where('status', 'active')->count(),
                'completed' => Program::query()->where('status', 'completed')->count(),
            ],
            'projects' => [
                'total' => Project::query()->count(),
                'active' => Project::query()->where('status', 'active')->count(),
            ],
            'beneficiaries' => [
                'registered' => $beneficiaries->where('participation_status', 'registered')->count(),
                'active' => $beneficiaries->where('participation_status', 'active')->count(),
                'completed' => $beneficiaries->where('participation_status', 'completed')->count(),
            ],
            'placements' => [
                'internships' => BeneficiaryPlacement::query()->where('opportunity_type', 'internship')->count(),
                'learnerships' => BeneficiaryPlacement::query()->where('opportunity_type', 'learnership')->count(),
                'employment' => BeneficiaryPlacement::query()->where('opportunity_type', 'employment')->count(),
            ],
            'activities' => [
                'total' => ProjectActivity::query()->count(),
                'in_progress' => ProjectActivity::query()->where('status', 'in_progress')->count(),
                'completed' => ProjectActivity::query()->where('status', 'completed')->count(),
            ],
            'attendance' => [
                'records' => ServiceAttendance::query()->count(),
                'present' => ServiceAttendance::query()->where('attendance_status', 'present')->count(),
                'absent' => ServiceAttendance::query()->where('attendance_status', 'absent')->count(),
            ],
            'partnerships' => [
                'total' => ProgrammePartnership::query()->count(),
                'active' => ProgrammePartnership::query()->where('status', 'active')->count(),
            ],
            'outcomes' => [
                'tracked' => ProgrammeOutcome::query()->count(),
                'target_total' => (int) ProgrammeOutcome::query()->sum('target'),
                'actual_total' => (int) ProgrammeOutcome::query()->sum('actual'),
            ],
            'geography' => [
                'provinces' => $this->groupByGeo($beneficiaries, 'province'),
                'townships' => $this->groupByGeo($beneficiaries, 'township'),
                'branches' => $this->groupByGeo($beneficiaries, 'branch'),
            ],
        ];
    }

    protected function groupByGeo(Collection $beneficiaries, string $relation): array
    {
        return $beneficiaries
            ->groupBy(fn (Beneficiary $beneficiary) => $beneficiary->member?->{$relation}?->name ?? 'Unassigned')
            ->map(fn (Collection $items, string $name) => [
                'name' => $name,
                'total' => $items->count(),
                'active' => $items->where('participation_status', 'active')->count(),
                'completed' => $items->where('participation_status', 'completed')->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
