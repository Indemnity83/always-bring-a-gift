<?php

namespace App\Livewire\Gifts;

use App\Models\Event;
use App\Models\Gift;
use Livewire\Component;

class Create extends Component
{
    public Event $event;

    public int $year;

    public string $title = '';

    public string $value = '';

    /**
     * Mount the component
     */
    public function mount(Event $event, int $year): void
    {
        $this->event = $event->load('person');
        $this->year = $year;
    }

    /**
     * Save the gift
     */
    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['event_id'] = $this->event->id;
        $validated['year'] = $this->year;
        $validated['value'] = $validated['value'] ?: null;

        Gift::create($validated);

        session()->flash('status', 'Gift added successfully.');

        $this->redirect(route('events.show', $this->event), navigate: true);
    }

    public function render()
    {
        return view('livewire.gifts.create');
    }
}
