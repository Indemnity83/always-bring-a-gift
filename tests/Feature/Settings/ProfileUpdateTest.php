<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('timezone can be updated', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('timezone', 'America/New_York')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->timezone)->toEqual('America/New_York');
});

test('christmas default date can be updated', function () {
    $user = User::factory()->create(['christmas_default_date' => '12-25']);

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('christmasMonth', 12)
        ->set('christmasDay', 24)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->christmas_default_date)->toEqual('12-24');
});

test('timezone must be valid', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', $user->name)
        ->set('email', $user->email)
        ->set('timezone', 'Invalid/Timezone')
        ->call('updateProfileInformation');

    $response->assertHasErrors(['timezone']);
});

test('timezone defaults to UTC for new users', function () {
    $user = User::factory()->create();

    expect($user->getUserTimezone())->toEqual('UTC');
});
