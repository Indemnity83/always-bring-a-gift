<?php

namespace App\Livewire\Gifts;

use App\Models\Gift;
use Livewire\Component;

class Edit extends Component
{
    public Gift $gift;

    public string $title = '';

    public string $value = '';

    /**
     * Mount the component
     */
    public function mount(Gift $gift): void
    {
        $this->gift = $gift->load('event.person');
        $this->title = $gift->title;
        $this->value = $gift->value ?? '';
    }

    /**
     * Update the gift
     */
    public function update(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['value'] = $validated['value'] ?: null;

        $this->gift->update($validated);

        session()->flash('status', 'Gift updated successfully.');

        $this->redirect(route('events.show', $this->gift->event), navigate: true);
    }

    public function render()
    {
        return view('livewire.gifts.edit');
    }
}
