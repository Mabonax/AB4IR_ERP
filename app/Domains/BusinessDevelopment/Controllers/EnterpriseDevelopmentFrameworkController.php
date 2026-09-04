<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentCriterion;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentDimension;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EnterpriseDevelopmentFrameworkController extends Controller
{
    public function index()
    {
        $this->authorizePermission('enterprise-development.framework.view');

        return Inertia::render('BusinessDevelopment/DevelopmentFramework/Index', [
            'dimensions' => EnterpriseDevelopmentDimension::query()
                ->with('criteria')
                ->orderBy('sequence')
                ->get(),
        ]);
    }

    public function storeDimension(Request $request)
    {
        $this->authorizePermission('enterprise-development.framework.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:enterprise_development_dimensions,code'],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'integer', 'min:0'],
            'weighting' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ]);

        EnterpriseDevelopmentDimension::query()->create([
            ...$validated,
            'active' => (bool) ($validated['active'] ?? true),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Development dimension created.');
    }

    public function updateDimension(Request $request, EnterpriseDevelopmentDimension $dimension)
    {
        $this->authorizePermission('enterprise-development.framework.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('enterprise_development_dimensions', 'code')->ignore($dimension)],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'integer', 'min:0'],
            'weighting' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ]);

        $dimension->update([...$validated, 'active' => (bool) ($validated['active'] ?? false), 'updated_by' => auth()->id()]);

        return redirect()->back()->with('success', 'Development dimension updated.');
    }

    public function storeCriterion(Request $request, EnterpriseDevelopmentDimension $dimension)
    {
        $this->authorizePermission('enterprise-development.framework.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:enterprise_development_criteria,code'],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'integer', 'min:0'],
            'weighting' => ['required', 'numeric', 'min:0'],
            'required' => ['boolean'],
            'active' => ['boolean'],
            'evidence_required' => ['boolean'],
            'guidance' => ['nullable', 'string'],
            'expires' => ['boolean'],
        ]);

        $dimension->criteria()->create([
            ...$validated,
            'required' => (bool) ($validated['required'] ?? false),
            'active' => (bool) ($validated['active'] ?? true),
            'evidence_required' => (bool) ($validated['evidence_required'] ?? false),
            'expires' => (bool) ($validated['expires'] ?? false),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Development criterion created.');
    }

    public function updateCriterion(Request $request, EnterpriseDevelopmentCriterion $criterion)
    {
        $this->authorizePermission('enterprise-development.framework.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('enterprise_development_criteria', 'code')->ignore($criterion)],
            'description' => ['nullable', 'string'],
            'sequence' => ['required', 'integer', 'min:0'],
            'weighting' => ['required', 'numeric', 'min:0'],
            'required' => ['boolean'],
            'active' => ['boolean'],
            'evidence_required' => ['boolean'],
            'guidance' => ['nullable', 'string'],
            'expires' => ['boolean'],
        ]);

        $criterion->update([
            ...$validated,
            'required' => (bool) ($validated['required'] ?? false),
            'active' => (bool) ($validated['active'] ?? false),
            'evidence_required' => (bool) ($validated['evidence_required'] ?? false),
            'expires' => (bool) ($validated['expires'] ?? false),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Development criterion updated.');
    }

    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission) || auth()->user()?->can('domain.business-development.manage'), 403);
    }
}
