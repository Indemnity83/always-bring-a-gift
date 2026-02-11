<?php

declare(strict_types=1);

use App\Livewire\People\Create;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can create person with name only', function () {
    Livewire::test(Create::class)
        ->set('name', 'John Doe')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'John Doe')->first();
    expect($person)->not->toBeNull();
    expect($person->user_id)->toBe($this->user->id);
});

test('can create person with birthday', function () {
    Livewire::test(Create::class)
        ->set('name', 'Jane Doe')
        ->set('birthday', '1990-05-15')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Jane Doe')->first();
    expect($person)
        ->not->toBeNull()
        ->and($person->birthday->format('Y-m-d'))->toBe('1990-05-15');
});

test('can create person with anniversary', function () {
    Livewire::test(Create::class)
        ->set('name', 'John and Jane Smith')
        ->set('anniversary', '2015-06-20')
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'John and Jane Smith')->first();
    expect($person)
        ->not->toBeNull()
        ->and($person->anniversary->format('Y-m-d'))->toBe('2015-06-20');
});

test('can create person with birthday event', function () {
    EventType::factory()->create(['name' => 'Birthday']);

    Livewire::test(Create::class)
        ->set('name', 'Bob Smith')
        ->set('birthday', '1985-03-20')
        ->set('create_birthday_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Bob Smith')->first();
    expect($person)->not->toBeNull();

    $event = Event::where('person_id', $person->id)->first();
    expect($event)
        ->not->toBeNull()
        ->and($event->eventType->name)->toBe('Birthday')
        ->and($event->is_annual)->toBeTrue()
        ->and($event->date->format('Y-m-d'))->toBe('1985-03-20');
});

test('can create person with anniversary event', function () {
    EventType::factory()->create(['name' => 'Anniversary']);

    Livewire::test(Create::class)
        ->set('name', 'Robert and Sarah')
        ->set('anniversary', '2010-08-14')
        ->set('create_anniversary_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Robert and Sarah')->first();
    expect($person)->not->toBeNull();

    $event = Event::where('person_id', $person->id)->first();
    expect($event)
        ->not->toBeNull()
        ->and($event->eventType->name)->toBe('Anniversary')
        ->and($event->is_annual)->toBeTrue()
        ->and($event->show_milestone)->toBeTrue()
        ->and($event->date->format('Y-m-d'))->toBe('2010-08-14');
});

test('can create person with christmas event', function () {
    EventType::factory()->create(['name' => 'Christmas']);

    Livewire::test(Create::class)
        ->set('name', 'Alice Jones')
        ->set('create_christmas_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Alice Jones')->first();
    expect($person)->not->toBeNull();

    $event = Event::where('person_id', $person->id)->first();
    expect($event)
        ->not->toBeNull()
        ->and($event->eventType->name)->toBe('Christmas')
        ->and($event->is_annual)->toBeTrue()
        ->and($event->date->format('m-d'))->toBe('12-25');
});

test('uses user default christmas date when creating christmas events', function () {
    EventType::factory()->create(['name' => 'Christmas']);

    $this->user->update(['christmas_default_date' => '12-24']);

    Livewire::test(Create::class)
        ->set('name', 'Holiday Friend')
        ->set('create_christmas_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Holiday Friend')->first();
    expect($person)->not->toBeNull();

    $event = Event::where('person_id', $person->id)->first();
    expect($event)
        ->not->toBeNull()
        ->and($event->eventType->name)->toBe('Christmas')
        ->and($event->date->format('m-d'))->toBe('12-24');
});

test('can override christmas date when creating a person', function () {
    EventType::factory()->create(['name' => 'Christmas']);

    $this->user->update(['christmas_default_date' => '12-25']);

    Livewire::test(Create::class)
        ->set('name', 'Override Friend')
        ->set('create_christmas_event', true)
        ->set('christmas_month', 12)
        ->set('christmas_day', 24)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Override Friend')->first();
    expect($person)->not->toBeNull();

    $event = Event::where('person_id', $person->id)->first();
    expect($event)
        ->not->toBeNull()
        ->and($event->eventType->name)->toBe('Christmas')
        ->and($event->date->format('m-d'))->toBe('12-24');
});

test('can create person with both birthday and christmas events', function () {
    EventType::factory()->create(['name' => 'Birthday']);
    EventType::factory()->create(['name' => 'Christmas']);

    Livewire::test(Create::class)
        ->set('name', 'Charlie Brown')
        ->set('birthday', '1992-07-10')
        ->set('create_birthday_event', true)
        ->set('create_christmas_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Charlie Brown')->first();
    expect($person)->not->toBeNull();

    $events = Event::where('person_id', $person->id)->with('eventType')->get();
    expect($events)->toHaveCount(2);

    $birthdayEvent = $events->first(fn ($event) => $event->eventType->name === 'Birthday');
    expect($birthdayEvent)->not->toBeNull();

    $christmasEvent = $events->first(fn ($event) => $event->eventType->name === 'Christmas');
    expect($christmasEvent)->not->toBeNull();
});

test('birthday event requires birthday date', function () {
    EventType::factory()->create(['name' => 'Birthday']);

    Livewire::test(Create::class)
        ->set('name', 'Test User')
        ->set('create_birthday_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Test User')->first();
    expect($person)->not->toBeNull();

    // Should not create birthday event without birthday date
    $event = Event::where('person_id', $person->id)->first();
    expect($event)->toBeNull();
});

test('anniversary event requires anniversary date', function () {
    EventType::factory()->create(['name' => 'Anniversary']);

    Livewire::test(Create::class)
        ->set('name', 'Test Couple')
        ->set('create_anniversary_event', true)
        ->call('save')
        ->assertHasNoErrors();

    $person = Person::where('name', 'Test Couple')->first();
    expect($person)->not->toBeNull();

    // Should not create anniversary event without anniversary date
    $event = Event::where('person_id', $person->id)->first();
    expect($event)->toBeNull();
});

test('validates birthday is not in future', function () {
    Livewire::test(Create::class)
        ->set('name', 'Future Person')
        ->set('birthday', now()->addYear()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['birthday' => 'before_or_equal']);
});

test('validates anniversary is not in future', function () {
    Livewire::test(Create::class)
        ->set('name', 'Future Couple')
        ->set('anniversary', now()->addYear()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['anniversary' => 'before_or_equal']);
});

test('requires name', function () {
    Livewire::test(Create::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});
