<?php

declare(strict_types=1);

use App\Livewire\People\Show;
use App\Models\GiftIdea;
use App\Models\Person;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can view person details', function () {
    $person = Person::factory()->create(['name' => 'John Doe']);

    Livewire::test(Show::class, ['person' => $person])
        ->assertSee('John Doe')
        ->assertStatus(200);
});

test('can update notes live', function () {
    $person = Person::factory()->create(['name' => 'Jane Doe', 'notes' => 'Initial notes']);

    Livewire::test(Show::class, ['person' => $person])
        ->set('notes', 'Updated notes')
        ->assertHasNoErrors();

    expect(Person::find($person->id)->notes)->toBe('Updated notes');
});

test('can add gift idea', function () {
    $person = Person::factory()->create(['name' => 'Bob Smith']);

    Livewire::test(Show::class, ['person' => $person])
        ->set('newIdea', 'A cool gadget')
        ->call('addIdea')
        ->assertHasNoErrors();

    expect(GiftIdea::where('person_id', $person->id)->where('idea', 'A cool gadget')->exists())->toBeTrue();
});

test('can add gift idea with enter key', function () {
    $person = Person::factory()->create(['name' => 'Alice Jones']);

    Livewire::test(Show::class, ['person' => $person])
        ->set('newIdea', 'A nice book')
        ->call('addIdea')
        ->assertHasNoErrors();

    expect(GiftIdea::where('person_id', $person->id)->where('idea', 'A nice book')->exists())->toBeTrue();
});

test('clears input after adding gift idea', function () {
    $person = Person::factory()->create(['name' => 'Charlie Brown']);

    Livewire::test(Show::class, ['person' => $person])
        ->set('newIdea', 'Something special')
        ->call('addIdea')
        ->assertSet('newIdea', '');
});

test('validates gift idea is required', function () {
    $person = Person::factory()->create(['name' => 'Test User']);

    Livewire::test(Show::class, ['person' => $person])
        ->set('newIdea', '')
        ->call('addIdea')
        ->assertHasErrors(['newIdea' => 'required']);
});

test('validates gift idea max length', function () {
    $person = Person::factory()->create(['name' => 'Test User']);
    $longIdea = str_repeat('a', 1001);

    Livewire::test(Show::class, ['person' => $person])
        ->set('newIdea', $longIdea)
        ->call('addIdea')
        ->assertHasErrors(['newIdea' => 'max']);
});

test('can delete gift idea', function () {
    $person = Person::factory()->create(['name' => 'Delete Test']);
    $giftIdea = GiftIdea::factory()->create([
        'person_id' => $person->id,
        'idea' => 'To be deleted',
    ]);

    Livewire::test(Show::class, ['person' => $person])
        ->call('deleteIdea', $giftIdea)
        ->assertHasNoErrors();

    expect(GiftIdea::find($giftIdea->id))->toBeNull();
});

test('cannot delete gift idea belonging to different person', function () {
    $person1 = Person::factory()->create(['name' => 'Person 1']);
    $person2 = Person::factory()->create(['name' => 'Person 2']);

    $giftIdea = GiftIdea::factory()->create([
        'person_id' => $person2->id,
        'idea' => 'Protected idea',
    ]);

    Livewire::test(Show::class, ['person' => $person1])
        ->call('deleteIdea', $giftIdea);

    // Gift idea should still exist
    expect(GiftIdea::find($giftIdea->id))->not->toBeNull();
});

test('displays multiple gift ideas', function () {
    $person = Person::factory()->create(['name' => 'Multi Idea']);

    GiftIdea::factory()->create([
        'person_id' => $person->id,
        'idea' => 'First idea',
    ]);

    GiftIdea::factory()->create([
        'person_id' => $person->id,
        'idea' => 'Second idea',
    ]);

    Livewire::test(Show::class, ['person' => $person])
        ->assertSee('First idea')
        ->assertSee('Second idea');
});

test('shows empty state when no gift ideas', function () {
    $person = Person::factory()->create(['name' => 'No Ideas']);

    Livewire::test(Show::class, ['person' => $person])
        ->assertSee('No gift ideas yet');
});
