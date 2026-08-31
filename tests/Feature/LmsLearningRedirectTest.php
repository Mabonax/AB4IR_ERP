<?php

use App\Models\User;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated ERP users are redirected to the LMS dashboard from learning link', function () {
    config(['services.lms.app_url' => 'http://127.0.0.1:8016']);

    $this->actingAs(User::factory()->create())
        ->get('/learning')
        ->assertRedirect('http://127.0.0.1:8016/dashboard');
});

test('Inertia ERP navigation receives a full browser location response for the LMS dashboard', function () {
    config(['services.lms.app_url' => 'http://127.0.0.1:8016']);
    $version = app(HandleInertiaRequests::class)->version(request());

    $this->actingAs(User::factory()->create())
        ->withHeader('X-Inertia', 'true')
        ->withHeader('X-Inertia-Version', $version)
        ->get('/learning')
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'http://127.0.0.1:8016/dashboard');
});
