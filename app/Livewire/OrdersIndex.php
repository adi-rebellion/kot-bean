<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
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

    public function cancelOrder(int $orderId): void
    {
        abort_unless(auth()->user()->hasPermission('orders.manage'), 403);

        $order = Order::findOrFail($orderId);

        try {
            app(OrderService::class)->cancel($order, auth()->user(), 'Cancelled from orders page');
            session()->flash('success', "Order {$order->order_number} cancelled.");
        } catch (\InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $query = Order::query()
            ->with(['table', 'customer', 'creator', 'kots'])
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
            'canManage' => auth()->user()->hasPermission('orders.manage'),
        ]);
    }
}
