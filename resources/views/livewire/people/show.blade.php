<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $person->name }}</flux:heading>
            @if ($person->birthday)
                <flux:subheading>Birthday: {{ $person->birthday->format('F j, Y') }}</flux:subheading>
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

    <div>
        <flux:field>
            <flux:label>Notes</flux:label>
            <flux:textarea
                wire:model.live.debounce.500ms="notes"
                placeholder="Add notes about this person..."
                rows="3"
            />
        </flux:field>
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
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-zinc-200 dark:border-zinc-700">
                        <tr class="text-left">
                            <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Event Type</th>
                            <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Date</th>
                            <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Recurrence</th>
                            <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Target</th>
                            <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100 w-1 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach ($person->events as $event)
                            <tr>
                                <td class="py-3 text-zinc-900 dark:text-zinc-100">
                                    {{ $event->eventType->name }}
                                </td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                    {{ $event->date->format('M j, Y') }}
                                </td>
                                <td class="py-3">
                                    @if ($event->recurrence === 'yearly')
                                        <flux:badge variant="primary" size="sm">Yearly</flux:badge>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500">-</span>
                                    @endif
                                </td>
                                <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                    @if ($event->target_value)
                                        ${{ number_format($event->target_value, 2) }}
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500">-</span>
                                    @endif
                                </td>
                                <td class="py-3 w-1">
                                    <div class="flex gap-2 whitespace-nowrap">
                                        <flux:button size="sm" variant="ghost" href="{{ route('events.edit', $event) }}" wire:navigate>
                                            Edit
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" href="{{ route('events.show', $event) }}" wire:navigate>
                                            View
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Gift Ideas</flux:heading>
        </div>

        <div class="space-y-3">
            <div class="flex gap-2">
                <flux:input
                    wire:model="newIdea"
                    wire:keydown.enter="addIdea"
                    placeholder="Add a gift idea..."
                    class="flex-1"
                />
                <flux:button wire:click="addIdea" variant="primary" icon="plus">
                    Add
                </flux:button>
            </div>

            @error('newIdea')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            @if ($person->giftIdeas->isEmpty())
                <flux:callout variant="info">
                    <strong>No gift ideas yet</strong> - Add ideas as they come to you so you're ready when it's time to give a gift!
                </flux:callout>
            @else
                <div class="space-y-2">
                    @foreach ($person->giftIdeas as $idea)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-3">
                            <span class="text-zinc-900 dark:text-zinc-100">{{ $idea->idea }}</span>
                            <flux:button
                                wire:click="deleteIdea({{ $idea->id }})"
                                wire:confirm="Are you sure you want to delete this idea?"
                                size="sm"
                                variant="ghost"
                                icon="trash"
                            />
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
