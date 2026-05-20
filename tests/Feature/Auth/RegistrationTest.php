<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('public registration screen is unavailable', function () {
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn ($feature) => ! str_contains((string) $feature, 'registration')
    )));

    $this->get('/register')->assertNotFound();
});

test('public registration submission is blocked', function () {
    config()->set('fortify.features', array_values(array_filter(
        config('fortify.features'),
        fn ($feature) => ! str_contains((string) $feature, 'registration')
    )));

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $response->assertNotFound();
});
