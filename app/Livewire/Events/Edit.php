<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventType;
use Livewire\Component;

class Edit extends Component
{
    public Event $event;

    public int $event_type_id;

    public string $recurrence = 'none';

    public string $date = '';

    public string $target_value = '';

    public string $notes = '';

    /**
     * Mount the component
     */
    public function mount(Event $event): void
    {
        $this->event = $event->load('person');
        $this->event_type_id = $event->event_type_id;
        $this->recurrence = $event->recurrence;
        $this->date = $event->date->format('Y-m-d');
        $this->target_value = $event->target_value ?? '';
        $this->notes = $event->notes ?? '';
    }

    /**
     * Update the event
     */
    public function update(): void
    {
        $validated = $this->validate([
            'event_type_id' => ['required', 'exists:event_types,id'],
            'recurrence' => ['required', 'in:none,yearly'],
            'date' => ['required', 'date'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['target_value'] = $validated['target_value'] ?: null;

        $this->event->update($validated);

        session()->flash('status', 'Event updated successfully.');

        $this->redirect(route('events.show', $this->event), navigate: true);
    }

    public function render()
    {
        $eventTypes = EventType::orderBy('is_custom')->orderBy('name')->get();

        return view('livewire.events.edit', [
            'eventTypes' => $eventTypes,
        ]);
    }
}
