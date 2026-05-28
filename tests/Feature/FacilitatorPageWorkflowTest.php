<?php

use App\Domains\Facilitators\Models\Facilitator;
use App\Models\Provinces;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function makeFacilitatorProvince(): Provinces
{
    return Provinces::query()->create([
        'name' => 'Facilitator Province '.fake()->unique()->lexify('????'),
    ]);
}

test('authorized user can open the facilitator create page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'facilitators');
    $province = makeFacilitatorProvince();

    $this->actingAs($user)
        ->get('/facilitators/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Facilitators/Create')
            ->has('provinces', 1)
            ->where('provinces.0.id', $province->id)
        );
});

test('authorized user can open the facilitator edit page', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'facilitators');
    $province = makeFacilitatorProvince();

    $facilitator = Facilitator::query()->create([
        'name' => 'Lebo',
        'surname' => 'Trainer',
        'email' => 'facilitator-edit@example.test',
        'province_id' => $province->id,
    ]);

    $this->actingAs($user)
        ->get("/facilitators/{$facilitator->id}/edit")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Facilitators/Edit')
            ->where('facilitator.id', $facilitator->id)
            ->where('facilitator.full_name', 'Lebo Trainer')
        );
});

test('facilitator creation allows lean profile data and creates a linked user', function () {
    $user = User::factory()->create();
    grantDomainAccess($user, 'facilitators');

    $response = $this->actingAs($user)->post('/facilitators', [
        'name' => 'Ava',
        'surname' => 'Coach',
        'email' => 'facilitator-create@example.test',
        'cell' => '',
        'specialization' => '',
        'province_id' => '',
        'dob' => '',
        'id_number' => '',
        'address' => '',
    ]);

    $facilitator = Facilitator::query()->where('email', 'facilitator-create@example.test')->firstOrFail();

    $response->assertRedirect("/facilitators/{$facilitator->id}");
    expect($facilitator->fresh('user')->user)->not->toBeNull();
    expect($facilitator->cell)->toBeNull();
    expect($facilitator->specialization)->toBeNull();
    expect($facilitator->fresh('user')->user->hasRole('facilitator'))->toBeTrue();
});

test('facilitator update workflow reuses an existing matching user and lands on the facilitator file', function () {
    $admin = User::factory()->create();
    grantDomainAccess($admin, 'facilitators');
    $province = makeFacilitatorProvince();

    $existingUser = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'facilitator-existing@example.test',
        'password' => Hash::make('password'),
    ]);

    $facilitator = Facilitator::query()->create([
        'name' => 'Neo',
        'surname' => 'Guide',
        'email' => 'facilitator-old@example.test',
        'user_id' => null,
        'province_id' => null,
    ]);

    $response = $this->actingAs($admin)->put("/facilitators/{$facilitator->id}", [
        'name' => 'Neo',
        'surname' => 'Guide',
        'email' => 'facilitator-existing@example.test',
        'cell' => '0711111111',
        'specialization' => 'Mentorship',
        'province_id' => (string) $province->id,
        'dob' => '',
        'id_number' => '',
        'address' => '',
    ]);

    $response->assertRedirect("/facilitators/{$facilitator->id}");

    $updated = $facilitator->fresh('user');

    expect($updated->user_id)->toBe($existingUser->id);
    expect($updated->user->name)->toBe('Neo Guide');
    expect($updated->province_id)->toBe($province->id);
});
