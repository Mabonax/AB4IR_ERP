<?php

namespace App\Domains\Programs\Controllers;

use App\Domains\Committees\Models\Committee;
use App\Domains\Programs\Requests\StoreProgramRequest;
use App\Domains\Programs\Requests\UpdateProgramRequest;
use App\Domains\Programs\Resources\ProgramResource;
use App\Domains\Programs\Services\ProgramService;
use App\Domains\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function __construct(
        protected ProgramService $service
    ) {}

    public function index()
    {
        $portfolio = $this->service->summarizePortfolio();

        return Inertia::render('Programs/Dashboard', [
            'stats' => $portfolio['stats'],
            'programs' => $portfolio['programs'],
            ...$this->formOptions(),
        ]);
    }

    public function list(): RedirectResponse
    {
        return redirect()->route('programs.index');
    }

    public function store(StoreProgramRequest $request)
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->back()->with('success', 'Program created');
    }

    public function show(int $program)
    {
        $overview = $this->service->getOverview($program);

        return Inertia::render('Programs/Show', [
            'program' => new ProgramResource($overview['program']),
            'stats' => $overview['stats'],
            'yearlyImpact' => $overview['yearly_impact'],
            'projects' => $overview['projects'],
        ]);
    }

    public function update(UpdateProgramRequest $request, int $program)
    {
        $this->service->update($program, $request->validated());

        return redirect()->back()->with('success', 'Program updated');
    }

    public function destroy(int $program)
    {
        $this->service->delete($program);

        return redirect()->back()->with('success', 'Program deleted');
    }

    protected function formOptions(): array
    {
        return [
            'committees' => Committee::query()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'staffMembers' => StaffMember::query()
                ->select('id', 'first_name', 'last_name')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->map(fn (StaffMember $staffMember) => [
                    'id' => $staffMember->id,
                    'name' => trim($staffMember->first_name.' '.$staffMember->last_name),
                ]),
        ];
    }
}
