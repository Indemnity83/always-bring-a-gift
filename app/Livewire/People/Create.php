<?php

namespace App\Livewire\People;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public ?string $birthday = null;

    public bool $create_birthday_event = false;

    public string $birthday_target_value = '';

    public bool $create_christmas_event = false;

    public string $christmas_target_value = '';

    public string $notes = '';

    /**
     * Save the person
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'create_birthday_event' => ['boolean'],
            'birthday_target_value' => ['nullable', 'numeric', 'min:0'],
            'create_christmas_event' => ['boolean'],
            'christmas_target_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $person = Person::create([
            'name' => $validated['name'],
            'birthday' => $validated['birthday'],
            'notes' => $validated['notes'],
        ]);

        // Create Birthday event if requested and birthday is provided
        if ($validated['create_birthday_event'] && $validated['birthday']) {
            $birthdayType = EventType::where('name', 'Birthday')->first();
            if ($birthdayType) {
                Event::create([
                    'person_id' => $person->id,
                    'event_type_id' => $birthdayType->id,
                    'recurrence' => 'yearly',
                    'date' => $validated['birthday'],
                    'target_value' => $validated['birthday_target_value'] ?: null,
                ]);
            }
        }

        // Create Christmas event if requested
        if ($validated['create_christmas_event']) {
            $christmasType = EventType::where('name', 'Christmas')->first();
            if ($christmasType) {
                Event::create([
                    'person_id' => $person->id,
                    'event_type_id' => $christmasType->id,
                    'recurrence' => 'yearly',
                    'date' => now()->year.'-12-25',
                    'target_value' => $validated['christmas_target_value'] ?: null,
                ]);
            }
        }

        session()->flash('status', 'Person created successfully.');

        $this->redirect(route('people.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.people.create');
    }
}
