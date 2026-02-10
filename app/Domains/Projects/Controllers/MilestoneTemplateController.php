<?php

namespace App\Domains\Projects\Controllers;

use App\Domains\Projects\Models\MilestoneTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MilestoneTemplateController extends Controller
{
    public function index()
    {
        $templates = MilestoneTemplate::orderBy('sort_order')->paginate(15);

        return Inertia::render('MilestoneTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
        ]);

        MilestoneTemplate::create($data);

        return redirect()->back()->with('success', 'Milestone template created');
    }

    public function update(Request $request, int $milestone_template)
    {
        $template = MilestoneTemplate::findOrFail($milestone_template);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'max_score' => 'nullable|integer|min:0',
        ]);

        $template->update($data);

        return redirect()->back()->with('success', 'Milestone template updated');
    }

    public function destroy(int $milestone_template)
    {
        $template = MilestoneTemplate::findOrFail($milestone_template);
        $template->delete();

        return redirect()->back()->with('success', 'Milestone template deleted');
    }
}
