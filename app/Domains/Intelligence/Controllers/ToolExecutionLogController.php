<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\AiTool;
use App\Domains\Intelligence\Models\ToolExecutionLog;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ToolExecutionLogController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiTool::class);

        return Inertia::render('Intelligence/Logs', [
            'logs' => ToolExecutionLog::query()
                ->with(['tool', 'agent', 'user'])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(fn (ToolExecutionLog $log) => [
                    'id' => $log->id,
                    'tool' => $log->tool?->name,
                    'agent' => $log->agent?->name,
                    'user' => $log->user?->name,
                    'status' => $log->status,
                    'approved' => $log->approved,
                    'duration_ms' => $log->duration_ms,
                    'executed_at' => $log->executed_at?->toIso8601String(),
                    'error_message' => $log->error_message,
                    'input_payload' => $log->input_payload ?? [],
                    'output_payload' => $log->output_payload ?? [],
                ])
                ->values(),
        ]);
    }
}
