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

test('opening a notification marks it as read and redirects to the target page', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => 'App\\Domains\\Leave\\Notifications\\LeaveRequestNotification',
        'data' => [
            'title' => 'Leave request decision',
            'message' => 'Your leave request has been reviewed.',
            'url' => '/leave/requests',
        ],
    ]);

    $notificationId = $user->notifications()->firstOrFail()->id;

    $this->actingAs($user)
        ->get(route('notifications.open', $notificationId))
        ->assertRedirect('/leave/requests');

    $notification = $user->fresh()->notifications()->whereKey($notificationId)->firstOrFail();

    expect($notification->read_at)->not->toBeNull();
    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});

test('opening a notification without a target page redirects to the notifications index', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => 'App\\Domains\\Leave\\Notifications\\LeaveRequestNotification',
        'data' => [
            'title' => 'Leave request decision',
            'message' => 'Your leave request has been reviewed.',
        ],
    ]);

    $notificationId = $user->notifications()->firstOrFail()->id;

    $this->actingAs($user)
        ->get(route('notifications.open', $notificationId))
        ->assertRedirect(route('notifications.index'));

    $notification = $user->fresh()->notifications()->whereKey($notificationId)->firstOrFail();

    expect($notification->read_at)->not->toBeNull();
});

test('organization document notifications open the vault page', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => (string) str()->uuid(),
        'type' => 'App\\Domains\\Organization\\Notifications\\OrganizationDocumentPublishedNotification',
        'data' => [
            'title' => 'New organization document available',
            'message' => 'A new email signature is ready for download.',
            'url' => '/organization/documents',
        ],
    ]);

    $notificationId = $user->notifications()->firstOrFail()->id;

    $this->actingAs($user)
        ->get(route('notifications.open', $notificationId))
        ->assertRedirect('/organization/documents');
});
