<div class="space-y-6">
    <div>
        <flux:heading size="xl">Edit Gift</flux:heading>
        <flux:subheading>{{ $gift->event->eventType->name }} for {{ $gift->event->person->name }}</flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    <form wire:submit="update" class="max-w-2xl space-y-6">
        <flux:input wire:model="title" label="Gift Title" required />
        <flux:input wire:model="value" label="Value" type="number" step="0.01" min="0" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update Gift</flux:button>
            <flux:button href="{{ route('events.show', $gift->event) }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
