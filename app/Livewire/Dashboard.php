<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Gift;
use App\Services\LinkPreviewService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;

    public bool $showGiftModal = false;

    public ?int $selectedEventId = null;

    public string $giftTitle = '';

    public string $giftValue = '';

    public $giftImage = null;

    public string $giftLink = '';

    public bool $fetchImageFromLink = true;

    public int $timeframeDays = 30;

    #[Session]
    public bool $noPeeking = false;

    /**
     * Set the timeframe for upcoming events
     */
    public function setTimeframe(int $days): void
    {
        $this->timeframeDays = $days;
    }

    /**
     * Toggle no-peeking mode
     */
    public function toggleNoPeeking(): void
    {
        $this->noPeeking = ! $this->noPeeking;
    }

    /**
     * Get upcoming events based on selected timeframe
     */
    public function getUpcomingEventsProperty(): Collection
    {
        $today = now()->startOfDay();
        $endDate = now()->addDays($this->timeframeDays);

        return Event::with(['person', 'eventType', 'gifts', 'completions'])
            ->get()
            ->filter(function ($event) use ($today, $endDate) {
                $nextOccurrence = $event->next_occurrence;

                return $nextOccurrence->between($today, $endDate);
            })
            ->sortBy('next_occurrence')
            ->values();
    }

    /**
     * Open the gift modal for a specific event
     */
    public function openGiftModal(int $eventId): void
    {
        $this->selectedEventId = $eventId;
        $this->giftTitle = '';
        $this->giftValue = '';
        $this->giftImage = null;
        $this->giftLink = '';
        $this->fetchImageFromLink = true;
        $this->showGiftModal = true;
    }

    /**
     * Close the gift modal
     */
    public function closeGiftModal(): void
    {
        $this->showGiftModal = false;
        $this->selectedEventId = null;
        $this->giftTitle = '';
        $this->giftValue = '';
        $this->giftImage = null;
        $this->giftLink = '';
        $this->fetchImageFromLink = true;
        $this->resetValidation();
    }

    /**
     * Save the gift
     */
    public function saveGift(LinkPreviewService $linkPreviewService): void
    {
        $validated = $this->validate([
            'giftTitle' => ['required', 'string', 'max:255'],
            'giftValue' => ['nullable', 'numeric', 'min:0'],
            'giftImage' => ['nullable', 'image', 'max:2048'],
            'giftLink' => ['nullable', 'url', 'max:255'],
        ]);

        $event = Event::findOrFail($this->selectedEventId);

        $imagePath = null;
        if ($this->giftImage) {
            $imagePath = $this->giftImage->store('gifts', 'public');
        } elseif ($this->fetchImageFromLink && $validated['giftLink']) {
            // Auto-fetch image from link if enabled and no image was uploaded
            $imagePath = $linkPreviewService->fetchImageFromUrl($validated['giftLink']);
        }

        Gift::create([
            'event_id' => $event->id,
            'year' => $event->next_occurrence_year,
            'title' => $validated['giftTitle'],
            'value' => $validated['giftValue'] ?: null,
            'image_path' => $imagePath,
            'link' => $validated['giftLink'] ?: null,
        ]);

        session()->flash('status', 'Gift added successfully.');

        $this->closeGiftModal();
    }

    /**
     * Toggle completion for an event
     */
    public function toggleCompletion(int $eventId): void
    {
        $event = Event::findOrFail($eventId);
        $year = $event->next_occurrence_year;

        if ($event->isCompletedForYear($year)) {
            $event->unmarkCompleteForYear($year);
            session()->flash('status', 'Event marked as incomplete.');
        } else {
            $event->markCompleteForYear($year);
            session()->flash('status', 'Event marked as complete!');
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
