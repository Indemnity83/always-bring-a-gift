<?php

namespace App\Livewire\People;

use App\Models\GiftIdea;
use App\Models\Person;
use Livewire\Component;

class Show extends Component
{
    public Person $person;

    public string $notes = '';

    public string $newIdea = '';

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person->load(['events.eventType', 'events.gifts', 'events.completions', 'giftIdeas']);
        $this->notes = $this->person->notes ?? '';

    }

    /**
     * Update the person's notes
     */
    public function updatedNotes(): void
    {
        $this->person->update(['notes' => $this->notes]);
    }

    /**
     * Add a new gift idea
     */
    public function addIdea(): void
    {
        $this->validate([
            'newIdea' => 'required|string|max:1000',
        ]);

        $this->person->giftIdeas()->create([
            'idea' => $this->newIdea,
        ]);

        $this->newIdea = '';
        $this->person->load('giftIdeas');
    }

    /**
     * Delete a gift idea
     */
    public function deleteIdea(GiftIdea $giftIdea): void
    {
        if ($giftIdea->person_id !== $this->person->id) {
            return;
        }

        $giftIdea->delete();
        $this->person->load('giftIdeas');
    }

    /**
     * Delete an event
     */
    public function deleteEvent(int $eventId): void
    {
        $event = $this->person->events()->findOrFail($eventId);
        $event->delete();
        $this->person->load(['events.eventType', 'events.gifts', 'events.completions']);
    }

    public function render()
    {
        return view('livewire.people.show');
    }
}
