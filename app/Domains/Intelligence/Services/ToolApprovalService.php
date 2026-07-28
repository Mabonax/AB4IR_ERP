<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\AiTool;
use App\Models\User;

class ToolApprovalService
{
    public function approved(AiTool $tool, ?User $user = null): bool
    {
        if (! $tool->requires_approval) {
            return true;
        }

        if (config('intelligence.approval.auto_approve_safe_tools') && in_array($tool->slug, config('intelligence.tool_runtime.safe_stub_tools', []), true)) {
            return true;
        }

        return $user?->can($tool->permission_key ?: 'domain.intelligence.manage') ?? false;
    }
}
