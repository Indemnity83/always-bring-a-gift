<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Event for {{ $person->name }}</flux:heading>
        <flux:subheading>Create a new gifting event</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:select wire:model.live="event_type_id" label="Event Type" required>
            <option value="">Select event type...</option>
            @foreach ($eventTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </flux:select>

        <flux:input wire:model="date" label="Date" type="date" required />

        <flux:switch wire:model.live="is_annual" label="Annual Event" />

        @if ($is_annual)
            <div x-transition class="pl-6">
                <flux:switch wire:model="show_milestone" label="Track milestone (e.g., 38th Birthday, 5th Anniversary)" />
            </div>
        @endif

        <flux:input wire:model="budget" label="Budget (Optional)" type="number" step="0.01" min="0" placeholder="100.00" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Create Event
            </flux:button>
            <flux:button href="{{ route('people.show', $person) }}" variant="ghost" wire:navigate>
                Cancel
            </flux:button>
        </div>
    </form>
</div>
