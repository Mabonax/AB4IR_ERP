<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Projects\Models\Project;
use App\Domains\Projects\Models\ProjectActivity;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectActivityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Activities', [
            'projects' => Project::query()->select('id', 'name')->orderBy('name')->get(),
            'activities' => ProjectActivity::query()
                ->with('project:id,name')
                ->latest('planned_date')
                ->latest('id')
                ->get()
                ->map(fn (ProjectActivity $activity) => [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'project_id' => $activity->project_id,
                    'project_name' => $activity->project?->name,
                    'description' => $activity->description,
                    'planned_date' => $activity->planned_date?->format('Y-m-d'),
                    'actual_date' => $activity->actual_date?->format('Y-m-d'),
                    'status' => $activity->status,
                    'assigned_team' => $activity->assigned_team,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProjectActivity::query()->create($request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'planned_date' => ['nullable', 'date'],
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', 'in:planned,in_progress,completed,cancelled'],
            'assigned_team' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()->back()->with('success', 'Project activity saved.');
    }

    public function update(Request $request, ProjectActivity $activity): RedirectResponse
    {
        $activity->update($request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'planned_date' => ['nullable', 'date'],
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', 'in:planned,in_progress,completed,cancelled'],
            'assigned_team' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()->back()->with('success', 'Project activity updated.');
    }
}
