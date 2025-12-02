<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">People</flux:heading>
            <flux:subheading>Manage the people you give gifts to</flux:subheading>
        </div>
        <flux:button variant="primary" href="{{ route('people.create') }}" icon="plus" wire:navigate>
            Add Person
        </flux:button>
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
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Events
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @foreach ($people as $person)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $person->name }}
                                </div>
                                @if ($person->notes)
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ Str::limit($person->notes, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $person->events_count }} {{ Str::plural('event', $person->events_count) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" href="{{ route('people.show', $person) }}" wire:navigate>
                                        View
                                    </flux:button>
                                    <flux:button size="sm" variant="ghost" href="{{ route('people.edit', $person) }}" wire:navigate>
                                        Edit
                                    </flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="delete({{ $person->id }})" wire:confirm="Are you sure you want to delete this person? All their events and gifts will also be deleted.">
                                        Delete
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $people->links() }}
        </div>
    @endif
</div>
