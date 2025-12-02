<?php

namespace App\Livewire\People;

use App\Models\Person;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Person $person;

    public string $name = '';

    public $profile_picture = null;

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
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->profile_picture) {
            // Delete old profile picture if exists
            if ($this->person->profile_picture) {
                Storage::disk('public')->delete($this->person->profile_picture);
            }

            // Store new profile picture
            $validated['profile_picture'] = $this->profile_picture->store('profile-pictures', 'public');
        } else {
            // Keep existing profile picture
            unset($validated['profile_picture']);
        }

        $this->person->update($validated);

        session()->flash('status', 'Person updated successfully.');

        $this->redirect(route('people.show', $this->person), navigate: true);
    }

    public function render()
    {
        return view('livewire.people.edit');
    }
}
