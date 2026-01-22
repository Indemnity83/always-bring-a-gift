<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $timezone = 'UTC';

    public int $christmasMonth = 12;

    public int $christmasDay = 25;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->timezone = $user->getUserTimezone();

        $monthDay = $user->getChristmasDefaultDate();
        [$month, $day] = array_map('intval', explode('-', $monthDay));
        $this->christmasMonth = $month;
        $this->christmasDay = $day;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'timezone' => ['required', 'string', 'timezone:all'],
            'christmasMonth' => ['required', 'integer', 'between:1,12'],
            'christmasDay' => [
                'required',
                'integer',
                'between:1,31',
                function (string $attribute, int $value, \Closure $fail) {
                    if (! checkdate($this->christmasMonth, $value, 2000)) {
                        $fail('The christmas day is invalid for the selected month.');
                    }
                },
            ],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'timezone' => $validated['timezone'],
            'christmas_default_date' => sprintf('%02d-%02d', $validated['christmasMonth'], $validated['christmasDay']),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Get the list of available timezones as a flat array.
     *
     * @return array<string, string>
     */
    public function getTimezonesProperty(): array
    {
        static $cachedTimezones = null;

        if ($cachedTimezones !== null) {
            return $cachedTimezones;
        }

        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $list = [];

        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone, 2);
            if (count($parts) === 1) {
                $displayName = $timezone;
            } else {
                $region = $parts[0];
                $city = str_replace(['_', '/'], [' ', ' / '], $parts[1]);
                $displayName = $region.' / '.$city;
            }
            $list[$timezone] = $displayName;
        }

        // Sort by display name
        asort($list);

        $cachedTimezones = $list;

        return $cachedTimezones;
    }

    /**
     * Get the list of months for the Christmas default date selector.
     *
     * @return array<int, string>
     */
    public function getChristmasMonthsProperty(): array
    {
        return [
            1 => __('January'),
            2 => __('February'),
            3 => __('March'),
            4 => __('April'),
            5 => __('May'),
            6 => __('June'),
            7 => __('July'),
            8 => __('August'),
            9 => __('September'),
            10 => __('October'),
            11 => __('November'),
            12 => __('December'),
        ];
    }

    /**
     * Get the list of days for the Christmas default date selector.
     *
     * @return array<int, string>
     */
    public function getChristmasDaysProperty(): array
    {
        $month = $this->christmasMonth ?: 12;
        $date = \DateTime::createFromFormat('Y-m-d', sprintf('2000-%02d-01', $month));
        $daysInMonth = $date ? (int) $date->format('t') : 31;
        $days = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $days[$day] = (string) $day;
        }

        return $days;
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
