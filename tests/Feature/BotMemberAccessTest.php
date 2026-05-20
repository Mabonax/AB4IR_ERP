<?php

use App\Domains\Programs\Models\Program;
use App\Domains\Projects\Models\Project;
use App\Domains\Staff\Models\StaffDepartment;
use App\Domains\Staff\Models\StaffMember;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\BotMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeBotMemberProject(): Project
{
    $managerUser = User::factory()->create();

    $department = StaffDepartment::query()->create([
        'name' => 'Projects '.Str::upper(Str::random(4)),
        'description' => 'Projects department',
    ]);

    $manager = StaffMember::query()->create([
        'user_id' => $managerUser->id,
        'department_id' => $department->id,
        'first_name' => 'Project',
        'last_name' => 'Manager',
        'email' => 'project.manager@example.test',
        'employee_number' => 'PM-'.Str::upper(Str::random(6)),
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $program = Program::query()->create([
        'title' => 'Project Visibility '.Str::upper(Str::random(4)),
        'description' => 'Portfolio program',
        'slug' => 'project-visibility-'.Str::lower(Str::random(6)),
    ]);

    return Project::query()->create([
        'program_id' => $program->id,
        'project_manager_id' => $manager->id,
        'name' => 'Read Only Project',
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'description' => 'Project visible to the bot member.',
    ]);
}

test('seeded bot member can view project dashboards without project operation access', function () {
    $this->seed([
        AccessControlSeeder::class,
        BotMemberSeeder::class,
    ]);

    $project = makeBotMemberProject();

    $botMember = User::query()->where('email', config('app.bot_member_email'))->firstOrFail();

    expect($botMember->name)->toBe((string) config('app.bot_member_name'));
    expect($botMember->hasRole('bot-member'))->toBeTrue();
    expect($botMember->can('domain.projects.view'))->toBeTrue();
    expect($botMember->can('domain.projects.manage'))->toBeFalse();

    $this->actingAs($botMember)
        ->get(route('projects.dashboard'))
        ->assertOk();

    $this->actingAs($botMember)
        ->get(route('projects.list'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->where('canManageProjects', false)
        );

    $this->actingAs($botMember)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Show')
            ->where('canManageProjects', false)
            ->where('canManageGovernance', false)
        );

    $this->actingAs($botMember)
        ->post(route('projects.conclude', $project), [
            'closure_date' => now()->toDateString(),
        ])
        ->assertForbidden();
});
