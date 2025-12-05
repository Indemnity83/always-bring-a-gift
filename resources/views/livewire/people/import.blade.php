<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex-1">
            <flux:heading size="xl">Import People</flux:heading>
            <flux:subheading>Bulk import people from CSV or vCard files</flux:subheading>
        </div>
        <flux:button href="{{ route('people.index') }}" variant="ghost" wire:navigate class="w-full sm:w-auto">
            Back to List
        </flux:button>
    </div>

    @if (session('success'))
        <flux:callout variant="success">
            {{ session('success') }}
        </flux:callout>
    @endif

    @if ($errors->any())
        <flux:callout variant="danger">
            <strong>There were errors with your import:</strong>
            <ul class="mt-2 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    @if (!$showPreview)
        {{-- Step 1: Upload File --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <div class="space-y-4">
                        <div>
                            <flux:heading size="lg">Upload File</flux:heading>
                            <flux:subheading>Select a CSV or vCard file to import people</flux:subheading>
                        </div>

                        <form wire:submit="parseFile">
                            <div class="space-y-4">
                                <flux:input
                                    wire:model="csvFile"
                                    label="File"
                                    type="file"
                                    accept=".csv,text/csv,.vcf,text/vcard,text/x-vcard"
                                    required
                                />

                                <flux:button type="submit" variant="primary" icon="arrow-right" class="w-full">
                                    Parse File
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                    <div class="space-y-4">
                        <div>
                            <flux:heading size="lg">CSV Format</flux:heading>
                            <flux:subheading>Simple format - just person data</flux:subheading>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 p-3 font-mono text-xs overflow-x-auto">
                                <div class="whitespace-nowrap">name,birthday,anniversary,notes</div>
                                <div class="whitespace-nowrap text-zinc-600 dark:text-zinc-400">John Doe,1990-05-15,2015-06-20,Loves tech</div>
                                <div class="whitespace-nowrap text-zinc-600 dark:text-zinc-400">Jane Smith,1985-03-22,2010-08-15,</div>
                                <div class="whitespace-nowrap text-zinc-600 dark:text-zinc-400">Bob Johnson,,,Coffee fan</div>
                            </div>

                            <div class="space-y-2 text-sm">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">Columns:</p>
                                <ul class="space-y-1 text-zinc-600 dark:text-zinc-400">
                                    <li><strong>name</strong> (required): Person's full name</li>
                                    <li><strong>birthday</strong> (optional): Birth date in YYYY-MM-DD format</li>
                                    <li><strong>anniversary</strong> (optional): Anniversary date in YYYY-MM-DD format</li>
                                    <li><strong>notes</strong> (optional): Notes about the person</li>
                                </ul>
                            </div>

                            <flux:separator />

                            <div class="space-y-2 text-sm">
                                <p class="font-semibold text-zinc-900 dark:text-zinc-100">vCard Support:</p>
                                <p class="text-zinc-600 dark:text-zinc-400">
                                    You can also upload vCard (.vcf) files exported from your contacts app.
                                    We'll extract name, birthday, anniversary, notes, and profile pictures automatically.
                                </p>
                            </div>

                            <a href="{{ url('people/import?download=template') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-4 py-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Template CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Step 2: Preview and Configure Per-Person Events --}}
        <div class="space-y-6">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                <div class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <flux:heading size="lg">{{ count($parsedPeople) }} People Ready to Import</flux:heading>
                            <flux:subheading>Configure events for each person individually</flux:subheading>
                        </div>
                        <flux:button wire:click="resetImport" variant="ghost" size="sm">
                            Start Over
                        </flux:button>
                    </div>

                    {{-- People Table with Per-Person Controls --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b-2 border-zinc-300 dark:border-zinc-600">
                                <tr class="text-left">
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Name</th>
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Birthday</th>
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100">Anniversary</th>
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100 w-48">
                                        <div>Birthday Event</div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-normal">
                                            @if($showBirthdayBudgetInput)
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        wire:model.live="headerBirthdayBudget"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Amount"
                                                        class="w-24 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    />
                                                    <button
                                                        wire:click="applyBirthdayBudgetToAll"
                                                        type="button"
                                                        class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                                    >
                                                        Set
                                                    </button>
                                                    <button
                                                        wire:click="$set('showBirthdayBudgetInput', false)"
                                                        type="button"
                                                        class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300"
                                                    >
                                                        X
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    wire:click="toggleAllBirthday"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Toggle All
                                                </button>
                                                <span class="text-zinc-400">•</span>
                                                <button
                                                    wire:click="$set('showBirthdayBudgetInput', true)"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Set Budget
                                                </button>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100 w-48">
                                        <div>Christmas Event</div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-normal">
                                            @if($showChristmasBudgetInput)
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        wire:model.live="headerChristmasBudget"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Amount"
                                                        class="w-24 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    />
                                                    <button
                                                        wire:click="applyChristmasBudgetToAll"
                                                        type="button"
                                                        class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                                    >
                                                        Set
                                                    </button>
                                                    <button
                                                        wire:click="$set('showChristmasBudgetInput', false)"
                                                        type="button"
                                                        class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300"
                                                    >
                                                        X
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    wire:click="toggleAllChristmas"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Toggle All
                                                </button>
                                                <span class="text-zinc-400">•</span>
                                                <button
                                                    wire:click="$set('showChristmasBudgetInput', true)"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Set Budget
                                                </button>
                                            @endif
                                        </div>
                                    </th>
                                    <th class="pb-3 font-semibold text-zinc-900 dark:text-zinc-100 w-48">
                                        <div>Anniversary Event</div>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-normal">
                                            @if($showAnniversaryBudgetInput)
                                                <div class="flex items-center gap-2">
                                                    <input
                                                        wire:model.live="headerAnniversaryBudget"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Amount"
                                                        class="w-24 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                    />
                                                    <button
                                                        wire:click="applyAnniversaryBudgetToAll"
                                                        type="button"
                                                        class="text-blue-600 dark:text-blue-400 hover:underline font-medium"
                                                    >
                                                        Set
                                                    </button>
                                                    <button
                                                        wire:click="$set('showAnniversaryBudgetInput', false)"
                                                        type="button"
                                                        class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300"
                                                    >
                                                        X
                                                    </button>
                                                </div>
                                            @else
                                                <button
                                                    wire:click="toggleAllAnniversary"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Toggle All
                                                </button>
                                                <span class="text-zinc-400">•</span>
                                                <button
                                                    wire:click="$set('showAnniversaryBudgetInput', true)"
                                                    type="button"
                                                    class="text-blue-600 dark:text-blue-400 hover:underline"
                                                >
                                                    Set Budget
                                                </button>
                                            @endif
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($parsedPeople as $index => $person)
                                    <tr>
                                        <td class="py-3">
                                            <div class="max-w-xs">
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $person['name'] }}</div>
                                                @if($person['notes'])
                                                    <div
                                                        class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate"
                                                        title="{{ $person['notes'] }}"
                                                    >
                                                        {{ $person['notes'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                            @if($person['birthday'])
                                                {{ \Carbon\Carbon::parse($person['birthday'])->format('M j, Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3 text-zinc-600 dark:text-zinc-400">
                                            @if($person['anniversary'])
                                                {{ \Carbon\Carbon::parse($person['anniversary'])->format('M j, Y') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <flux:checkbox
                                                    wire:model.live="parsedPeople.{{ $index }}.add_birthday"
                                                    :disabled="!$person['birthday']"
                                                />
                                                <input
                                                    wire:model.live="parsedPeople.{{ $index }}.birthday_budget"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="$"
                                                    @disabled(!$person['add_birthday'] || !$person['birthday'])
                                                    class="w-20 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                />
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <flux:checkbox
                                                    wire:model.live="parsedPeople.{{ $index }}.add_christmas"
                                                />
                                                <input
                                                    wire:model.live="parsedPeople.{{ $index }}.christmas_budget"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="$"
                                                    @disabled(!$person['add_christmas'])
                                                    class="w-20 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                />
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <div class="flex items-center gap-2">
                                                <flux:checkbox
                                                    wire:model.live="parsedPeople.{{ $index }}.add_anniversary"
                                                    :disabled="!$person['anniversary']"
                                                />
                                                <input
                                                    wire:model.live="parsedPeople.{{ $index }}.anniversary_budget"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="$"
                                                    @disabled(!$person['add_anniversary'] || !$person['anniversary'])
                                                    class="w-20 px-2 py-1 text-xs border-b-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800 focus:border-blue-500 dark:focus:border-blue-400 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Import Button --}}
                    <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                        <flux:button wire:click="resetImport" variant="ghost">
                            Cancel
                        </flux:button>
                        <flux:button wire:click="import" variant="primary" icon="check">
                            Import {{ count($parsedPeople) }} People
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
