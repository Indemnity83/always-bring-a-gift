<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Livewire\Component;

class Show extends Component
{
    public Event $event;

    /**
     * Mount the component
     */
    public function mount(Event $event): void
    {
        $this->event = $event->load(['person', 'eventType', 'gifts', 'completions']);
    }

    /**
     * Toggle completion for the next occurrence year
     */
    public function toggleCompletion(): void
    {
        $year = $this->event->next_occurrence_year;

        if ($this->event->isCompletedForYear($year)) {
            $this->event->unmarkCompleteForYear($year);
            session()->flash('status', 'Event marked as incomplete.');
        } else {
            $this->event->markCompleteForYear($year);
            session()->flash('status', 'Event marked as complete!');
        }

        $this->event->refresh()->load(['completions']);
    }

    /**
     * Delete a gift
     */
    public function deleteGift(int $giftId): void
    {
        $this->event->gifts()->findOrFail($giftId)->delete();

        session()->flash('status', 'Gift deleted successfully.');

        $this->event->refresh()->load(['gifts']);
    }

    public function render()
    {
        $nextOccurrenceYear = $this->event->next_occurrence_year;
        $giftsThisYear = $this->event->gifts()->where('year', $nextOccurrenceYear)->get();
        $isCompleted = $this->event->isCompletedForYear($nextOccurrenceYear);

        return view('livewire.events.show', [
            'nextOccurrenceYear' => $nextOccurrenceYear,
            'giftsThisYear' => $giftsThisYear,
            'isCompleted' => $isCompleted,
        ]);
    }
}
