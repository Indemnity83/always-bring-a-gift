<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Person</flux:heading>
        <flux:subheading>Create a new person to track gifts for</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />

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
