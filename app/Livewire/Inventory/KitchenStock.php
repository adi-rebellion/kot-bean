<?php

namespace App\Livewire\Inventory;

use App\Models\StockDailyRecord;
use App\Models\StockItem;
use App\Models\StockItemVendorPrice;
use App\Models\Vendor;
use App\Services\KitchenStockService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class KitchenStock extends Component
{
    use WithPagination;

    public bool $showItemForm = false;

    public ?int $editingItemId = null;

    public string $itemName = '';

    public string $itemUnit = 'slices';

    public string $itemMinQuantity = '';

    public ?int $activeItemId = null;

    public string $openingQuantity = '';

    public string $restockQuantity = '';

    public string $closingQuantity = '';

    public string $dayNotes = '';

    public bool $showVendorPriceForm = false;

    public ?int $vendorPriceId = null;

    public ?int $vendorId = null;

    public string $vendorUnitPrice = '';

    public string $vendorPriceUnit = '';

    public function mount(): void
    {
        $first = StockItem::query()->orderBy('sort_order')->orderBy('name')->first();

        if ($first) {
            $this->selectItem($first->id);
        }
    }

    public function selectItem(int $itemId): void
    {
        $this->activeItemId = $itemId;
        $item = StockItem::findOrFail($itemId);
        $service = app(KitchenStockService::class);

        $today = StockDailyRecord::query()
            ->where('stock_item_id', $item->id)
            ->whereDate('record_date', today())
            ->first();

        if ($today) {
            $this->openingQuantity = (string) $today->opening_quantity;
            $this->restockQuantity = $today->restock_quantity > 0 ? (string) $today->restock_quantity : '';
            $this->closingQuantity = $today->closing_quantity !== null ? (string) $today->closing_quantity : '';
            $this->dayNotes = $today->notes ?? '';
        } else {
            $suggested = $service->suggestedOpeningQuantity($item);
            $this->openingQuantity = $suggested !== null ? (string) $suggested : '';
            $this->restockQuantity = '';
            $this->closingQuantity = '';
            $this->dayNotes = '';
        }
    }

    public function openItemForm(?int $itemId = null): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        if ($itemId) {
            $item = StockItem::findOrFail($itemId);
            $this->editingItemId = $item->id;
            $this->itemName = $item->name;
            $this->itemUnit = $item->unit;
            $this->itemMinQuantity = $item->min_quantity !== null ? (string) $item->min_quantity : '';
        } else {
            $this->resetItemForm();
        }

        $this->showItemForm = true;
    }

    public function saveItem(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $data = $this->validate([
            'itemName' => ['required', 'string', 'max:255'],
            'itemUnit' => ['required', 'string', 'max:50'],
            'itemMinQuantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $payload = [
            'name' => $data['itemName'],
            'unit' => $data['itemUnit'],
            'min_quantity' => $data['itemMinQuantity'] === '' || $data['itemMinQuantity'] === null
                ? null
                : $data['itemMinQuantity'],
        ];

        if ($this->editingItemId) {
            StockItem::findOrFail($this->editingItemId)->update($payload);
            $itemId = $this->editingItemId;
        } else {
            $item = StockItem::create($payload);
            $itemId = $item->id;
        }

        $this->showItemForm = false;
        $this->resetItemForm();
        $this->selectItem($itemId);
        session()->flash('kitchen_success', 'Ingredient saved.');
    }

    public function deleteItem(int $itemId): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        StockItem::findOrFail($itemId)->delete();
        $this->activeItemId = null;
        session()->flash('kitchen_success', 'Ingredient removed.');
    }

    public function saveOpening(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $this->validate(['openingQuantity' => ['required', 'numeric', 'min:0']]);

        $item = StockItem::findOrFail($this->activeItemId);
        app(KitchenStockService::class)->recordOpening($item, (float) $this->openingQuantity, auth()->user());

        session()->flash('kitchen_success', 'Opening count saved for today.');
    }

    public function saveRestock(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $this->validate(['restockQuantity' => ['required', 'numeric', 'min:0.01']]);

        $item = StockItem::findOrFail($this->activeItemId);
        app(KitchenStockService::class)->recordRestock(
            $item,
            (float) $this->restockQuantity,
            auth()->user(),
            $this->dayNotes ?: null,
        );

        $this->restockQuantity = '';
        $this->selectItem($item->id);
        session()->flash('kitchen_success', 'Restock added to today\'s ledger.');
    }

    public function saveClosing(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $this->validate(['closingQuantity' => ['required', 'numeric', 'min:0']]);

        $item = StockItem::findOrFail($this->activeItemId);
        app(KitchenStockService::class)->recordClosing(
            $item,
            (float) $this->closingQuantity,
            auth()->user(),
            $this->dayNotes ?: null,
        );

        $this->selectItem($item->id);
        session()->flash('kitchen_success', 'End-of-day count saved. Usage updated in the ledger.');
    }

    public function openVendorPriceForm(?int $priceId = null): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        if ($priceId) {
            $price = StockItemVendorPrice::with('vendor')->findOrFail($priceId);
            abort_unless($price->stock_item_id === $this->activeItemId, 403);

            $this->vendorPriceId = $price->id;
            $this->vendorId = $price->vendor_id;
            $this->vendorUnitPrice = (string) $price->unit_price;
            $this->vendorPriceUnit = $price->price_unit ?? '';
        } else {
            $this->vendorPriceId = null;
            $this->vendorId = null;
            $this->vendorUnitPrice = '';
            $this->vendorPriceUnit = '';
        }

        $this->showVendorPriceForm = true;
    }

    public function saveVendorPrice(): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        $data = $this->validate([
            'vendorId' => ['required', 'exists:vendors,id'],
            'vendorUnitPrice' => ['required', 'numeric', 'min:0'],
            'vendorPriceUnit' => ['nullable', 'string', 'max:100'],
        ]);

        abort_unless($this->activeItemId, 403);

        StockItemVendorPrice::updateOrCreate(
            [
                'stock_item_id' => $this->activeItemId,
                'vendor_id' => $data['vendorId'],
            ],
            [
                'unit_price' => $data['vendorUnitPrice'],
                'price_unit' => $data['vendorPriceUnit'] ?: null,
            ],
        );

        $this->showVendorPriceForm = false;
        session()->flash('kitchen_success', 'Supplier price saved.');
    }

    public function deleteVendorPrice(int $priceId): void
    {
        abort_unless(auth()->user()->hasPermission('inventory.manage'), 403);

        StockItemVendorPrice::query()
            ->where('stock_item_id', $this->activeItemId)
            ->findOrFail($priceId)
            ->delete();

        session()->flash('kitchen_success', 'Supplier price removed.');
    }

    public function cancelItemForm(): void
    {
        $this->showItemForm = false;
        $this->resetItemForm();
    }

    public function cancelVendorPriceForm(): void
    {
        $this->showVendorPriceForm = false;
    }

    private function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->itemName = '';
        $this->itemUnit = 'slices';
        $this->itemMinQuantity = '';
    }

    public function render(): View
    {
        $service = app(KitchenStockService::class);
        $restaurant = auth()->user()->restaurant;

        $items = StockItem::query()->orderBy('sort_order')->orderBy('name')->get();
        $activeItem = $this->activeItemId ? StockItem::find($this->activeItemId) : null;

        $ledger = $activeItem
            ? StockDailyRecord::query()
                ->where('stock_item_id', $activeItem->id)
                ->orderByDesc('record_date')
                ->paginate(14, pageName: 'ledgerPage')
            : null;

        $vendorPrices = $activeItem
            ? StockItemVendorPrice::query()
                ->with('vendor')
                ->where('stock_item_id', $activeItem->id)
                ->orderBy('unit_price')
                ->get()
            : collect();

        $bestQuote = $activeItem ? $service->bestVendorQuote($activeItem) : null;

        return view('livewire.inventory.kitchen-stock', [
            'items' => $items,
            'activeItem' => $activeItem,
            'ledger' => $ledger,
            'vendorPrices' => $vendorPrices,
            'bestQuote' => $bestQuote,
            'restockAlerts' => $service->restockAlerts($restaurant),
            'vendors' => Vendor::query()->where('is_active', true)->orderBy('name')->get(),
            'canManage' => auth()->user()->hasPermission('inventory.manage'),
            'kitchenStockService' => $service,
        ]);
    }
}
