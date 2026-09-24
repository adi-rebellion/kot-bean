<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.kotbean')]
#[Title('Orders')]
class OrdersIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function mount(): void
    {
        $today = now()->toDateString();

        if ($this->dateFrom === '') {
            $this->dateFrom = $today;
        }

        if ($this->dateTo === '') {
            $this->dateTo = $today;
        }
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Order::query()
            ->with(['table', 'customer', 'creator'])
            ->latest();

        if ($this->statusFilter !== '') {
            $query->where('status', OrderStatus::from($this->statusFilter));
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return view('livewire.orders-index', [
            'orders' => $query->paginate(15),
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
