<?php

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow('2025-06-01');
});

afterEach(function () {
    Carbon::setTestNow();
});

test('calculates milestone correctly for yearly events', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Birthday on December 31, 1990 (will turn 35 in 2025)
    // Test date is June 1, so this birthday hasn't happened yet this year
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '1990-12-31',
    ]);

    expect($event->milestone)->toBe(35);
});

test('returns null milestone for non-yearly events', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Party']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'recurrence' => 'none',
        'show_milestone' => true,
        'date' => '2025-12-25',
    ]);

    expect($event->milestone)->toBeNull();
});

test('returns zero milestone for event in its original year', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Anniversary']);

    // Event created this year
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '2025-12-25',
    ]);

    expect($event->milestone)->toBe(0);
});

test('generates correct ordinal suffixes for numbers', function (int $number, string $expected) {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Calculate the birth year to produce the desired milestone
    // Test date is June 1, 2025, so use December 31 (after test date) to ensure next occurrence is this year
    $birthYear = 2025 - $number;

    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => "{$birthYear}-12-31",
    ]);

    expect($event->display_name)->toBe("{$expected} Birthday");
})->with([
    [1, '1st'],
    [2, '2nd'],
    [3, '3rd'],
    [4, '4th'],
    [11, '11th'],
    [12, '12th'],
    [13, '13th'],
    [21, '21st'],
    [22, '22nd'],
    [23, '23rd'],
    [31, '31st'],
    [38, '38th'],
    [101, '101st'],
    [111, '111th'],
    [121, '121st'],
]);

test('display name includes milestone for yearly events with milestone greater than zero', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Anniversary']);

    // Anniversary from 2020 (5 years ago)
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '2020-08-15',
    ]);

    expect($event->display_name)->toBe('5th Anniversary');
});

test('display name shows just event type for non-yearly events', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Party']);

    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'recurrence' => 'none',
        'date' => '2025-12-25',
    ]);

    expect($event->display_name)->toBe('Party');
});

test('display name shows just event type for yearly events in year zero', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Anniversary']);

    // Event happening this year for the first time
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '2025-12-25',
    ]);

    expect($event->display_name)->toBe('Anniversary');
});

test('milestone calculation accounts for next occurrence year', function () {
    // Set current date to June 1, 2025
    Carbon::setTestNow('2025-06-01');

    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Birthday on January 1, 1990 - already passed this year
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '1990-01-01',
    ]);

    // Next occurrence is in 2026, so milestone will be 36
    expect($event->milestone)->toBe(36);
});

test('milestone calculation for event that has not occurred this year', function () {
    // Set current date to June 1, 2025
    Carbon::setTestNow('2025-06-01');

    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Birthday on December 31, 1990 - has not occurred yet this year
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => true,
        'date' => '1990-12-31',
    ]);

    // Next occurrence is later this year (2025), so milestone will be 35
    expect($event->milestone)->toBe(35);
});

test('display name does not include milestone when flag is false', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Christmas']);

    // Christmas event with show_milestone disabled
    $event = Event::factory()->yearly()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'show_milestone' => false,
        'date' => '2020-12-25',
    ]);

    // Milestone exists but shouldn't be shown
    expect($event->milestone)->toBe(5);
    expect($event->display_name)->toBe('Christmas');
});
