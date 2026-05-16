<?php

namespace App\Domains\BusinessDevelopment\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BdsIncubateeResource extends JsonResource
{
    public function toArray($request): array
    {
        $kpis = $this->whenLoaded('kpis', fn () => $this->kpis, collect());
        $latestReviews = $kpis->map(fn ($kpi) => $kpi->reviews->sortByDesc('review_date')->first())->filter();
        $criticalCount = $latestReviews->filter(fn ($review) => $review->status === 'at_risk')->count();
        $warningCount = $latestReviews->filter(fn ($review) => $review->status === 'needs_attention')->count();
        $completedCount = $kpis->filter(fn ($kpi) => $kpi->status === 'completed')->count();
        $activeCount = $kpis->filter(fn ($kpi) => $kpi->status === 'active')->count();
        $overdueCount = $kpis
            ->filter(fn ($kpi) => $kpi->status === 'active' && $kpi->due_date && $kpi->due_date->isPast())
            ->count();

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'id_number' => $this->id_number,
            'gender' => $this->gender,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'company_registration_number' => $this->company_registration_number,
            'position_in_company' => $this->position_in_company,
            'majority_shareholding' => $this->majority_shareholding,
            'current_number_of_employees' => $this->current_number_of_employees,
            'physical_address' => $this->physical_address,
            'website_address' => $this->website_address,
            'years_in_operation' => $this->years_in_operation,
            'province_id' => $this->province_id,
            'province_name' => $this->province?->name,
            'has_business_plan' => $this->has_business_plan,
            'relevant_skill_set' => $this->relevant_skill_set,
            'technology_product_service' => $this->technology_product_service,
            'technology_stage_of_development' => $this->technology_stage_of_development,
            'status' => $this->status,
            'incubated_date' => $this->incubated_date?->toDateString(),
            'intake' => [
                'type' => $this->intake_type,
                'source' => $this->intake_source,
                'justification' => $this->intake_justification,
                'approved_at' => $this->intake_approved_at?->toDateTimeString(),
                'approved_by' => [
                    'id' => $this->intakeApprover?->id,
                    'name' => $this->intakeApprover?->name,
                ],
            ],
            'kpi_summary' => [
                'total' => $kpis->count(),
                'active' => $activeCount,
                'completed' => $completedCount,
                'warnings' => $warningCount,
                'critical' => $criticalCount,
                'overdue' => $overdueCount,
                'health' => match (true) {
                    $criticalCount > 0 || $overdueCount > 0 => 'critical',
                    $warningCount > 0 => 'warning',
                    $kpis->count() > 0 => 'healthy',
                    default => 'unassigned',
                },
            ],
            'kpis' => BdsIncubateeKpiResource::collection($kpis),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
