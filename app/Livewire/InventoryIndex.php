<?php

namespace App\Livewire;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.kotbean')]
#[Title('Inventory')]
class InventoryIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;
        $inventoryService = app(InventoryService::class);

        return view('livewire.inventory-index', [
            'lowStockProducts' => $inventoryService->getLowStockProducts($restaurant),
            'totalProducts' => Product::query()->where('track_inventory', true)->count(),
            'totalStockValue' => Product::query()
                ->where('track_inventory', true)
                ->selectRaw('SUM(stock * cost_price) as value')
                ->value('value') ?? 0,
            'transactions' => InventoryTransaction::query()
                ->with(['product', 'user'])
                ->latest()
                ->paginate(20),
        ]);
    }
}
