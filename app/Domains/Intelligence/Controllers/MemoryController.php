<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\MemoryRecord;
use App\Domains\Intelligence\Requests\StoreMemoryRecordRequest;
use App\Domains\Intelligence\Requests\UpdateMemoryRecordRequest;
use App\Domains\Intelligence\Services\MemoryReviewService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MemoryController extends Controller
{
    public function __construct(
        protected MemoryReviewService $reviewService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', MemoryRecord::class);

        return Inertia::render('Intelligence/Memory', [
            'memoryRecords' => MemoryRecord::query()
                ->orderByDesc('reviewed_at')
                ->orderByDesc('id')
                ->get()
                ->map(fn (MemoryRecord $memory) => [
                    'id' => $memory->id,
                    'subject_type' => $memory->subject_type,
                    'subject_id' => $memory->subject_id,
                    'memory_type' => $memory->memory_type,
                    'content' => $memory->content,
                    'confidence_score' => $memory->confidence_score,
                    'visibility' => $memory->visibility,
                    'expires_at' => $memory->expires_at?->toIso8601String(),
                    'reviewed_at' => $memory->reviewed_at?->toIso8601String(),
                    'approved_by' => $memory->approver?->name,
                ])
                ->values(),
        ]);
    }

    public function store(StoreMemoryRecordRequest $request)
    {
        $this->authorize('create', MemoryRecord::class);

        MemoryRecord::query()->create($request->validated());

        return redirect()->back()->with('success', 'Memory record created.');
    }

    public function update(UpdateMemoryRecordRequest $request, MemoryRecord $memory)
    {
        $this->authorize('update', $memory);

        $memory->update($request->validated());

        return redirect()->back()->with('success', 'Memory record updated.');
    }

    public function review(MemoryRecord $memory)
    {
        $this->authorize('update', $memory);

        $this->reviewService->approve($memory, request()->user());

        return redirect()->back()->with('success', 'Memory record reviewed.');
    }
}
