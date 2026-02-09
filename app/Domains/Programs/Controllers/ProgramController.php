<?php

namespace App\Domains\Programs\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Programs\Services\ProgramService;
use App\Domains\Programs\Requests\StoreProgramRequest;
use App\Domains\Programs\Requests\UpdateProgramRequest;
use App\Domains\Programs\Resources\ProgramResource;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function __construct(
        protected ProgramService $service
    ) {}

    public function index()
    {
        return Inertia::render('Programs/Index', [
            'programs' => ProgramResource::collection(
                $this->service->paginatePrograms()
            ),
        ]);
    }

    public function store(StoreProgramRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->back()->with('success', 'Program created');
    }

    public function show(int $program)
    {
        $model = $this->service->getById($program);

        return response()->json(new ProgramResource($model));
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
}