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

        <div class="space-y-3">
            <flux:input wire:model="link" label="Link (Optional)" type="url" placeholder="https://example.com/product" />

            <flux:switch wire:model.live="fetchImageFromLink" label="Fetch image from link" />

            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                <button
                    type="button"
                    wire:click="toggleManualImageUpload"
                    class="text-blue-600 dark:text-blue-400 hover:underline"
                >
                    {{ $showManualImageUpload ? 'Hide manual upload' : 'Upload manually' }}
                </button>
            </p>
        </div>

        @if ($showManualImageUpload && !$fetchImageFromLink)
            <div class="space-y-2">
                <flux:input wire:model="image" label="Manual Image Upload" type="file" accept="image/*" />
                @if ($gift->image_path)
                    <div class="text-sm text-zinc-600 dark:text-zinc-400">
                        Current image:
                        <img src="{{ asset('storage/' . $gift->image_path) }}" alt="Current gift image" class="mt-2 h-20 w-20 object-contain rounded">
                    </div>
                @endif
            </div>
        @endif

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">Update Gift</flux:button>
            <flux:button href="{{ route('events.show', $gift->event) }}" variant="ghost" wire:navigate>Cancel</flux:button>
        </div>
    </form>
</div>
