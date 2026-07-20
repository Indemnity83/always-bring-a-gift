<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Notifications')" :subheading="__('Configure reminder timing and channels')">
        @error('test')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ $message }}
            </div>
        @enderror

        <div x-data="{ testSent: null }"
             x-on:test-sent.window="testSent = $event.detail.channel; setTimeout(() => testSent = null, 4000)">
            <div x-show="testSent" x-transition class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                <span x-text="testSent + ' test sent! Check your ' + testSent.toLowerCase() + ' for the message.'"></span>
            </div>
        </div>

        <form wire:submit="save" class="my-6 w-full space-y-6">
            <flux:input
                wire:model="leadTimeDays"
                :label="__('Days in advance')"
                type="number"
                min="1"
                max="365"
                helper-text="{{ __('How many days before an event to notify you') }}"
            />

            <flux:input
                wire:model="remindAt"
                :label="__('Send reminders at')"
                type="time"
                helper-text="{{ __('Based on your profile timezone') }}"
            />

            <div class="space-y-4">
                <flux:heading size="sm">{{ __('Channels') }}</flux:heading>

                <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <flux:checkbox wire:model="channels" value="mail" :label="__('Email')" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Sends to your account email:') }} {{ auth()->user()->email }}
                    </p>
                    <flux:button size="sm" variant="ghost" type="button" wire:click.prevent="sendTest('mail')">
                        {{ __('Send test email') }}
                    </flux:button>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <flux:checkbox wire:model="channels" value="slack" :label="__('Slack')" />
                    <flux:input
                        wire:model.live.debounce.300ms="slackWebhook"
                        :label="__('Slack webhook URL')"
                        placeholder="https://hooks.slack.com/services/..."
                    />
                    <flux:button size="sm" variant="ghost" type="button" wire:click.prevent="sendTest('slack')">
                        {{ __('Send test Slack message') }}
                    </flux:button>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <flux:checkbox wire:model="channels" value="discord" :label="__('Discord')" />
                    <flux:input
                        wire:model.live.debounce.300ms="discordWebhook"
                        :label="__('Discord webhook URL')"
                        placeholder="https://discord.com/api/webhooks/..."
                    />
                    <flux:button size="sm" variant="ghost" type="button" wire:click.prevent="sendTest('discord')">
                        {{ __('Send test Discord message') }}
                    </flux:button>
                </div>

                <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-800">
                    <flux:checkbox wire:model="channels" value="push" :label="__('Push / webhook')" />
                    <flux:input
                        wire:model.live.debounce.300ms="pushEndpoint"
                        :label="__('Endpoint')"
                        placeholder="https://your.push.service/notify"
                    />
                    <flux:input
                        wire:model.live.debounce.300ms="pushToken"
                        :label="__('Auth token (optional)')"
                        type="password"
                        placeholder="Bearer/secret token"
                    />
                    <flux:button size="sm" variant="ghost" type="button" wire:click.prevent="sendTest('push')">
                        {{ __('Send test push') }}
                    </flux:button>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>

                <x-action-message class="me-3" on="notifications-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
