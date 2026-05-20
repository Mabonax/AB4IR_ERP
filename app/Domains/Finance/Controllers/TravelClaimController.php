<?php

namespace App\Domains\Finance\Controllers;

use App\Domains\Finance\Models\TravelClaim;
use App\Domains\Finance\Services\TravelClaimService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TravelClaimController extends Controller
{
    public function __construct(
        protected TravelClaimService $travelClaimService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', TravelClaim::class);

        $claims = $this->travelClaimService->visibleClaims($request->user());

        return Inertia::render('Finance/TravelClaims/Index', [
            'claims' => $claims->map(fn (TravelClaim $claim) => $this->travelClaimService->mapClaim($claim, $request->user()))->values(),
            'isFinanceUser' => $request->user()->can('domain.finance.view') || $request->user()->can('domain.finance.manage'),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', TravelClaim::class);

        return Inertia::render('Finance/TravelClaims/Create', [
            'claimants' => $this->travelClaimService->claimantOptions($request->user()),
            'defaultTariffPerKm' => TravelClaimService::DEFAULT_TARIFF_PER_KM,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', TravelClaim::class);

        $data = $request->validate([
            'claimant_staff_member_id' => 'required|integer|exists:staff_members,id',
            'claim_month' => 'required|date',
            'claimant_address' => 'nullable|string|max:1000',
            'vehicle_make_model' => 'nullable|string|max:255',
            'vehicle_type' => 'nullable|string|max:255',
            'vehicle_year' => 'nullable|integer|min:1900|max:2100',
            'engine_volume' => 'nullable|string|max:255',
            'tariff_per_km' => 'required|numeric|min:0',
            'home_distance_km' => 'nullable|numeric|min:0',
            'trips' => 'required|array|min:1',
            'trips.*.travel_date' => 'required|date',
            'trips.*.route_from' => 'required|string|max:255',
            'trips.*.route_to' => 'required|string|max:255',
            'trips.*.start_time' => 'nullable',
            'trips.*.end_time' => 'nullable',
            'trips.*.nature_of_duty' => 'nullable|string|max:2000',
            'trips.*.actual_distance_km' => 'required|numeric|min:0',
            'trips.*.claimable_distance_km' => 'required|numeric|min:0',
        ]);

        $claim = $this->travelClaimService->create($request->user(), $data);

        return redirect()->route('finance.travel-claims.show', $claim->id)
            ->with('success', 'Travel claim submitted for executive approval');
    }

    public function show(Request $request, TravelClaim $travelClaim)
    {
        $travelClaim->load(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
        $this->authorize('view', $travelClaim);

        return Inertia::render('Finance/TravelClaims/Show', [
            'claim' => $this->travelClaimService->mapClaim($travelClaim, $request->user()),
        ]);
    }

    public function approve(Request $request, TravelClaim $travelClaim)
    {
        $this->authorize('approve', $travelClaim);

        $data = $request->validate([
            'approval_comment' => 'nullable|string|max:2000',
        ]);

        $this->travelClaimService->approve($travelClaim, $request->user(), $data['approval_comment'] ?? null);

        return redirect()->back()->with('success', 'Travel claim approved for finance processing');
    }

    public function rejectApproval(Request $request, TravelClaim $travelClaim)
    {
        $this->authorize('approve', $travelClaim);

        $data = $request->validate([
            'approval_comment' => 'nullable|string|max:2000',
        ]);

        $this->travelClaimService->rejectApproval($travelClaim, $request->user(), $data['approval_comment'] ?? null);

        return redirect()->back()->with('success', 'Travel claim rejected during approval');
    }

    public function receive(Request $request, TravelClaim $travelClaim)
    {
        $this->authorize('receive', $travelClaim);

        $data = $request->validate([
            'finance_comment' => 'nullable|string|max:2000',
        ]);

        $this->travelClaimService->receive($travelClaim, $request->user(), $data['finance_comment'] ?? null);

        return redirect()->back()->with('success', 'Travel claim received by finance');
    }

    public function pay(Request $request, TravelClaim $travelClaim)
    {
        $this->authorize('pay', $travelClaim);

        $data = $request->validate([
            'finance_comment' => 'nullable|string|max:2000',
        ]);

        $this->travelClaimService->pay($travelClaim, $request->user(), $data['finance_comment'] ?? null);

        return redirect()->back()->with('success', 'Travel claim marked as paid');
    }

    public function reject(Request $request, TravelClaim $travelClaim)
    {
        $this->authorize('reject', $travelClaim);

        $data = $request->validate([
            'finance_comment' => 'nullable|string|max:2000',
        ]);

        $this->travelClaimService->reject($travelClaim, $request->user(), $data['finance_comment'] ?? null);

        return redirect()->back()->with('success', 'Travel claim rejected');
    }

    public function pdf(Request $request, TravelClaim $travelClaim)
    {
        $travelClaim->load(['claimant.department', 'claimant.manager.user', 'checkedBy', 'submittedBy', 'receivedBy', 'approvedBy', 'paidBy', 'trips']);
        $this->authorize('view', $travelClaim);

        $pdf = Pdf::loadView('pdf.travel-claim', [
            'claim' => $travelClaim,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("travel-claim-{$travelClaim->claim_number}.pdf");
    }
}
