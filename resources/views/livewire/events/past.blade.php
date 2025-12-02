<div class="space-y-6">
    <div>
        <flux:heading size="xl">Past Events</flux:heading>
        <flux:subheading>View completed and past events</flux:subheading>
    </div>

    @if ($this->pastEvents->isEmpty())
        <flux:callout variant="info">
            <strong>No past events</strong> - Completed events will appear here.
        </flux:callout>
    @else
        <div class="space-y-4">
            @foreach ($this->pastEvents as $event)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $event->person->name }} - {{ $event->eventType->name }}
                            </h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $event->date->format('F j, Y') }}
                                @if ($event->recurrence === 'yearly')
                                    <flux:badge variant="primary" size="sm" class="ml-2">Yearly</flux:badge>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($event->completions->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Completed Years:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($event->completions->sortByDesc('year') as $completion)
                                    @php
                                        $yearGifts = $event->gifts->where('year', $completion->year);
                                        $totalValue = $yearGifts->sum('value');
                                    @endphp
                                    <div class="text-xs bg-zinc-100 dark:bg-zinc-800 px-3 py-1 rounded">
                                        {{ $completion->year }}
                                        @if ($totalValue > 0)
                                            (${{ number_format($totalValue, 2) }})
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <flux:button size="sm" variant="outline" href="{{ route('events.show', $event) }}" wire:navigate>
                            View Details
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
