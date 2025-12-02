<div class="space-y-6">
    <div>
        <flux:heading size="xl">Edit Event</flux:heading>
        <flux:subheading>Update event for {{ $event->person->name }}</flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="update" class="max-w-2xl space-y-6">
        <flux:select wire:model="event_type_id" label="Event Type" required>
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
        <flux:input wire:model="target_value" label="Target Budget" type="number" step="0.01" min="0" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update Event</flux:button>
            <flux:button href="{{ route('events.show', $event) }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
