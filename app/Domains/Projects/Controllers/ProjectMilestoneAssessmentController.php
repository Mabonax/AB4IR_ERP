<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Projects\Models\ProjectEnrollment;
use App\Domains\Projects\Models\ProjectMilestone;
use App\Domains\Projects\Models\ProjectMilestoneAssessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProjectMilestoneAssessmentController extends Controller
{
    public function store(Request $request, int $project_location)
    {
        $data = $request->validate([
            'project_milestone_id' => 'required|exists:project_milestones,id',
            'beneficiary_id' => 'required|exists:beneficiaries,id',
            'score' => 'required|integer|min:0',
            'comments' => 'nullable|string|max:2000',
        ]);

        $milestone = ProjectMilestone::with('project')->findOrFail($data['project_milestone_id']);

        // Ensure beneficiary is enrolled at this location and project
        $enrolled = ProjectEnrollment::where('project_id', $milestone->project_id)
            ->where('project_location_id', $project_location)
            ->where('beneficiary_id', $data['beneficiary_id'])
            ->exists();

        if (! $enrolled) {
            return redirect()->back()->withErrors([
                'beneficiary_id' => 'Beneficiary is not enrolled at this location.',
            ]);
        }

        $maxScore = $milestone->max_score ?? 100;
        $passMark = (int) ceil($maxScore * 0.5);
        $status = $data['score'] >= $passMark ? 'completed' : 'failed';

        ProjectMilestoneAssessment::updateOrCreate(
            [
                'project_milestone_id' => $milestone->id,
                'beneficiary_id' => $data['beneficiary_id'],
                'project_location_id' => $project_location,
            ],
            [
                'status' => $status,
                'score' => $data['score'],
                'comments' => $data['comments'] ?? null,
                'assessed_at' => now(),
            ]
        );

        return redirect()->back()->with('success', 'Assessment saved');
    }
}
