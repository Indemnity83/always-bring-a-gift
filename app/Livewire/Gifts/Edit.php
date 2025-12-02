<?php

namespace App\Livewire\Gifts;

use App\Models\Gift;
use App\Services\LinkPreviewService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Edit extends Component
{
    use WithFileUploads;

    public Gift $gift;

    public string $title = '';

    public string $value = '';

    public $image = null;

    public string $link = '';

    public bool $showManualImageUpload = false;

    public bool $fetchImageFromLink = false;

    /**
     * Mount the component
     */
    public function mount(Gift $gift): void
    {
        $this->gift = $gift->load('event.person');
        $this->title = $gift->title;
        $this->value = $gift->value ?? '';
        $this->link = $gift->link ?? '';
    }

    /**
     * Toggle manual image upload (mutually exclusive with fetch from link)
     */
    public function toggleManualImageUpload(): void
    {
        $this->showManualImageUpload = ! $this->showManualImageUpload;

        if ($this->showManualImageUpload) {
            $this->fetchImageFromLink = false;
        }
    }

    /**
     * When fetch from link is enabled, hide manual upload
     */
    public function updatedFetchImageFromLink($value): void
    {
        if ($value) {
            $this->showManualImageUpload = false;
        }
    }

    /**
     * Update the gift
     */
    public function update(LinkPreviewService $linkPreviewService): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'link' => ['nullable', 'url', 'max:255'],
        ]);

        $updateData = [
            'title' => $validated['title'],
            'value' => $validated['value'] ?: null,
            'link' => $validated['link'] ?: null,
        ];

        if ($this->image) {
            $updateData['image_path'] = $this->image->store('gifts', 'public');
        } elseif ($this->fetchImageFromLink && $validated['link']) {
            // Auto-fetch image from link if enabled and no image was uploaded
            $fetchedImagePath = $linkPreviewService->fetchImageFromUrl($validated['link']);
            if ($fetchedImagePath) {
                $updateData['image_path'] = $fetchedImagePath;
            }
        }

        $this->gift->update($updateData);

        if ($this->fetchImageFromLink && $validated['link'] && ! isset($updateData['image_path'])) {
            session()->flash('status', 'Gift updated, but image could not be fetched from link.');
        } else {
            session()->flash('status', 'Gift updated successfully.');
        }

        $this->redirect(route('events.show', $this->gift->event), navigate: true);
    }

    public function render()
    {
        return view('livewire.gifts.edit');
    }
}
