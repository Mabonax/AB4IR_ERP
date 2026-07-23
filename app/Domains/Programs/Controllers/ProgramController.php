<?php

namespace App\Domains\Programs\Controllers;

use App\Domains\Documents\Services\DocumentFolderService;
use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Requests\StoreProgramRequest;
use App\Domains\Programs\Requests\UpdateProgramRequest;
use App\Domains\Programs\Resources\ProgramResource;
use App\Domains\Programs\Services\ProgramService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function __construct(
        protected ProgramService $service,
        protected DocumentFolderService $documentFolderService,
    ) {}

    public function index()
    {
        $portfolio = $this->service->summarizePortfolio();

        return Inertia::render('Programs/Dashboard', [
            'stats' => $portfolio['stats'],
            'programs' => $portfolio['programs'],
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
        $repositoryRoot = $this->documentFolderService->findOwnedRootFolder(Program::class, $overview['program']->id);

        return Inertia::render('Programs/Show', [
            'program' => new ProgramResource($overview['program']),
            'stats' => $overview['stats'],
            'yearlyImpact' => $overview['yearly_impact'],
            'projects' => $overview['projects'],
            'documentRepository' => $repositoryRoot ? [
                'folder_id' => $repositoryRoot->id,
                'href' => route('organization.document-library.index', ['folder' => $repositoryRoot->id]),
            ] : null,
        ]);
    }

    public function update(UpdateProgramRequest $request, int $program)
    {
        $this->service->update($program, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Program updated');
    }

    public function destroy(int $program)
    {
        $this->service->delete($program);

        return redirect()->back()->with('success', 'Program deleted');
    }
}
