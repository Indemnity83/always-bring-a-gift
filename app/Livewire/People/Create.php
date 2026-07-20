<?php

namespace App\Livewire\People;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $name = '';

    public $profile_picture = null;

    public ?string $birthday = null;

    public bool $create_birthday_event = false;

    public string $birthday_target_value = '';

    public ?string $anniversary = null;

    public bool $create_anniversary_event = false;

    public string $anniversary_target_value = '';

    public bool $create_christmas_event = false;

    public string $christmas_target_value = '';

    public int $christmas_month = 12;

    public int $christmas_day = 25;

    public string $notes = '';

    public function mount(): void
    {
        $user = Auth::user();
        $monthDay = $user instanceof User
            ? $user->getChristmasDefaultDate()
            : config('reminders.christmas_default_date', '12-25');

        $parts = explode('-', $monthDay);
        $this->christmas_month = isset($parts[0]) ? (int) $parts[0] : 12;
        $this->christmas_day = isset($parts[1]) ? (int) $parts[1] : 25;
    }

    /**
     * Save the person
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'create_birthday_event' => ['boolean'],
            'birthday_target_value' => ['nullable', 'numeric', 'min:0'],
            'anniversary' => ['nullable', 'date', 'before_or_equal:today'],
            'create_anniversary_event' => ['boolean'],
            'anniversary_target_value' => ['nullable', 'numeric', 'min:0'],
            'create_christmas_event' => ['boolean'],
            'christmas_target_value' => ['nullable', 'numeric', 'min:0'],
            'christmas_month' => ['required', 'integer', 'between:1,12'],
            'christmas_day' => [
                'required',
                'integer',
                'between:1,31',
                function (string $attribute, int $value, \Closure $fail) {
                    if (! checkdate((int) $this->christmas_month, $value, 2000)) {
                        $fail('The christmas day is invalid for the selected month.');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $profilePicturePath = null;
        if ($this->profile_picture) {
            $profilePicturePath = $this->profile_picture->store('profile-pictures', 'public');
        }

        $person = Person::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'profile_picture' => $profilePicturePath,
            'birthday' => $validated['birthday'],
            'anniversary' => $validated['anniversary'],
            'christmas_default_date' => $this->formatChristmasMonthDay($validated),
            'notes' => $validated['notes'],
        ]);

        // Create Birthday event if requested and birthday is provided
        if ($validated['create_birthday_event'] && $validated['birthday']) {
            $birthdayType = EventType::where('name', 'Birthday')->first();
            if ($birthdayType) {
                Event::create([
                    'person_id' => $person->id,
                    'event_type_id' => $birthdayType->id,
                    'is_annual' => true,
                    'show_milestone' => true,
                    'date' => $validated['birthday'],
                    'budget' => $validated['birthday_target_value'] ?: null,
                ]);
            }
        }

        // Create Anniversary event if requested and anniversary is provided
        if ($validated['create_anniversary_event'] && $validated['anniversary']) {
            $anniversaryType = EventType::where('name', 'Anniversary')->first();
            if ($anniversaryType) {
                Event::create([
                    'person_id' => $person->id,
                    'event_type_id' => $anniversaryType->id,
                    'is_annual' => true,
                    'show_milestone' => true,
                    'date' => $validated['anniversary'],
                    'budget' => $validated['anniversary_target_value'] ?: null,
                ]);
            }
        }

        // Create Christmas event if requested
        if ($validated['create_christmas_event']) {
            $christmasType = EventType::where('name', EventType::CHRISTMAS_NAME)->first();
            if ($christmasType) {
                Event::create([
                    'person_id' => $person->id,
                    'event_type_id' => $christmasType->id,
                    'is_annual' => true,
                    'date' => $this->resolveChristmasDateForPerson($person),
                    'budget' => $validated['christmas_target_value'] ?: null,
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

    /**
     * Resolve the Christmas date for the given person.
     */
    protected function resolveChristmasDateForPerson(Person $person): string
    {
        $year = now()->year;
        return $person->getChristmasDateForYear($year);
    }

    /**
     * Format the Christmas month/day for storage.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function formatChristmasMonthDay(array $validated): string
    {
        return sprintf('%02d-%02d', $validated['christmas_month'], $validated['christmas_day']);
    }
}
