<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Livewire\Component;

class Create extends Component
{
    public Person $person;

    public int $event_type_id;

    public string $recurrence = 'none';

    public bool $show_milestone = false;

    public string $date = '';

    public string $target_value = '';

    public string $notes = '';

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person;
        $this->date = now()->format('Y-m-d');
    }

    /**
     * Save the event
     */
    public function save(): void
    {
        $validated = $this->validate([
            'event_type_id' => ['required', 'exists:event_types,id'],
            'recurrence' => ['required', 'in:none,yearly'],
            'show_milestone' => ['boolean'],
            'date' => ['required', 'date'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['person_id'] = $this->person->id;
        $validated['target_value'] = $validated['target_value'] ?: null;

        Event::create($validated);

        session()->flash('status', 'Event created successfully.');

        $this->redirect(route('people.show', $this->person), navigate: true);
    }

    public function render()
    {
        $eventTypes = EventType::orderBy('is_custom')->orderBy('name')->get();

        return view('livewire.events.create', [
            'eventTypes' => $eventTypes,
        ]);
    }
}
