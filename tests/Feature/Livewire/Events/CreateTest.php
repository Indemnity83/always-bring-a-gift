<?php

declare(strict_types=1);

use App\Livewire\Events\Create;
use App\Models\EventType;
use App\Models\Person;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('christmas event defaults to the person preference', function () {
    $person = Person::factory()->for($this->user)->create([
        'christmas_default_date' => '12-24',
    ]);
    $christmasType = EventType::factory()->create(['name' => 'Christmas']);

    Livewire::test(Create::class, ['person' => $person])
        ->set('event_type_id', $christmasType->id)
        ->assertSet('date', sprintf('%04d-12-24', now()->year));
});

test('christmas event falls back to user preference when person has none', function () {
    $this->user->update(['christmas_default_date' => '12-26']);
    $person = Person::factory()->for($this->user)->create([
        'christmas_default_date' => null,
    ]);
    $christmasType = EventType::factory()->create(['name' => 'Christmas']);

    Livewire::test(Create::class, ['person' => $person])
        ->set('event_type_id', $christmasType->id)
        ->assertSet('date', sprintf('%04d-12-26', now()->year));
});

test('christmas event falls back to config default', function () {
    $this->user->update(['christmas_default_date' => null]);
    $person = Person::factory()->for($this->user)->create([
        'christmas_default_date' => null,
    ]);
    $christmasType = EventType::factory()->create(['name' => 'Christmas']);

    Livewire::test(Create::class, ['person' => $person])
        ->set('event_type_id', $christmasType->id)
        ->assertSet('date', sprintf('%04d-12-25', now()->year));
});

test('non-christmas event keeps today as default date', function () {
    $person = Person::factory()->for($this->user)->create();
    $birthdayType = EventType::factory()->create(['name' => 'Birthday']);
    $today = now()->format('Y-m-d');

    Livewire::test(Create::class, ['person' => $person])
        ->assertSet('date', $today)
        ->set('event_type_id', $birthdayType->id)
        ->assertSet('date', $today);
});
