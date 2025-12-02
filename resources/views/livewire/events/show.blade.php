<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $event->eventType->name }} - {{ $event->person->name }}</flux:heading>
            <flux:subheading>
                {{ $event->next_occurrence->format('F j, Y') }} ({{ $event->next_occurrence->diffForHumans() }})
                @if ($event->recurrence === 'yearly')
                    <flux:badge variant="primary" class="ml-2">Yearly</flux:badge>
                @endif
            </flux:subheading>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('events.edit', $event) }}" variant="primary" wire:navigate>Edit</flux:button>
            <flux:button href="{{ route('people.show', $event->person) }}" variant="ghost" wire:navigate>Back</flux:button>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success">{{ session('status') }}</flux:callout>
    @endif

    @if ($event->notes)
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 p-4">
            <p class="text-sm text-zinc-700 dark:text-zinc-300"><strong>Notes:</strong> {{ $event->notes }}</p>
        </div>
    @endif

    @if ($event->target_value)
        @php
            $totalValue = $event->totalGiftsValueForYear($nextOccurrenceYear);
            $remaining = $event->remainingValueForYear($nextOccurrenceYear);
            $percentage = $event->target_value > 0 ? ($totalValue / $event->target_value) * 100 : 0;
        @endphp
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
            <h3 class="text-lg font-semibold mb-4">Budget for {{ $nextOccurrenceYear }}</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span>Spent</span>
                    <span class="font-medium">${{ number_format($totalValue, 2) }} / ${{ number_format($event->target_value, 2) }}</span>
                </div>
                <div class="h-3 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full {{ $percentage > 100 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ min($percentage, 100) }}%"></div>
                </div>
                @if ($remaining < 0)
                    <p class="text-sm text-red-600 dark:text-red-400">Over budget by ${{ number_format(abs($remaining), 2) }}</p>
                @else
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">${{ number_format($remaining, 2) }} remaining</p>
                @endif
            </div>
        </div>
    @endif

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Gifts for {{ $nextOccurrenceYear }}</flux:heading>
            <div class="flex gap-2">
                <flux:button href="{{ route('gifts.create', [$event, $nextOccurrenceYear]) }}" variant="primary" icon="plus" wire:navigate>Add Gift</flux:button>
                @if ($isCompleted)
                    <flux:button wire:click="toggleCompletion" variant="outline">Mark Incomplete</flux:button>
                @else
                    <flux:button wire:click="toggleCompletion" variant="primary">Mark Complete</flux:button>
                @endif
            </div>
        </div>

        @if ($giftsThisYear->isEmpty())
            <flux:callout variant="info">No gifts logged yet for {{ $nextOccurrenceYear }}</flux:callout>
        @else
            <div class="space-y-3">
                @foreach ($giftsThisYear as $gift)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-4 flex items-center gap-4 justify-between">
                        <div class="flex items-center gap-4 flex-1">
                            @if ($gift->image_path)
                                <img src="{{ asset('storage/' . $gift->image_path) }}" alt="{{ $gift->title }}" class="h-16 w-16 object-contain rounded">
                            @endif
                            <div class="flex-1">
                                <h4 class="font-medium text-zinc-900 dark:text-zinc-100">{{ $gift->title }}</h4>
                                @if ($gift->value)
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">${{ number_format($gift->value, 2) }}</p>
                                @endif
                                @if ($gift->link)
                                    <a href="{{ $gift->link }}" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                        View Product
                                        <flux:icon.arrow-up-right class="size-3" />
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" href="{{ route('gifts.edit', $gift) }}" wire:navigate>Edit</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="deleteGift({{ $gift->id }})" wire:confirm="Delete this gift?">Delete</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
