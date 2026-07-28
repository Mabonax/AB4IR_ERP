<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Models\ProgrammeOutcome;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammeOutcomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Outcomes', [
            'programs' => Program::query()->select('id', 'title')->orderBy('title')->get(),
            'outcomes' => ProgrammeOutcome::query()
                ->with('program:id,title')
                ->latest('id')
                ->get()
                ->map(fn (ProgrammeOutcome $outcome) => [
                    'id' => $outcome->id,
                    'program_id' => $outcome->program_id,
                    'program_title' => $outcome->program?->title,
                    'name' => $outcome->name,
                    'target' => $outcome->target,
                    'actual' => $outcome->actual,
                    'reporting_period' => $outcome->reporting_period,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProgrammeOutcome::query()->create($request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'target' => ['required', 'integer', 'min:0'],
            'actual' => ['required', 'integer', 'min:0'],
            'reporting_period' => ['required', 'string', 'max:100'],
        ]));

        return redirect()->back()->with('success', 'Programme outcome saved.');
    }

    public function update(Request $request, ProgrammeOutcome $outcome): RedirectResponse
    {
        $outcome->update($request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'target' => ['required', 'integer', 'min:0'],
            'actual' => ['required', 'integer', 'min:0'],
            'reporting_period' => ['required', 'string', 'max:100'],
        ]));

        return redirect()->back()->with('success', 'Programme outcome updated.');
    }
}
