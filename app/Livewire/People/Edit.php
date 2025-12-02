<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Component;

class Edit extends Component
{
    public Person $person;

    public string $name = '';

    public ?string $birthday = null;

    public string $notes = '';

    /**
     * Mount the component
     */
    public function mount(Person $person): void
    {
        $this->person = $person;
        $this->name = $person->name;
        $this->birthday = $person->birthday?->format('Y-m-d');
        $this->notes = $person->notes ?? '';
    }

    /**
     * Update the person
     */
    public function update(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->person->update($validated);

        session()->flash('status', 'Person updated successfully.');

        $this->redirect(route('people.show', $this->person), navigate: true);
    }

    public function render()
    {
        return view('livewire.people.edit');
    }
}
