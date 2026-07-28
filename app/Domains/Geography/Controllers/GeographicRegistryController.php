<?php

namespace App\Domains\Geography\Controllers;

use App\Domains\Geography\Requests\StoreGeographyRecordRequest;
use App\Domains\Geography\Services\GeographyRegistryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeographicRegistryController extends Controller
{
    public function __construct(
        protected GeographyRegistryService $service
    ) {}

    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->can('domain.geography.view') || $request->user()?->can('domain.geography.manage'),
            403
        );

        return Inertia::render('Geography/Index', [
            'registry' => $this->service->referenceData(),
        ]);
    }

    public function store(StoreGeographyRecordRequest $request)
    {
        $this->service->createRecord($request->string('type')->toString(), $request->validated());

        return redirect()->back()->with('success', 'Geographic record added.');
    }
}
