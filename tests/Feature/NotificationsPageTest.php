<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('authenticated user can view and mark notifications as read', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => 'App\\Domains\\TaskManagement\\Notifications\\TaskAssignedNotification',
        'data' => [
            'title' => 'Task assignment updated',
            'message' => 'A new task has been assigned to your work queue.',
            'url' => '/task-management/tasks',
        ],
    ]);

    $notificationId = $user->notifications()->firstOrFail()->id;

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
            ->where('notifications.data.0.id', $notificationId)
            ->where('notifications.data.0.read_at', null)
        );

    $this->actingAs($user)
        ->post(route('notifications.read', $notificationId))
        ->assertRedirect();

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
