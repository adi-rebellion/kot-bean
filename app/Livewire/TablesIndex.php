<?php

namespace App\Livewire;

use App\Enums\TableStatus;
use App\Models\RestaurantTable;
use App\Services\TableService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Tables')]
class TablesIndex extends Component
{
    public function mount(): void
    {
        if (! auth()->user()?->restaurant?->usesTables()) {
            session()->flash('error', 'Table management is disabled for this restaurant. Enable it in Settings if needed.');

            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function updateStatus(int $tableId, string $status): void
    {
        $table = RestaurantTable::findOrFail($tableId);
        app(TableService::class)->updateStatus($table, TableStatus::from($status));
    }

    public function render(): View
    {
        $tables = RestaurantTable::query()
            ->orderBy('zone')
            ->orderBy('sort_order')
            ->get()
            ->groupBy(fn ($table) => $table->zone ?: 'Main');

        return view('livewire.tables-index', [
            'tableGroups' => $tables,
            'statuses' => TableStatus::cases(),
        ]);
    }
}
