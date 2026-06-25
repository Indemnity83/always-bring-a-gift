<?php

declare(strict_types=1);

use App\Livewire\Gifts\Create;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Gift;
use App\Models\Person;
use App\Models\User;
use App\Services\LinkPreviewService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    Storage::fake('public');
});

test('can view create gift page', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->assertStatus(200);
});

test('can create gift with all fields', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    $file = UploadedFile::fake()->create('gift.jpg', 100, 'image/jpeg');

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Test Gift')
        ->set('value', '99.99')
        ->set('image', $file)
        ->set('link', 'https://example.com/product')
        ->call('save')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Test Gift')
        ->and((float) $gift->value)->toBe(99.99)
        ->and($gift->link)->toBe('https://example.com/product')
        ->and($gift->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($gift->image_path);
});

test('can create gift without optional fields', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Simple Gift')
        ->call('save')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->title)->toBe('Simple Gift')
        ->and($gift->value)->toBeNull()
        ->and($gift->link)->toBeNull()
        ->and($gift->image_path)->toBeNull();
});

test('validates title is required', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('validates value is numeric', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Test Gift')
        ->set('value', 'invalid')
        ->call('save')
        ->assertHasErrors(['value' => 'numeric']);
});

test('validates link is valid url', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Test Gift')
        ->set('link', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['link' => 'url']);
});

test('validates image is an image file', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Test Gift')
        ->set('image', $file)
        ->call('save')
        ->assertHasErrors(['image' => 'image']);
});

test('auto-fetches image from link when no image uploaded', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    // Mock the LinkPreviewService
    $this->mock(LinkPreviewService::class, function ($mock) {
        $mock->shouldReceive('fetchImageFromUrl')
            ->once()
            ->with('https://example.com/product')
            ->andReturn('gifts/auto-fetched.jpg');
    });

    Livewire::test(Create::class, ['event' => $event, 'year' => now()->year])
        ->set('title', 'Auto Fetch Test')
        ->set('link', 'https://example.com/product')
        ->call('save')
        ->assertHasNoErrors();

    $gift = Gift::where('event_id', $event->id)->first();
    expect($gift)
        ->not->toBeNull()
        ->and($gift->link)->toBe('https://example.com/product')
        ->and($gift->image_path)->toBe('gifts/auto-fetched.jpg');
});
