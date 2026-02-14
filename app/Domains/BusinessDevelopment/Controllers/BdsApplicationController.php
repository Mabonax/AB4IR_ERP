<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Requests\AssessBdsApplicationRequest;
use App\Domains\BusinessDevelopment\Requests\ImportBdsApplicationRequest;
use App\Domains\BusinessDevelopment\Requests\ScheduleBdsPitchRequest;
use App\Domains\BusinessDevelopment\Resources\BdsApplicationResource;
use App\Domains\BusinessDevelopment\Services\BdsApplicationService;
use App\Http\Controllers\Controller;
use App\Models\Provinces;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BdsApplicationController extends Controller
{
    public function __construct(
        protected BdsApplicationService $service
    ) {}

    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 15);

        return Inertia::render('BusinessDevelopment/Applications/Index', [
            'applications' => BdsApplicationResource::collection(
                $this->service->paginate($perPage)
            ),
            'provinces' => Provinces::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function show(int $bds_application)
    {
        return Inertia::render('BusinessDevelopment/Applications/Show', [
            'application' => new BdsApplicationResource(
                $this->service->getById($bds_application)
            ),
        ]);
    }

    public function import(ImportBdsApplicationRequest $request)
    {
        $summary = $this->service->importFromFile($request->file('file'));

        return redirect()->back()->with('success', sprintf(
            'Import completed. Processed: %d, Created: %d, Duplicates: %d, Errors: %d.',
            $summary['processed'],
            $summary['created'],
            $summary['duplicates'],
            count($summary['errors'])
        ))->with('import_errors', $summary['errors']);
    }

    public function assess(AssessBdsApplicationRequest $request, int $bds_application)
    {
        $this->service->assess($bds_application, $request->validated());

        return redirect()->back()->with('success', 'Assessment saved.');
    }

    public function schedulePitch(ScheduleBdsPitchRequest $request, int $bds_application)
    {
        $this->service->schedulePitch($bds_application, $request->validated());

        return redirect()->back()->with('success', 'Pitch scheduled.');
    }
}
