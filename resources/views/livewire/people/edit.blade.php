<div class="space-y-6">
    <div>
        <flux:heading size="xl">Edit Person</flux:heading>
        <flux:subheading>Update {{ $person->name }}'s information</flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="update" class="max-w-2xl space-y-6">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />

        <flux:input wire:model="birthday" label="Birthday (Optional)" type="date" />

        <flux:textarea wire:model="notes" label="Notes" placeholder="Optional notes about this person..." rows="4" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Person
            </flux:button>
            <flux:button href="{{ route('people.show', $person) }}" variant="ghost" wire:navigate>
                Cancel
            </flux:button>
        </div>
    </form>
</div>
