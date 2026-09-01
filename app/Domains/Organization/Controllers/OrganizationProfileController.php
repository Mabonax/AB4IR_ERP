<?php

namespace App\Domains\Organization\Controllers;

use App\Domains\Organization\Services\OrganizationProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrganizationProfileController extends Controller
{
    public function __construct(
        protected OrganizationProfileService $service
    ) {}

    public function show()
    {
        $profile = $this->service->getProfile();
        $this->authorize('view', $profile);

        return Inertia::render('Organization/Show', [
            'profile' => $this->service->mapProfile($profile),
        ]);
    }

    public function edit()
    {
        $profile = $this->service->getProfile();
        $this->authorize('update', $profile);

        return Inertia::render('Organization/Edit', [
            'profile' => $this->service->mapProfile($profile),
        ]);
    }

    public function update(Request $request)
    {
        $profile = $this->service->getProfile();
        $this->authorize('update', $profile);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'mission' => 'nullable|string|max:4000',
            'vision' => 'nullable|string|max:4000',
            'objectives' => 'nullable|string|max:12000',
            'focus_areas' => 'nullable|string|max:4000',
            'about' => 'nullable|string|max:12000',
            'service_offering' => 'nullable|string|max:4000',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'impact_total' => 'nullable|integer|min:0|max:9999999999',
            'impact_digital' => 'nullable|integer|min:0|max:9999999999',
            'impact_physical' => 'nullable|integer|min:0|max:9999999999',
            'trainings_conducted' => 'nullable|integer|min:0|max:9999999999',
            'impact_website' => 'nullable|integer|min:0|max:9999999999',
            'impact_walkins' => 'nullable|integer|min:0|max:9999999999',
            'impact_facebook' => 'nullable|integer|min:0|max:9999999999',
            'impact_x' => 'nullable|integer|min:0|max:9999999999',
            'impact_linkedin' => 'nullable|integer|min:0|max:9999999999',
            'impact_livestreaming' => 'nullable|integer|min:0|max:9999999999',
            'impact_instagram' => 'nullable|integer|min:0|max:9999999999',
            'impact_youtube' => 'nullable|integer|min:0|max:9999999999',
        ]);

        $this->service->updateProfile($data, $request->user());

        return redirect()->back()->with('success', 'Organization profile updated.');
    }

    public function updateLogos(Request $request)
    {
        $profile = $this->service->getProfile();
        $this->authorize('update', $profile);

        $data = $request->validate([
            'primary_logo' => 'nullable|image|max:5120',
            'light_logo' => 'nullable|image|max:5120',
            'dark_logo' => 'nullable|image|max:5120',
            'icon_logo' => 'nullable|image|max:5120',
        ]);

        $this->service->updateLogos($data, $request->user());

        return redirect()->back()->with('success', 'Organization logos updated.');
    }

    public function showLogo(string $variant)
    {
        $profile = $this->service->getProfile();
        $this->authorize('view', $profile);

        $column = match ($variant) {
            'primary' => 'primary_logo_path',
            'light' => 'light_logo_path',
            'dark' => 'dark_logo_path',
            'icon' => 'icon_logo_path',
            default => abort(404),
        };

        $path = $profile->{$column};

        abort_if(! $path || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
