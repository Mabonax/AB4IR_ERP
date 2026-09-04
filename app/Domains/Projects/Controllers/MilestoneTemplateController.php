<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\ProgramMilestoneTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MilestoneTemplateController extends Controller
{
    public function index()
    {
        $programs = Program::withCount('milestoneTemplates')
            ->orderBy('title')
            ->get()
            ->map(fn ($program) => [
                'id' => $program->id,
                'title' => $program->title,
                'milestone_count' => $program->milestone_templates_count ?? 0,
            ]);

        return Inertia::render('MilestoneTemplates/Index', [
            'programs' => $programs,
        ]);
    }

    public function program(int $program)
    {
        $programModel = Program::findOrFail($program);

        $templates = ProgramMilestoneTemplate::with('program')
            ->withCount('projectMilestones')
            ->where('program_id', $programModel->id)
            ->orderBy('sort_order')
            ->paginate(15)
            ->through(function ($template) {
                return [
                    'id' => $template->id,
                    'program_id' => $template->program_id,
                    'program_title' => $template->program?->title,
                    'title' => $template->title,
                    'description' => $template->description,
                    'sort_order' => $template->sort_order,
                    'max_score' => $template->max_score,
                    'is_required' => (bool) $template->is_required,
                    'is_active' => (bool) $template->is_active,
                    'pass_mark' => $template->pass_mark,
                    'expected_timing' => $template->expected_timing,
                    'projects_using_count' => (int) ($template->project_milestones_count ?? 0),
                ];
            });

        return Inertia::render('MilestoneTemplates/Program', [
            'program' => [
                'id' => $programModel->id,
                'title' => $programModel->title,
            ],
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'pass_mark' => 'nullable|integer|min:0',
            'expected_timing' => 'nullable|string|max:255',
        ]);
        $this->ensurePassMarkWithinMaximum($data);

        ProgramMilestoneTemplate::create($data);

        return redirect()->back()->with('success', 'Milestone template created');
    }

    public function update(Request $request, int $milestone_template)
    {
        $template = ProgramMilestoneTemplate::findOrFail($milestone_template);

        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'pass_mark' => 'nullable|integer|min:0',
            'expected_timing' => 'nullable|string|max:255',
        ]);
        $this->ensurePassMarkWithinMaximum($data);

        $template->update($data);

        return redirect()->back()->with('success', 'Milestone template updated');
    }

    public function destroy(int $milestone_template)
    {
        $template = ProgramMilestoneTemplate::findOrFail($milestone_template);
        $template->delete();

        return redirect()->back()->with('success', 'Milestone template deleted');
    }

    protected function ensurePassMarkWithinMaximum(array $data): void
    {
        if (($data['pass_mark'] ?? null) === null) {
            return;
        }

        $maxScore = (int) ($data['max_score'] ?? 100);

        if ((int) $data['pass_mark'] <= $maxScore) {
            return;
        }

        throw ValidationException::withMessages([
            'pass_mark' => "The pass mark may not be greater than the maximum score of {$maxScore}.",
        ]);
    }
}
