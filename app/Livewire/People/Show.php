<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Component;

class Show extends Component
{
    public Person $person;

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person->load(['events.eventType', 'events.gifts', 'events.completions']);
    }

    public function render()
    {
        return view('livewire.people.show');
    }
}
