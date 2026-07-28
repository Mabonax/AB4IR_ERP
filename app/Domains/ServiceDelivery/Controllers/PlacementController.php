<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Beneficiaries\Models\Beneficiary;
use App\Domains\ServiceDelivery\Models\BeneficiaryPlacement;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlacementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Placements', [
            'beneficiaries' => Beneficiary::query()
                ->select('id', 'name', 'surname', 'beneficiary_number')
                ->orderBy('name')
                ->get()
                ->map(fn (Beneficiary $beneficiary) => [
                    'id' => $beneficiary->id,
                    'name' => trim($beneficiary->name.' '.$beneficiary->surname),
                    'beneficiary_number' => $beneficiary->beneficiary_number,
                ]),
            'placements' => BeneficiaryPlacement::query()
                ->with('beneficiary:id,name,surname,beneficiary_number')
                ->latest('placement_date')
                ->latest('id')
                ->get()
                ->map(fn (BeneficiaryPlacement $placement) => [
                    'id' => $placement->id,
                    'beneficiary_id' => $placement->beneficiary_id,
                    'beneficiary_name' => $placement->beneficiary
                        ? trim($placement->beneficiary->name.' '.$placement->beneficiary->surname)
                        : null,
                    'beneficiary_number' => $placement->beneficiary?->beneficiary_number,
                    'employer' => $placement->employer,
                    'opportunity_type' => $placement->opportunity_type,
                    'placement_date' => $placement->placement_date?->format('Y-m-d'),
                    'completion_date' => $placement->completion_date?->format('Y-m-d'),
                    'status' => $placement->status,
                    'notes' => $placement->notes,
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BeneficiaryPlacement::query()->create($request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'employer' => ['required', 'string', 'max:255'],
            'opportunity_type' => ['required', 'in:internship,learnership,apprenticeship,employment,volunteer_placement'],
            'placement_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date', 'after_or_equal:placement_date'],
            'status' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]));

        return redirect()->back()->with('success', 'Placement saved.');
    }

    public function update(Request $request, BeneficiaryPlacement $placement): RedirectResponse
    {
        $placement->update($request->validate([
            'beneficiary_id' => ['required', 'integer', 'exists:beneficiaries,id'],
            'employer' => ['required', 'string', 'max:255'],
            'opportunity_type' => ['required', 'in:internship,learnership,apprenticeship,employment,volunteer_placement'],
            'placement_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date', 'after_or_equal:placement_date'],
            'status' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]));

        return redirect()->back()->with('success', 'Placement updated.');
    }
}
