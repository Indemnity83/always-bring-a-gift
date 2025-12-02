<div class="space-y-6">
    <div>
        <flux:heading size="xl">Add Gift</flux:heading>
        <flux:subheading>{{ $event->eventType->name }} for {{ $event->person->name }} ({{ $year }})</flux:subheading>
    </div>

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <flux:input wire:model="title" label="Gift Title" placeholder="e.g., Watch, Book, Gift Card..." required />
        <flux:input wire:model="value" label="Value (Optional)" type="number" step="0.01" min="0" placeholder="50.00" />

        <div class="space-y-3">
            <flux:input wire:model="link" label="Link (Optional)" type="url" placeholder="https://example.com/product" />

            <flux:switch wire:model.live="fetchImageFromLink" label="Fetch image from link" />
        </div>

        @if (!$fetchImageFromLink)
            <flux:input wire:model="image" label="Image Upload" type="file" accept="image/*" />
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Add Gift</flux:button>
            <flux:button href="{{ route('events.show', $event) }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
