<div class="space-y-6">
    <div>
        <flux:heading size="xl">Edit Person</flux:heading>
        <flux:subheading>Update {{ $person->name }}'s information</flux:subheading>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="update" class="max-w-2xl space-y-6">
        <flux:input wire:model="name" label="Name" placeholder="John Doe" required />

        <div>
            <flux:field>
                <flux:label>Profile Picture (Optional)</flux:label>
                <input type="file" wire:model="profile_picture" accept="image/*" class="block w-full text-sm text-zinc-900 dark:text-zinc-100 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700">
                @error('profile_picture')
                    <flux:error>{{ $message }}</flux:error>
                @enderror
            </flux:field>
            <div class="mt-2 flex items-center gap-4">
                @if ($profile_picture)
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">New:</p>
                        <img src="{{ $profile_picture->temporaryUrl() }}" alt="Preview" class="h-24 w-24 rounded-full object-cover">
                    </div>
                @endif
                @if ($person->profile_picture && !$profile_picture)
                    <div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-1">Current:</p>
                        <img src="{{ asset('storage/' . $person->profile_picture) }}" alt="Current" class="h-24 w-24 rounded-full object-cover">
                    </div>
                @endif
            </div>
        </div>

        <flux:input wire:model="birthday" label="Birthday (Optional)" type="date" />

        <flux:textarea wire:model="notes" label="Notes" placeholder="Optional notes about this person..." rows="4" />

        <div class="flex gap-3">
            <flux:button type="submit" variant="primary">
                Update Person
            </flux:button>
            <flux:button href="{{ route('people.show', $person) }}" variant="ghost" wire:navigate>
                Cancel
            </flux:button>
        </div>
    </form>
</div>
