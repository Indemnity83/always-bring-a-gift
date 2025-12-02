<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Person</flux:heading>
        <flux:subheading>Create a new person to track gifts for</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />

        <flux:input wire:model="birthday" label="Birthday (Optional)" type="date" />

        <div class="space-y-4">
            <div class="space-y-2">
                <flux:checkbox wire:model.live="create_birthday_event" label="Create yearly Birthday event" />
                <div x-show="$wire.create_birthday_event" x-transition class="pl-6">
                    <flux:input
                        wire:model="birthday_target_value"
                        label="Birthday Target Amount"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="100.00"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <flux:checkbox wire:model.live="create_christmas_event" label="Create yearly Christmas event" />
                <div x-show="$wire.create_christmas_event" x-transition class="pl-6">
                    <flux:input
                        wire:model="christmas_target_value"
                        label="Christmas Target Amount"
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
