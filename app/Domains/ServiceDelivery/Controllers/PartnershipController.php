<?php

namespace App\Domains\ServiceDelivery\Controllers;

use App\Domains\Programs\Models\Program;
use App\Domains\Programs\Models\ProgrammePartnership;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnershipController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ServiceDelivery/Partnerships', [
            'programs' => Program::query()->select('id', 'title')->orderBy('title')->get(),
            'partnerships' => ProgrammePartnership::query()
                ->with('programs:id,title')
                ->latest('id')
                ->get()
                ->map(fn (ProgrammePartnership $partnership) => [
                    'id' => $partnership->id,
                    'organisation' => $partnership->organisation,
                    'contact_person' => $partnership->contact_person,
                    'contact_email' => $partnership->contact_email,
                    'contact_phone' => $partnership->contact_phone,
                    'partnership_type' => $partnership->partnership_type,
                    'status' => $partnership->status,
                    'program_ids' => $partnership->programs->pluck('id')->map(fn ($id) => (string) $id)->all(),
                    'program_names' => $partnership->programs->pluck('title')->all(),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organisation' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'partnership_type' => ['required', 'in:government,private_sector,ngo,academic_institution,donor'],
            'status' => ['required', 'string', 'max:100'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $partnership = ProgrammePartnership::query()->create(collect($data)->except('program_ids')->all());
        $partnership->programs()->sync($data['program_ids'] ?? []);

        return redirect()->back()->with('success', 'Partnership saved.');
    }

    public function update(Request $request, ProgrammePartnership $partnership): RedirectResponse
    {
        $data = $request->validate([
            'organisation' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'partnership_type' => ['required', 'in:government,private_sector,ngo,academic_institution,donor'],
            'status' => ['required', 'string', 'max:100'],
            'program_ids' => ['nullable', 'array'],
            'program_ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $partnership->update(collect($data)->except('program_ids')->all());
        $partnership->programs()->sync($data['program_ids'] ?? []);

        return redirect()->back()->with('success', 'Partnership updated.');
    }
}
