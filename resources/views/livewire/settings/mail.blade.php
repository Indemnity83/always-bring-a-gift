<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Email')" :subheading="__('Configure SMTP details for reminders and app email')" >
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Mailer') }}</label>
                <select wire:model="driver" class="w-full rounded-md border border-gray-300 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                    <option value="smtp">SMTP</option>
                    <option value="sendmail">Sendmail</option>
                    <option value="log">Log (debug only)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="host" :label="__('Host')" placeholder="smtp.example.com" />
                <flux:input wire:model="port" :label="__('Port')" type="number" min="1" max="65535" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="username" :label="__('Username')" autocomplete="off" />
                <flux:input wire:model="password" :label="__('Password')" type="password" autocomplete="off" />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('Encryption') }}</label>
                    <select wire:model="encryption" class="w-full rounded-md border border-gray-300 bg-white p-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                        <option value="">{{ __('None') }}</option>
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                    </select>
                </div>
                <div></div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="fromAddress" :label="__('From email')" placeholder="hello@example.com" />
                <flux:input wire:model="fromName" :label="__('From name')" placeholder="Always Bring a Gift" />
            </div>

            <flux:input wire:model="testRecipient" :label="__('Test recipient (optional)')" placeholder="override@example.com" />

            <flux:callout icon="information-circle" variant="muted">
                {{ __('Changes apply immediately for new reminders. Ensure credentials are valid; failed sends will appear in logs.') }}
            </flux:callout>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                <flux:button variant="ghost" type="button" wire:click="sendTestEmail">{{ __('Send test email') }}</flux:button>
                <x-action-message class="me-3" on="mail-settings-saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>
