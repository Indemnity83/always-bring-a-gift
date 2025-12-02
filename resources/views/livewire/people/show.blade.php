<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $person->name }}</flux:heading>
            @if ($person->birthday)
                <flux:subheading>Birthday: {{ $person->birthday->format('F j, Y') }}</flux:subheading>
            @endif
            @if ($person->notes)
                <flux:subheading>{{ $person->notes }}</flux:subheading>
            @endif
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('people.edit', $person) }}" variant="primary" wire:navigate>
                Edit
            </flux:button>
            <flux:button href="{{ route('people.index') }}" variant="ghost" wire:navigate>
                Back to List
            </flux:button>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Events</flux:heading>
            <flux:button href="{{ route('events.create', $person) }}" variant="primary" icon="plus" wire:navigate>
                Add Event
            </flux:button>
        </div>

        @if ($person->events->isEmpty())
            <flux:callout variant="info">
                <strong>No events yet</strong> - Add an event to start tracking gifts for this person.
            </flux:callout>
        @else
            <div class="grid gap-4">
                @foreach ($person->events as $event)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $event->eventType->name }}
                                </h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $event->date->format('F j, Y') }}
                                    @if ($event->recurrence === 'yearly')
                                        <flux:badge variant="primary" size="sm" class="ml-2">Yearly</flux:badge>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($event->target_value)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">
                                Target: ${{ number_format($event->target_value, 2) }}
                            </p>
                        @endif

                        @if ($event->notes)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-3 italic">
                                {{ $event->notes }}
                            </p>
                        @endif

                        <div class="flex gap-2">
                            <flux:button size="sm" variant="outline" href="{{ route('events.show', $event) }}" wire:navigate>
                                View Details
                            </flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
