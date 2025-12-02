<?php

namespace App\Livewire;

use App\Models\Event;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Get upcoming events in the next 30 days
     */
    public function getUpcomingEventsProperty(): Collection
    {
        $today = now()->startOfDay();
        $endDate = now()->addDays(90);

        return Event::with(['person', 'eventType', 'gifts', 'completions'])
            ->get()
            ->filter(function ($event) use ($today, $endDate) {
                $nextOccurrence = $event->next_occurrence;
                $isCompleted = $event->isCompletedForYear($nextOccurrence->year);

                return ! $isCompleted && $nextOccurrence->between($today, $endDate);
            })
            ->sortBy('next_occurrence')
            ->values();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
