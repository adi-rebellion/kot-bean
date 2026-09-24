<?php

namespace App\Livewire\Menu;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Products')]
class ProductsIndex extends Component
{
    #[Url]
    public ?int $categoryFilter = null;

    #[Url]
    public string $search = '';

    public function render(): View
    {
        $query = Product::query()
            ->with('category')
            ->orderBy('sort_order');

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('sku', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.menu.products-index', [
            'products' => $query->get(),
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }
}
