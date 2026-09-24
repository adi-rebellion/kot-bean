<?php

namespace App\Livewire;

use App\Enums\InventoryTransactionType;
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

    public bool $showAdjustForm = false;

    public ?int $productId = null;

    public string $adjustmentType = 'restock';

    public int $quantity = 1;

    public string $reason = '';

    public function openAdjustForm(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $this->showAdjustForm = true;
    }

    public function saveAdjustment(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $data = $this->validate([
            'productId' => ['required', 'exists:products,id'],
            'adjustmentType' => ['required', 'in:restock,wastage,manual_adjustment'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['productId']);
        $type = InventoryTransactionType::from($data['adjustmentType']);

        try {
            app(InventoryService::class)->adjustStock(
                $product,
                $data['quantity'],
                $type,
                auth()->user(),
                null,
                $data['reason'] ?: null,
            );

            $this->resetAdjustmentForm();
            session()->flash('success', 'Stock updated successfully.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function cancelAdjustment(): void
    {
        $this->resetAdjustmentForm();
    }

    private function resetAdjustmentForm(): void
    {
        $this->showAdjustForm = false;
        $this->productId = null;
        $this->adjustmentType = 'restock';
        $this->quantity = 1;
        $this->reason = '';
    }

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
            'products' => Product::query()
                ->where('track_inventory', true)
                ->orderBy('name')
                ->get(['id', 'name', 'stock']),
            'transactions' => InventoryTransaction::query()
                ->with(['product', 'user'])
                ->latest()
                ->paginate(20),
            'canManage' => auth()->user()->hasPermission('inventory.manage'),
        ]);
    }
}
