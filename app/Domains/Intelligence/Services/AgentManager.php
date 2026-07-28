<?php

namespace App\Domains\Intelligence\Services;

use App\Domains\Intelligence\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class AgentManager
{
    public function all(): Collection
    {
        return Agent::query()->with('owner')->orderBy('name')->get();
    }

    public function create(array $data, User $owner): Agent
    {
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $data['owner_user_id'] = $owner->id;

        return Agent::query()->create($data);
    }

    public function update(Agent $agent, array $data): Agent
    {
        if (blank($data['slug'] ?? null) && filled($data['name'] ?? null)) {
            $data['slug'] = Str::slug((string) $data['name']);
        }

        $agent->update($data);

        return $agent->refresh();
    }
}
