<?php

namespace App\Livewire\Gifts;

use App\Models\Event;
use App\Models\Gift;
use App\Services\LinkPreviewService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public Event $event;

    public int $year;

    public string $title = '';

    public string $value = '';

    public $image = null;

    public string $link = '';

    public bool $fetchImageFromLink = true;

    /**
     * Mount the component
     */
    public function mount(Event $event, int $year): void
    {
        $this->event = $event->load('person');
        $this->year = $year;
    }

    /**
     * Save the gift
     */
    public function save(LinkPreviewService $linkPreviewService): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'link' => ['nullable', 'url', 'max:255'],
        ]);

        $imagePath = null;
        if ($this->image) {
            $imagePath = $this->image->store('gifts', 'public');
        } elseif ($this->fetchImageFromLink && $validated['link']) {
            // Auto-fetch image from link if enabled and no image was uploaded
            $imagePath = $linkPreviewService->fetchImageFromUrl($validated['link']);
        }

        Gift::create([
            'event_id' => $this->event->id,
            'year' => $this->year,
            'title' => $validated['title'],
            'value' => $validated['value'] ?: null,
            'image_path' => $imagePath,
            'link' => $validated['link'] ?: null,
        ]);

        session()->flash('status', 'Gift added successfully.');

        $this->redirect(route('events.show', $this->event), navigate: true);
    }

    public function render()
    {
        return view('livewire.gifts.create');
    }
}
