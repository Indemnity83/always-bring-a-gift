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

    public bool $show_milestone = false;

    public string $date = '';

    public string $target_value = '';

    /**
     * Mount the component
     */
    public function mount(Event $event): void
    {
        $this->event = $event->load('person');
        $this->event_type_id = $event->event_type_id;
        $this->recurrence = $event->recurrence;
        $this->show_milestone = $event->show_milestone;
        $this->date = $event->date->format('Y-m-d');
        $this->target_value = $event->target_value ?? '';
    }

    /**
     * Update the event
     */
    public function update(): void
    {
        $validated = $this->validate([
            'event_type_id' => ['required', 'exists:event_types,id'],
            'recurrence' => ['required', 'in:none,yearly'],
            'show_milestone' => ['boolean'],
            'date' => ['required', 'date'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
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
