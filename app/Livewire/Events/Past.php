<?php

namespace App\Livewire\Events;

use App\Models\Event;
use Illuminate\Support\Collection;
use Livewire\Component;

class Past extends Component
{
    /**
     * Get past/completed events
     */
    public function getPastEventsProperty(): Collection
    {
        return Event::with(['person', 'eventType', 'completions', 'gifts'])
            ->get()
            ->filter(function ($event) {
                // One-time events in the past
                if ($event->recurrence === 'none' && $event->date->isPast()) {
                    return true;
                }

                // Events with any completions
                return $event->completions->isNotEmpty();
            })
            ->sortByDesc(function ($event) {
                // Sort by most recent completion or event date
                if ($event->completions->isNotEmpty()) {
                    return $event->completions->max('completed_at');
                }

                return $event->date;
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.events.past');
    }
}
