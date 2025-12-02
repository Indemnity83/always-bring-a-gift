<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Upcoming Events</flux:heading>
            <flux:subheading>
                Events in the next
                @if ($timeframeDays < 60)
                    {{ $timeframeDays }} days
                @elseif ($timeframeDays < 180)
                    {{ $timeframeDays }} days
                @elseif ($timeframeDays < 365)
                    6 months
                @else
                    year
                @endif
            </flux:subheading>
        </div>
        <flux:button variant="primary" href="{{ route('people.index') }}" icon="users">
            Manage People
        </flux:button>
    </div>

    {{-- Timeframe Selector --}}
    <div class="flex gap-2 flex-wrap items-center justify-between">
        <div class="flex gap-2 flex-wrap">
            <flux:button
                size="sm"
                variant="{{ $timeframeDays === 30 ? 'primary' : 'ghost' }}"
                wire:click="setTimeframe(30)"
            >
                30 Days
            </flux:button>
            <flux:button
                size="sm"
                variant="{{ $timeframeDays === 60 ? 'primary' : 'ghost' }}"
                wire:click="setTimeframe(60)"
            >
                60 Days
            </flux:button>
            <flux:button
                size="sm"
                variant="{{ $timeframeDays === 90 ? 'primary' : 'ghost' }}"
                wire:click="setTimeframe(90)"
            >
                90 Days
            </flux:button>
            <flux:button
                size="sm"
                variant="{{ $timeframeDays === 180 ? 'primary' : 'ghost' }}"
                wire:click="setTimeframe(180)"
            >
                6 Months
            </flux:button>
            <flux:button
                size="sm"
                variant="{{ $timeframeDays === 365 ? 'primary' : 'ghost' }}"
                wire:click="setTimeframe(365)"
            >
                1 Year
            </flux:button>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-600 dark:text-zinc-400">No Peeking</span>
            <flux:switch wire:model.live="noPeeking" />
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if ($this->upcomingEvents->isEmpty())
        <flux:callout variant="info">
            <strong>No upcoming events</strong> - You don't have any events in the selected timeframe.
            <a href="{{ route('people.index') }}" class="underline">Add people and events</a> to get started.
        </flux:callout>
    @else
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->upcomingEvents as $event)
                @php
                    $isCompleted = $event->isCompletedForYear($event->next_occurrence_year);
                @endphp
                <div class="rounded-lg border p-6 shadow-sm flex flex-col {{ $isCompleted ? 'border-zinc-300/50 dark:border-zinc-600/50 bg-zinc-50/50 dark:bg-zinc-800/50 opacity-75' : 'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="text-lg font-semibold {{ $isCompleted ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-900 dark:text-zinc-100' }}">
                                {{ $event->person->name }}
                            </h3>
                            <a
                                href="{{ route('events.show', $event) }}"
                                wire:navigate
                                class="text-sm {{ $isCompleted ? 'text-zinc-500 dark:text-zinc-500' : 'text-zinc-600 dark:text-zinc-400' }} no-underline hover:underline hover:{{ $isCompleted ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-900 dark:text-zinc-100' }} transition-colors inline-block"
                            >
                                {{ $event->eventType->name }} • {{ $event->next_occurrence->format('M j') }} ({{ $event->next_occurrence->diffForHumans() }})
                            </a>
                        </div>
                        @if ($isCompleted)
                            <flux:badge color="green" variant="solid" size="sm" icon="check">Complete</flux:badge>
                        @endif
                    </div>

                    <div class="space-y-3">

                        @if ($event->target_value && !$noPeeking)
                            @php
                                $totalValue = $event->totalGiftsValueForYear($event->next_occurrence_year);
                                $remaining = $event->remainingValueForYear($event->next_occurrence_year);
                                $percentage = $event->target_value > 0 ? ($totalValue / $event->target_value) * 100 : 0;
                            @endphp
                            <div class="space-y-1">
                                <div class="flex justify-between text-sm">
                                    <span class="{{ $isCompleted ? 'text-zinc-500 dark:text-zinc-500' : 'text-zinc-600 dark:text-zinc-400' }}">Budget</span>
                                    <span class="font-medium {{ $isCompleted ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-900 dark:text-zinc-100' }}">
                                        ${{ number_format($totalValue, 2) }} / ${{ number_format($event->target_value, 2) }}
                                    </span>
                                </div>
                                <div class="h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $isCompleted ? 'bg-zinc-400 dark:bg-zinc-500' : ($percentage > 100 ? 'bg-red-400 dark:bg-red-500' : 'bg-blue-500 dark:bg-blue-400') }}" style="width: {{ min($percentage, 100) }}%"></div>
                                </div>
                                @if (!$isCompleted)
                                    @if ($remaining < 0)
                                        <p class="text-xs text-red-500 dark:text-red-400">
                                            Over budget by ${{ number_format(abs($remaining), 2) }}
                                        </p>
                                    @elseif ($remaining > 0)
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            ${{ number_format($remaining, 2) }} remaining
                                        </p>
                                    @endif
                                @endif
                            </div>
                        @endif

                        @if (!$noPeeking)
                            @php
                                $giftsThisYear = $event->gifts()->where('year', $event->next_occurrence_year)->get();
                            @endphp
                            @if ($giftsThisYear->isNotEmpty())
                                <div class="space-y-1">
                                    <p class="text-xs font-medium {{ $isCompleted ? 'text-zinc-600 dark:text-zinc-500' : 'text-zinc-700 dark:text-zinc-400' }}">
                                        Gifts ({{ $giftsThisYear->count() }}):
                                    </p>
                                    <ul class="space-y-1">
                                        @foreach ($giftsThisYear as $gift)
                                            <li class="text-xs {{ $isCompleted ? 'text-zinc-500 dark:text-zinc-500' : 'text-zinc-600 dark:text-zinc-400' }} flex items-center gap-1">
                                                <flux:icon.gift class="size-3 {{ $isCompleted ? 'text-zinc-400 dark:text-zinc-600' : 'text-zinc-500 dark:text-zinc-500' }}" />
                                                <span>{{ $gift->title }}</span>
                                                @if ($gift->value)
                                                    <span class="{{ $isCompleted ? 'text-zinc-400 dark:text-zinc-600' : 'text-zinc-500 dark:text-zinc-500' }}">
                                                        (${{ number_format($gift->value, 2) }})
                                                    </span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="flex gap-2 pt-4 mt-auto">
                            @if (!$isCompleted)
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    wire:click="openGiftModal({{ $event->id }})"
                                    icon="plus"
                                >
                                    Add Gift
                                </flux:button>
                            @endif
                            <flux:button
                                size="sm"
                                variant="{{ $isCompleted ? 'ghost' : 'outline' }}"
                                wire:click="toggleCompletion({{ $event->id }})"
                                icon="{{ $isCompleted ? 'x-mark' : 'check' }}"
                                class="{{ $isCompleted ? 'flex-1' : '' }}"
                            >
                                {{ $isCompleted ? 'Uncomplete' : 'Complete' }}
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="ghost"
                                href="{{ route('events.show', $event) }}"
                                icon="arrow-right"
                                class="{{ $isCompleted ? 'flex-1' : '' }}"
                            >
                                Details
                            </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add Gift Modal --}}
    <flux:modal wire:model="showGiftModal" class="max-w-md">
        <form wire:submit="saveGift" class="space-y-6">
            <div>
                <flux:heading size="lg">Add Gift</flux:heading>
                @if ($selectedEventId)
                    @php
                        $selectedEvent = $this->upcomingEvents->firstWhere('id', $selectedEventId);
                    @endphp
                    @if ($selectedEvent)
                        <flux:subheading>
                            {{ $selectedEvent->eventType->name }} for {{ $selectedEvent->person->name }} ({{ $selectedEvent->next_occurrence_year }})
                        </flux:subheading>
                    @endif
                @endif
            </div>

            <flux:input
                wire:model="giftTitle"
                label="Gift Title"
                placeholder="e.g., Watch, Book, Gift Card..."
                required
            />

            <flux:input
                wire:model="giftValue"
                label="Value (Optional)"
                type="number"
                step="0.01"
                min="0"
                placeholder="50.00"
            />

            <div class="space-y-3">
                <flux:input
                    wire:model="giftLink"
                    label="Link (Optional)"
                    type="url"
                    placeholder="https://example.com/product"
                />

                <flux:switch wire:model.live="fetchImageFromLink" label="Fetch image from link" />
            </div>

            @if (!$fetchImageFromLink)
                <flux:input
                    wire:model="giftImage"
                    label="Image Upload"
                    type="file"
                    accept="image/*"
                />
            @endif

            <div class="flex gap-3 justify-end">
                <flux:button type="button" variant="ghost" wire:click="closeGiftModal">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Add Gift
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
