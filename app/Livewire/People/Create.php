<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $notes = '';

    /**
     * Save the person
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        Person::create($validated);

        session()->flash('status', 'Person created successfully.');

        $this->redirect(route('people.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.people.create');
    }
}
