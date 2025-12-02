<?php

namespace App\Livewire\People;

use App\Models\Person;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Delete a person
     */
    public function delete(Person $person): void
    {
        $person->delete();

        session()->flash('status', 'Person deleted successfully.');
    }

    public function render()
    {
        $people = Person::query()
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->with(['events.eventType'])
            ->withCount('events')
            ->latest()
            ->paginate(15);

        return view('livewire.people.index', [
            'people' => $people,
        ]);
    }
}
