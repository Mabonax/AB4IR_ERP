<?php

namespace App\Domains\Organization\Controllers;

use App\Domains\Organization\Enums\OrganizationDocumentType;
use App\Domains\Organization\Enums\OrganizationDocumentSlot;
use App\Domains\Organization\Models\OrganizationDocument;
use App\Domains\Organization\Resources\OrganizationDocumentResource;
use App\Domains\Organization\Services\OrganizationDocumentVaultService;
use App\Domains\Staff\Models\StaffDepartment;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OrganizationDocumentController extends Controller
{
    public function __construct(
        protected OrganizationDocumentVaultService $service,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OrganizationDocument::class);

        return Inertia::render('Organization/Documents/Index', [
            'documents' => OrganizationDocumentResource::collection($this->service->listForUser($request->user())),
            'departments' => StaffDepartment::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'documentTypes' => OrganizationDocumentType::options(),
            'slotOptions' => OrganizationDocumentSlot::options(),
            'can' => [
                'manage' => $request->user()?->can('create', OrganizationDocument::class) ?? false,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', OrganizationDocument::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_type' => ['required', Rule::in(OrganizationDocumentType::values())],
            'description' => ['nullable', 'string', 'max:4000'],
            'audience_scope' => ['required', 'in:all_staff,department,selected_users'],
            'department_id' => ['nullable', 'integer', 'exists:staff_departments,id'],
            'slot_key' => ['nullable', Rule::in(OrganizationDocumentSlot::values())],
            'replace_existing' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'selected_user_ids' => ['nullable', 'array'],
            'selected_user_ids.*' => ['integer', 'exists:users,id'],
            'file' => ['required', 'file', 'max:51200'],
        ]);

        $this->service->storeUpload($data, $request->user());

        return redirect()->route('organization.documents.index')
            ->with('success', 'Organization document uploaded.');
    }

    public function download(Request $request, OrganizationDocument $document): HttpResponse
    {
        $this->authorize('view', $document);

        return $this->service->download($document);
    }

    public function preview(Request $request, OrganizationDocument $document): HttpResponse
    {
        $this->authorize('view', $document);

        return $this->service->preview($document);
    }

    public function updateLifecycle(Request $request, OrganizationDocument $document)
    {
        $this->authorize('update', $document);

        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'retire_now'])],
        ]);

        $this->service->updateLifecycle($document, $data['action']);

        return redirect()->route('organization.documents.index')
            ->with('success', 'Organization document lifecycle updated.');
    }

    public function destroy(Request $request, OrganizationDocument $document)
    {
        $this->authorize('delete', $document);

        $this->service->deleteDocument($document);

        return redirect()->route('organization.documents.index')
            ->with('success', 'Organization document deleted.');
    }
}
