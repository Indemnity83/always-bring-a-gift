<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Livewire\Component;

class Create extends Component
{
    public Person $person;

    public ?int $event_type_id = null;

    public bool $is_annual = false;

    public bool $show_milestone = false;

    public string $date = '';

    public string $budget = '';

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person;
        $this->date = now()->format('Y-m-d');
    }

    public function updatedEventTypeId(?int $eventTypeId): void
    {
        $this->applyEventTypeDefaults($eventTypeId);
    }

    public function applyEventTypeDefaults(?int $eventTypeId = null): void
    {
        $eventTypeId ??= $this->event_type_id;

        if (! $eventTypeId) {
            return;
        }

        $eventType = EventType::find($eventTypeId);

        if ($eventType && $eventType->name === EventType::CHRISTMAS_NAME) {
            $this->date = $this->resolveChristmasDate();
        }
    }

    /**
     * Save the event
     */
    public function save(): void
    {
        $validated = $this->validate([
            'event_type_id' => ['required', 'exists:event_types,id'],
            'is_annual' => ['boolean'],
            'show_milestone' => ['boolean'],
            'date' => ['required', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['person_id'] = $this->person->id;
        $validated['budget'] = $validated['budget'] ?: null;

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

    protected function resolveChristmasDate(): string
    {
        $year = now()->year;
        return $this->person->getChristmasDateForYear($year);
    }
}
