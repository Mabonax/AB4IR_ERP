<?php

namespace App\Domains\Members\Controllers;

use App\Domains\Geography\Services\GeographyRegistryService;
use App\Domains\Members\Models\Member;
use App\Domains\Members\Requests\StoreMemberRequest;
use App\Domains\Members\Requests\UpdateMemberRequest;
use App\Domains\Members\Resources\MemberResource;
use App\Domains\Members\Services\MemberService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function __construct(
        protected MemberService $service,
        protected GeographyRegistryService $geographyService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Member::class);

        $members = $this->service->paginate($request->only(['search', 'member_type', 'status', 'township_id']));

        return Inertia::render('Members/Index', [
            'filters' => $request->only(['search', 'member_type', 'status', 'township_id']),
            'members' => [
                'data' => MemberResource::collection($members->getCollection())->resolve(),
                'meta' => [
                    'current_page' => $members->currentPage(),
                    'last_page' => $members->lastPage(),
                    'total' => $members->total(),
                ],
            ],
            'options' => $this->formOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Member::class);

        return Inertia::render('Members/Create', [
            'options' => $this->formOptions(),
            'assignmentOptions' => $this->service->assignmentOptions(),
        ]);
    }

    public function store(StoreMemberRequest $request)
    {
        $member = $this->service->create($request->validated());

        return redirect()->route('members.edit', $member->id)
            ->with('success', 'Member registered.');
    }

    public function edit(int $member): Response
    {
        $model = $this->service->findOrFail($member);
        $this->authorize('update', $model);

        return Inertia::render('Members/Edit', [
            'member' => MemberResource::make($model)->resolve(),
            'options' => $this->formOptions(),
            'assignmentOptions' => $this->service->assignmentOptions(),
        ]);
    }

    public function update(UpdateMemberRequest $request, int $member)
    {
        $model = $this->service->findOrFail($member);
        $this->authorize('update', $model);

        $this->service->update($member, $request->validated());

        return redirect()->back()->with('success', 'Member profile updated.');
    }

    protected function formOptions(): array
    {
        return array_merge(
            $this->geographyService->referenceData(),
            [
                'memberTypes' => [
                    'Community Member',
                    'Volunteer',
                    'Activist',
                    'Beneficiary',
                    'Student',
                    'Graduate',
                    'Professional',
                    'Entrepreneur',
                ],
                'memberStatuses' => ['active', 'inactive', 'suspended', 'deceased'],
                'genders' => ['Male', 'Female', 'Other', 'Prefer not to say'],
                'qualificationTypes' => [
                    'NATED',
                    'NCV',
                    'Certificate',
                    'Diploma',
                    'Advanced Diploma',
                    'Degree',
                    'Honours',
                    'Masters',
                    'Doctorate',
                    'Trade Test',
                    'Professional Certification',
                    'Other',
                ],
                'skillLevels' => ['Beginner', 'Intermediate', 'Advanced', 'Expert'],
                'employmentStatuses' => [
                    'Employed',
                    'Unemployed',
                    'Self-Employed',
                    'Entrepreneur',
                    'Student',
                    'Internship',
                    'Learnership',
                    'Contract Worker',
                ],
                'interestTypes' => [
                    'Learnership Interest',
                    'Internship Interest',
                    'Employment Interest',
                    'Entrepreneurship Interest',
                ],
                'incomeBands' => [
                    'No income',
                    'Under R2,000',
                    'R2,000 - R5,000',
                    'R5,001 - R10,000',
                    'R10,001 - R20,000',
                    'Over R20,000',
                ],
            ]
        );
    }
}
