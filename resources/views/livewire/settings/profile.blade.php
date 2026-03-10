<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:select wire:model="timezone" :label="__('Timezone')" searchable placeholder="Select timezone..." required>
                @foreach ($this->timezones as $tz => $displayName)
                    <option value="{{ $tz }}">{{ $displayName }}</option>
                @endforeach
            </flux:select>
            <flux:description class="-mt-4">{{ __('Used to determine when to send notifications based on your local time.') }}</flux:description>
            @error('timezone')
                <flux:text class="mt-2 text-sm text-red-600">{{ $message }}</flux:text>
            @enderror

            <div>
                <flux:label>{{ __('Default Christmas date') }}</flux:label>
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <flux:select wire:model="christmasMonth" required>
                        @foreach ($this->christmasMonths as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="christmasDay" required>
                        @foreach ($this->christmasDays as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:description class="mt-2">{{ __('Used when creating annual Christmas events.') }}</flux:description>
                @error('christmasMonth')
                    <flux:text class="mt-2 text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
                @error('christmasDay')
                    <flux:text class="mt-2 text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
