<?php

namespace App\Domains\Intelligence\Controllers;

use App\Domains\Intelligence\Models\Agent;
use App\Domains\Intelligence\Requests\RunConversationRequest;
use App\Domains\Intelligence\Services\ConversationManager;
use App\Http\Controllers\Controller;

class ConversationController extends Controller
{
    public function __construct(
        protected ConversationManager $conversationManager
    ) {}

    public function store(RunConversationRequest $request)
    {
        $this->authorize('viewAny', Agent::class);

        $result = $this->conversationManager->run($request->validated(), $request->user());

        return redirect()->back()
            ->with('success', 'Agent execution completed.')
            ->with('info', $result['response']['content']);
    }
}
