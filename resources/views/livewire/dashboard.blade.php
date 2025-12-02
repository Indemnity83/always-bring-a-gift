<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Upcoming Events</flux:heading>
            <flux:subheading>Events in the next 30 days</flux:subheading>
        </div>
        <flux:button variant="primary" href="{{ route('people.index') }}" icon="users">
            Manage People
        </flux:button>
    </div>

    @if ($this->upcomingEvents->isEmpty())
        <flux:callout variant="info">
            <strong>No upcoming events</strong> - You don't have any events in the next 30 days.
            <a href="{{ route('people.index') }}" class="underline">Add people and events</a> to get started.
        </flux:callout>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->upcomingEvents as $event)
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6 shadow-sm">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $event->person->name }}
                            </h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $event->eventType->name }}
                            </p>
                        </div>
                        @if ($event->recurrence === 'yearly')
                            <flux:badge variant="primary" size="sm">Yearly</flux:badge>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon.calendar-days class="size-4 text-zinc-500 dark:text-zinc-400" />
                            <span class="text-zinc-700 dark:text-zinc-300">
                                {{ $event->next_occurrence->format('F j, Y') }}
                                ({{ $event->next_occurrence->diffForHumans() }})
                            </span>
                        </div>

                        @if ($event->target_value)
                            @php
                                $totalValue = $event->totalGiftsValueForYear($event->next_occurrence_year);
                                $remaining = $event->remainingValueForYear($event->next_occurrence_year);
                                $percentage = $event->target_value > 0 ? ($totalValue / $event->target_value) * 100 : 0;
                            @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-zinc-600 dark:text-zinc-400">Budget</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                        ${{ number_format($totalValue, 2) }} / ${{ number_format($event->target_value, 2) }}
                                    </span>
                                </div>
                                <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $percentage > 100 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                                @if ($remaining < 0)
                                    <p class="text-xs text-red-600 dark:text-red-400">
                                        Over budget by ${{ number_format(abs($remaining), 2) }}
                                    </p>
                                @elseif ($remaining > 0)
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        ${{ number_format($remaining, 2) }} remaining
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="flex gap-2 pt-2">
                            <flux:button size="sm" variant="outline" href="{{ route('events.show', $event) }}" class="flex-1">
                                View Details
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
