<?php

namespace App\Livewire;

use App\Enums\KotStatus;
use App\Models\Kot;
use App\Services\KotService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kitchen')]
#[Title('Kitchen Display')]
class KitchenDisplay extends Component
{
    /** @var Collection<int, Kot> */
    public Collection $kots;

    public function mount(): void
    {
        $this->kots = collect();
        $this->refreshKots();
    }

    public function refreshKots(): void
    {
        $this->kots = app(KotService::class)
            ->getActiveKots(auth()->user()->restaurant)
            ->load(['items', 'table', 'order']);
    }

    public function advanceStatus(int $kotId): void
    {
        $kot = Kot::findOrFail($kotId);

        $nextStatus = match ($kot->status) {
            KotStatus::Pending, KotStatus::Accepted => KotStatus::Preparing,
            KotStatus::Preparing => KotStatus::Ready,
            KotStatus::Ready => KotStatus::Served,
            default => null,
        };

        if ($nextStatus === null) {
            return;
        }

        app(KotService::class)->updateStatus($kot, $nextStatus);
        $this->refreshKots();
    }

    public function render(): View
    {
        $newTickets = $this->kots->where('status', KotStatus::Pending);
        $cookingTickets = $this->kots->whereIn('status', [KotStatus::Accepted, KotStatus::Preparing]);
        $readyTickets = $this->kots->where('status', KotStatus::Ready);

        return view('livewire.kitchen-display', [
            'newTickets' => $newTickets,
            'cookingTickets' => $cookingTickets,
            'readyTickets' => $readyTickets,
        ]);
    }
}
