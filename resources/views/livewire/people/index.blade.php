<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex-1">
            <flux:heading size="xl">People</flux:heading>
            <flux:subheading>Manage the people you give gifts to</flux:subheading>
        </div>
        <div class="flex gap-2 flex-wrap sm:flex-nowrap">
            <flux:button variant="outline" href="{{ route('people.import') }}" icon="arrow-up-tray" wire:navigate class="flex-1 sm:flex-none">
                Import CSV
            </flux:button>
            <flux:button variant="primary" href="{{ route('people.create') }}" icon="plus" wire:navigate class="flex-1 sm:flex-none">
                Add Person
            </flux:button>
        </div>
    </div>

    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search people..." icon="magnifying-glass" />
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    @if ($people->isEmpty())
        <flux:callout variant="info">
            <strong>No people found</strong> - {{ $search ? 'Try a different search term' : 'Add your first person to get started' }}
        </flux:callout>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($people as $person)
                <div class="flex h-full flex-col rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start gap-4">
                        @if ($person->profile_picture)
                            <img src="{{ asset('storage/' . $person->profile_picture) }}" alt="{{ $person->name }}" class="h-16 w-16 rounded-full object-cover">
                        @else
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-xl font-bold text-white">
                                {{ strtoupper(substr($person->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $person->name }}
                            </h3>
                            @if ($person->birthday)
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $person->birthday->format('M j, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($person->events->isNotEmpty())
                        <div class="mt-4 mb-4 space-y-1">
                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-400">
                                Events ({{ $person->events_count }}):
                            </p>
                            <ul class="space-y-1">
                                @foreach ($person->events as $event)
                                    <li class="flex items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400">
                                        <flux:icon.calendar class="size-3 text-zinc-500 dark:text-zinc-500" />
                                        <span>{{ $event->eventType->name }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mt-auto flex gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button size="sm" variant="ghost" href="{{ route('people.show', $person) }}" wire:navigate class="flex-1">
                            View
                        </flux:button>
                        <flux:button size="sm" variant="ghost" href="{{ route('people.edit', $person) }}" wire:navigate class="flex-1">
                            Edit
                        </flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="delete({{ $person->id }})" wire:confirm="Are you sure you want to delete this person? All their events and gifts will also be deleted." icon="trash" class="hover:!text-red-600 dark:hover:!text-red-400" />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $people->links() }}
        </div>
    @endif
</div>
