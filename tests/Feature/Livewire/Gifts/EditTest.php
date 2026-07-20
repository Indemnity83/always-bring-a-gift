<?php

declare(strict_types=1);

use App\Livewire\Gifts\Edit;
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

test('can view edit gift page', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->assertStatus(200)
        ->assertSet('title', 'Original Gift');
});

test('can update gift with all fields', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    $file = UploadedFile::fake()->create('updated-gift.jpg', 100, 'image/jpeg');

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('title', 'Updated Gift')
        ->set('value', '149.99')
        ->set('image', $file)
        ->set('link', 'https://example.com/updated-product')
        ->call('update')
        ->assertHasNoErrors();

    $gift->refresh();
    expect($gift->title)->toBe('Updated Gift')
        ->and((float) $gift->value)->toBe(149.99)
        ->and($gift->link)->toBe('https://example.com/updated-product')
        ->and($gift->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($gift->image_path);
});

test('can update gift without changing image', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    $originalImage = UploadedFile::fake()->create('original.jpg', 100, 'image/jpeg');
    $imagePath = $originalImage->store('gifts', 'public');

    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
        'image_path' => $imagePath,
    ]);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('title', 'Updated Title Only')
        ->call('update')
        ->assertHasNoErrors();

    $gift->refresh();
    expect($gift->title)->toBe('Updated Title Only')
        ->and($gift->image_path)->toBe($imagePath);
});

test('can replace existing image', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);

    $originalImage = UploadedFile::fake()->create('original.jpg', 100, 'image/jpeg');
    $originalPath = $originalImage->store('gifts', 'public');

    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Gift with Image',
        'image_path' => $originalPath,
    ]);

    $newImage = UploadedFile::fake()->create('new.jpg', 100, 'image/jpeg');

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('image', $newImage)
        ->call('update')
        ->assertHasNoErrors();

    $gift->refresh();
    expect($gift->image_path)->not->toBe($originalPath);
    Storage::disk('public')->assertExists($gift->image_path);
});

test('validates title is required on update', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('title', '')
        ->call('update')
        ->assertHasErrors(['title' => 'required']);
});

test('validates value is numeric on update', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('value', 'invalid')
        ->call('update')
        ->assertHasErrors(['value' => 'numeric']);
});

test('validates link is valid url on update', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('link', 'not-a-url')
        ->call('update')
        ->assertHasErrors(['link' => 'url']);
});

test('validates image is an image file on update', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Original Gift',
    ]);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('image', $file)
        ->call('update')
        ->assertHasErrors(['image' => 'image']);
});

test('auto-fetches image from link when no image exists', function () {
    $person = Person::factory()->create();
    $eventType = EventType::factory()->create(['name' => 'Birthday']);
    $event = Event::factory()->create([
        'person_id' => $person->id,
        'event_type_id' => $eventType->id,
        'date' => now()->addDays(10),
        'is_annual' => true,
    ]);
    $gift = Gift::factory()->create([
        'event_id' => $event->id,
        'year' => now()->year,
        'title' => 'Gift Without Image',
        'image_path' => null,
    ]);

    // Mock the LinkPreviewService
    $this->mock(LinkPreviewService::class, function ($mock) {
        $mock->shouldReceive('fetchImageFromUrl')
            ->once()
            ->with('https://example.com/updated-product')
            ->andReturn('gifts/auto-fetched-edit.jpg');
    });

    Livewire::test(Edit::class, ['gift' => $gift])
        ->set('link', 'https://example.com/updated-product')
        ->set('fetchImageFromLink', true)
        ->call('update')
        ->assertHasNoErrors();

    $gift->refresh();
    expect($gift->link)->toBe('https://example.com/updated-product')
        ->and($gift->image_path)->toBe('gifts/auto-fetched-edit.jpg');
});
