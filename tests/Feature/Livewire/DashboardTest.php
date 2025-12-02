<?php

declare(strict_types=1);

use App\Livewire\Dashboard;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Gift;
use App\Models\Person;
use App\Models\User;
use App\Services\LinkPreviewService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can view dashboard', function () {
    Livewire::test(Dashboard::class)
        ->assertStatus(200);
});

test('can open gift modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->assertSet('showGiftModal', true)
        ->assertSet('selectedEventId', $event->id);
});

test('can close gift modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->assertSet('showGiftModal', true)
        ->call('closeGiftModal')
        ->assertSet('showGiftModal', false)
        ->assertSet('selectedEventId', null)
        ->assertSet('giftTitle', '')
        ->assertSet('giftValue', '');
});

test('can add gift via modal', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftValue', '50.00')
        ->call('saveGift')
        ->assertHasNoErrors()
        ->assertSet('showGiftModal', false);

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Test Gift')
        ->and((float) $gift->value)->toBe(50.00)
        ->and($gift->year)->toBe($event->next_occurrence_year);
});

test('can add gift without value', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Gift without value')
        ->call('saveGift')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Gift without value')
        ->and($gift->value)->toBeNull();
});

test('validates gift title is required', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', '')
        ->call('saveGift')
        ->assertHasErrors(['giftTitle' => 'required']);
});

test('validates gift value is numeric', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftValue', 'invalid')
        ->call('saveGift')
        ->assertHasErrors(['giftValue' => 'numeric']);
});

test('can add gift with link', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftLink', 'https://example.com/product')
        ->call('saveGift')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->link)->toBe('https://example.com/product');
});

test('validates gift link is valid url', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftLink', 'not-a-url')
        ->call('saveGift')
        ->assertHasErrors(['giftLink' => 'url']);
});

test('can toggle event completion', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeFalse();

    Livewire::test(Dashboard::class)
        ->call('toggleCompletion', $event->id);

    $event->refresh();
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeTrue();
});

test('can untoggle event completion', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    // Mark as complete first
    $event->markCompleteForYear($event->next_occurrence_year);
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeTrue();

    // Then toggle to incomplete
    Livewire::test(Dashboard::class)
        ->call('toggleCompletion', $event->id);

    $event->refresh();
    expect($event->isCompletedForYear($event->next_occurrence_year))->toBeFalse();
});

test('shows upcoming events including completed ones', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Create upcoming event
    $upcomingEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    // Create past event
    $pastEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->subDays(10),
        'recurrence' => 'yearly',
    ]);

    // Create completed upcoming event
    $completedEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(20),
        'recurrence' => 'yearly',
    ]);
    $completedEvent->markCompleteForYear($completedEvent->next_occurrence_year);

    $component = Livewire::test(Dashboard::class);
    $upcomingEvents = $component->get('upcomingEvents');

    // Should show both upcoming events (including the completed one)
    expect($upcomingEvents)->toHaveCount(2);
    expect($upcomingEvents->pluck('id')->toArray())
        ->toContain($upcomingEvent->id)
        ->toContain($completedEvent->id);
});

test('does not show past events', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Create past event
    $pastEvent = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->subDays(10),
        'recurrence' => 'yearly',
    ]);

    $component = Livewire::test(Dashboard::class);
    $upcomingEvents = $component->get('upcomingEvents');

    expect($upcomingEvents)->toHaveCount(0);
});

test('defaults to 30 day timeframe', function () {
    Livewire::test(Dashboard::class)
        ->assertSet('timeframeDays', 30);
});

test('can change timeframe to 60 days', function () {
    Livewire::test(Dashboard::class)
        ->call('setTimeframe', 60)
        ->assertSet('timeframeDays', 60);
});

test('can change timeframe to 90 days', function () {
    Livewire::test(Dashboard::class)
        ->call('setTimeframe', 90)
        ->assertSet('timeframeDays', 90);
});

test('can change timeframe to 6 months', function () {
    Livewire::test(Dashboard::class)
        ->call('setTimeframe', 180)
        ->assertSet('timeframeDays', 180);
});

test('can change timeframe to 1 year', function () {
    Livewire::test(Dashboard::class)
        ->call('setTimeframe', 365)
        ->assertSet('timeframeDays', 365);
});

test('filters events based on selected timeframe', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);

    // Create event in 20 days (within 30 days)
    $event20Days = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(20),
        'recurrence' => 'yearly',
    ]);

    // Create event in 50 days (outside 30 days, within 60 days)
    $event50Days = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(50),
        'recurrence' => 'yearly',
    ]);

    // Create event in 100 days (outside 60 days, within 90 days)
    $event100Days = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(100),
        'recurrence' => 'yearly',
    ]);

    // Test 30 day timeframe
    $component = Livewire::test(Dashboard::class);
    $upcomingEvents = $component->get('upcomingEvents');
    expect($upcomingEvents)->toHaveCount(1)
        ->first()->id->toBe($event20Days->id);

    // Test 60 day timeframe
    $component->call('setTimeframe', 60);
    $upcomingEvents = $component->get('upcomingEvents');
    expect($upcomingEvents)->toHaveCount(2);

    // Test 90+ day timeframe
    $component->call('setTimeframe', 180);
    $upcomingEvents = $component->get('upcomingEvents');
    expect($upcomingEvents)->toHaveCount(3);
});

test('auto-fetches image from link when no image uploaded', function () {
    Storage::fake('public');

    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'recurrence' => 'yearly',
    ]);

    // Mock the LinkPreviewService
    $this->mock(LinkPreviewService::class, function ($mock) {
        $mock->shouldReceive('fetchImageFromUrl')
            ->once()
            ->with('https://example.com/product')
            ->andReturn('gifts/fetched-image.jpg');
    });

    Livewire::test(Dashboard::class)
        ->call('openGiftModal', $event->id)
        ->set('giftTitle', 'Test Gift')
        ->set('giftLink', 'https://example.com/product')
        ->call('saveGift')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->link)->toBe('https://example.com/product')
        ->and($gift->image_path)->toBe('gifts/fetched-image.jpg');
});
