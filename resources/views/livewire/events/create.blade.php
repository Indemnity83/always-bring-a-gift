<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Event for {{ $person->name }}</flux:heading>
        <flux:subheading>Create a new gifting event</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:select wire:model="event_type_id" label="Event Type" placeholder="Select event type..." required>
            @foreach ($eventTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model="recurrence" label="Recurrence" required>
            <option value="none">One-time</option>
            <option value="yearly">Yearly</option>
        </flux:select>

        <flux:switch wire:model="show_milestone" label="Track milestone (e.g., 38th Birthday, 5th Anniversary)" />

        <flux:input wire:model="date" label="Date" type="date" required />

        <flux:input wire:model="target_value" label="Target Budget (Optional)" type="number" step="0.01" min="0" placeholder="100.00" />

        <flux:textarea wire:model="notes" label="Gift Ideas / Notes" placeholder="Optional notes or gift ideas for this event..." rows="4" />

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
