<?php

namespace App\Domains\HumanCapital\Services;

use App\Domains\Employment\Models\EmploymentProfile;
use App\Domains\Members\Models\Member;
use App\Domains\Qualifications\Models\Qualification;
use App\Domains\Skills\Models\MemberSkill;
use Illuminate\Support\Collection;

class HumanCapitalDashboardService
{
    public function dashboard(): array
    {
        $members = Member::query()
            ->with(['province', 'township', 'branch', 'skills', 'qualifications', 'employmentProfile'])
            ->get();

        return [
            'stats' => [
                'total_members' => $members->count(),
                'total_volunteers' => $members->where('member_type', 'Volunteer')->count(),
                'total_graduates' => $members->where('member_type', 'Graduate')->count(),
                'total_unemployed' => EmploymentProfile::query()->where('employment_status', 'Unemployed')->count(),
                'total_skills' => MemberSkill::query()->count(),
                'total_qualifications' => Qualification::query()->count(),
            ],
            'province_distribution' => $this->countByLabel($members, fn (Member $member) => $member->province?->name),
            'township_distribution' => $this->countByLabel($members, fn (Member $member) => $member->township?->name),
            'branch_distribution' => $this->countByLabel($members, fn (Member $member) => $member->branch?->name),
            'qualification_distribution' => $this->qualificationDistribution(),
            'skill_distribution' => $this->skillDistribution(),
            'employment_distribution' => $this->employmentDistribution(),
            'gender_distribution' => $this->countByLabel($members, fn (Member $member) => $member->gender),
            'youth_statistics' => [
                'youth_members' => $members->where('youth_indicator', true)->count(),
                'veterans' => $members->where('veteran_indicator', true)->count(),
                'members_with_disability' => $members->where('disability_status', true)->count(),
            ],
            'report_cards' => $this->townshipReports(),
        ];
    }

    public function reports(): array
    {
        return [
            'townships' => $this->townshipReports(),
        ];
    }

    protected function countByLabel(Collection $items, callable $resolver): array
    {
        return $items
            ->map(fn ($item) => $resolver($item) ?: 'Unassigned')
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $label) => [
                'label' => $label,
                'value' => $count,
            ])
            ->values()
            ->all();
    }

    protected function qualificationDistribution(): array
    {
        return Qualification::query()
            ->selectRaw('field_of_study as label, count(*) as value')
            ->groupBy('field_of_study')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])->all();
    }

    protected function skillDistribution(): array
    {
        return MemberSkill::query()
            ->selectRaw('skill_name as label, count(*) as value')
            ->groupBy('skill_name')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])->all();
    }

    protected function employmentDistribution(): array
    {
        return EmploymentProfile::query()
            ->selectRaw('employment_status as label, count(*) as value')
            ->groupBy('employment_status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])->all();
    }

    protected function townshipReports(): array
    {
        return Member::query()
            ->with(['township', 'qualifications', 'skills', 'employmentProfile'])
            ->get()
            ->groupBy(fn (Member $member) => $member->township?->name ?: 'Unassigned Township')
            ->map(function (Collection $members, string $townshipName) {
                $qualifications = $members
                    ->flatMap(fn (Member $member) => $member->qualifications)
                    ->groupBy('field_of_study')
                    ->map(fn (Collection $rows, string $field) => [
                        'label' => $field,
                        'value' => $rows->count(),
                    ])
                    ->sortByDesc('value')
                    ->values()
                    ->take(5)
                    ->all();

                $skills = $members
                    ->flatMap(fn (Member $member) => $member->skills)
                    ->groupBy('skill_name')
                    ->map(fn (Collection $rows, string $name) => [
                        'label' => $name,
                        'value' => $rows->count(),
                    ])
                    ->sortByDesc('value')
                    ->values()
                    ->take(5)
                    ->all();

                $employment = $members
                    ->map(fn (Member $member) => $member->employmentProfile?->employment_status ?: 'Not Captured')
                    ->countBy()
                    ->map(fn ($count, $label) => [
                        'label' => $label,
                        'value' => $count,
                    ])
                    ->sortByDesc('value')
                    ->values()
                    ->all();

                return [
                    'township_name' => $townshipName,
                    'population_registered' => $members->count(),
                    'qualifications' => $qualifications,
                    'skills' => $skills,
                    'employment' => $employment,
                ];
            })
            ->sortByDesc('population_registered')
            ->values()
            ->all();
    }
}
