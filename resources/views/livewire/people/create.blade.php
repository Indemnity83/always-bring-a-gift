<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Person</flux:heading>
        <flux:subheading>Create a new person to track gifts for</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />

        <div>
            <flux:field>
                <flux:label>Profile Picture (Optional)</flux:label>
                <input type="file" wire:model="profile_picture" accept="image/*" class="block w-full text-sm text-zinc-900 dark:text-zinc-100 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700">
                @error('profile_picture')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:field>
            @if ($profile_picture)
                <div class="mt-2">
                    <img src="{{ $profile_picture->temporaryUrl() }}" alt="Preview" class="h-24 w-24 rounded-full object-cover">
                </div>
            @endif
        </div>

        <flux:input wire:model="birthday" label="Birthday (Optional)" type="date" />

        <flux:input wire:model="anniversary" label="Anniversary (Optional)" type="date" />

        <div class="space-y-4">
            <div class="space-y-2">
                <flux:checkbox wire:model.live="create_birthday_event" label="Create annual Birthday event" />
                <div x-show="$wire.create_birthday_event" x-transition class="pl-6">
                    <flux:input
                        wire:model="birthday_target_value"
                        label="Birthday Budget"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="100.00"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <flux:checkbox wire:model.live="create_anniversary_event" label="Create annual Anniversary event" />
                <div x-show="$wire.create_anniversary_event" x-transition class="pl-6">
                    <flux:input
                        wire:model="anniversary_target_value"
                        label="Anniversary Budget"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="100.00"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <flux:checkbox wire:model.live="create_christmas_event" label="Create annual Christmas event" />
                <div x-show="$wire.create_christmas_event" x-transition class="pl-6">
                    <flux:input
                        wire:model="christmas_target_value"
                        label="Christmas Budget"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="100.00"
                    />
                </div>
            </div>
        </div>

        <flux:textarea wire:model="notes" label="Notes" placeholder="Optional notes about this person..." rows="4" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Person
            </flux:button>
            <flux:button href="{{ route('people.index') }}" variant="ghost" wire:navigate>
                Cancel
            </flux:button>
        </div>
    </form>
</div>
