<?php

namespace App\Domains\BusinessDevelopment\Controllers;

use App\Domains\BusinessDevelopment\Models\BdsIncubatee;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentGap;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentNeed;
use App\Domains\BusinessDevelopment\Models\EnterpriseDevelopmentPlanItem;
use App\Domains\BusinessDevelopment\Models\EnterpriseDiagnostic;
use App\Domains\BusinessDevelopment\Resources\EnterpriseDevelopmentWorkspaceResource;
use App\Domains\BusinessDevelopment\Services\EnterpriseDevelopmentService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EnterpriseDevelopmentController extends Controller
{
    public function __construct(
        protected EnterpriseDevelopmentService $service
    ) {}

    public function show(BdsIncubatee $incubatee)
    {
        abort_unless(auth()->user()?->can('enterprise-development.profile.view') || auth()->user()?->can('domain.business-development.view') || auth()->user()?->can('domain.business-development.manage'), 403);

        return Inertia::render('BusinessDevelopment/Incubatees/EnterpriseDevelopment', [
            'incubatee' => $incubatee->load('province'),
            'workspace' => new EnterpriseDevelopmentWorkspaceResource($this->service->workspace($incubatee)),
            'responsibleUsers' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function storeDiagnostic(Request $request, BdsIncubatee $incubatee)
    {
        $validated = $request->validate([
            'assessment_type' => ['required', Rule::in(['baseline', 'periodic', 'exit'])],
            'assessment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'baseline_employees' => ['nullable', 'integer', 'min:0'],
            'baseline_turnover' => ['nullable', 'numeric', 'min:0'],
            'baseline_markets_accessed' => ['nullable', 'string', 'max:1000'],
            'baseline_funding_accessed' => ['nullable', 'string', 'max:1000'],
            'baseline_customers' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->createDiagnostic($incubatee, $validated, $request->user());

        return redirect()->back()->with('success', 'Enterprise diagnostic created.');
    }

    public function saveCriteria(Request $request, EnterpriseDiagnostic $diagnostic)
    {
        $validated = $request->validate([
            'criteria' => ['required', 'array'],
            'criteria.*.id' => ['required', 'integer', 'exists:enterprise_diagnostic_criteria,id'],
            'criteria.*.maturity_status' => ['required', Rule::in(array_keys(EnterpriseDevelopmentService::MATURITY_SCORES))],
            'criteria.*.assessor_observation' => ['nullable', 'string'],
            'criteria.*.evidence_document_file_id' => ['nullable', 'integer', 'exists:document_files,id'],
            'criteria.*.evidence_label' => ['nullable', 'string', 'max:255'],
            'criteria.*.verified_at' => ['nullable', 'date'],
            'criteria.*.verified_by' => ['nullable', 'integer', 'exists:users,id'],
            'criteria.*.expires_at' => ['nullable', 'date'],
        ]);

        $this->service->saveCriteria($diagnostic, $validated['criteria'], $request->user());

        return redirect()->back()->with('success', 'Diagnostic progress saved.');
    }

    public function completeDiagnostic(Request $request, EnterpriseDiagnostic $diagnostic)
    {
        $this->service->completeDiagnostic($diagnostic, $request->user());

        return redirect()->back()->with('success', 'Diagnostic completed and development gaps generated.');
    }

    public function createNeed(Request $request, EnterpriseDevelopmentGap $gap)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'reason' => ['nullable', 'string'],
        ]);

        $this->service->createNeedFromGap($gap, $validated, $request->user());

        return redirect()->back()->with('success', 'Development need created.');
    }

    public function createPlan(Request $request, BdsIncubatee $incubatee)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'baseline_diagnostic_id' => ['nullable', 'integer', 'exists:enterprise_diagnostics,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'active', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.development_need_id' => ['nullable', 'integer', 'exists:enterprise_development_needs,id'],
            'items.*.objective' => ['required', 'string', 'max:255'],
            'items.*.priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'items.*.target_date' => ['nullable', 'date'],
            'items.*.responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.status' => ['required', Rule::in(['open', 'planned', 'in_progress', 'addressed', 'cancelled'])],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $this->service->createPlan($incubatee, $validated, $request->user());

        return redirect()->back()->with('success', 'Development plan created.');
    }

    public function updateNeed(Request $request, EnterpriseDevelopmentNeed $need)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'reason' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'planned', 'in_progress', 'addressed', 'cancelled'])],
        ]);

        $this->service->updateNeed($need, $validated, $request->user());

        return redirect()->back()->with('success', 'Development need updated.');
    }

    public function updatePlanItem(Request $request, EnterpriseDevelopmentPlanItem $item)
    {
        $validated = $request->validate([
            'objective' => ['sometimes', 'required', 'string', 'max:255'],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])],
            'target_date' => ['nullable', 'date'],
            'responsible_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'planned', 'in_progress', 'addressed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $this->service->updatePlanItem($item, $validated, $request->user());

        return redirect()->back()->with('success', 'Development plan item updated.');
    }
}
