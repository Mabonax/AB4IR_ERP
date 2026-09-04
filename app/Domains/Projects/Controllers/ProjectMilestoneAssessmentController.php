<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Projects\Models\ProjectLocation;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use App\Domains\Projects\Services\ProjectMilestoneAssessmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectMilestoneAssessmentController extends Controller
{
    public function __construct(
        protected ProjectMilestoneAssessmentService $service
    ) {}

    public function store(Request $request, int $project_location)
    {
        $data = $request->validate([
            'project_milestone_id' => 'required|exists:project_milestones,id',
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'score' => 'required|integer|min:0',
            'comments' => 'nullable|string|max:2000',
        ]);

        $milestone = ProjectMilestone::with('project')->findOrFail($data['project_milestone_id']);
        $location = ProjectLocation::query()->with(['project', 'facilitator'])->findOrFail($project_location);

        $this->authorize('store', [ProjectMilestoneAssessment::class, $location]);

        $facilitator = $location->facilitator;
        $this->service->storeAssessment($location, $milestone, $data, $facilitator);

        return redirect()->back()->with('success', 'Assessment saved');
    }

    public function bulkStore(Request $request, int $project_location)
    {
        $data = $request->validate([
            'project_milestone_id' => 'required|exists:project_milestones,id',
            'assessments' => 'required|array|min:1',
            'assessments.*.beneficiary_id' => 'required|exists:beneficiaries,id',
            'assessments.*.score' => 'required|integer|min:0',
            'assessments.*.comments' => 'nullable|string|max:2000',
        ]);

        $milestone = ProjectMilestone::with('project')->findOrFail($data['project_milestone_id']);
        $location = ProjectLocation::query()->with(['project', 'facilitator'])->findOrFail($project_location);

        $this->authorize('store', [ProjectMilestoneAssessment::class, $location]);

        $saved = $this->service->storeBulkAssessments(
            $location,
            $milestone,
            $data['assessments'],
            $location->facilitator
        );

        return redirect()->back()->with('success', 'Performance recorded for '.count($saved).' beneficiary record(s).');
    }
}
