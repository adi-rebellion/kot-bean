<?php

namespace App\Livewire\Menu;

use App\Enums\AiGenerationStatus;
use App\Models\AiImageGeneration;
use App\Models\Category;
use App\Models\Product;
use App\Services\AiImageService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.kotbean')]
class ProductForm extends Component
{
    public ?Product $product = null;

    public string $name = '';

    public ?int $category_id = null;

    public string $description = '';

    public string $sku = '';

    public string $price = '';

    public string $cost_price = '';

    public string $tax_rate = '';

    public int $stock = 0;

    public int $min_stock = 5;

    public int $preparation_time = 15;

    public bool $has_variants = false;

    public bool $track_inventory = true;

    public bool $is_available = true;

    public int $sort_order = 0;

    public ?int $generationId = null;

    public bool $generationPending = false;

    public string $generationMessage = '';

    public function mount(?Product $product = null): void
    {
        $this->product = $product;

        if ($product?->exists) {
            $this->fill([
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description ?? '',
                'sku' => $product->sku ?? '',
                'price' => (string) $product->price,
                'cost_price' => (string) ($product->cost_price ?? ''),
                'tax_rate' => (string) ($product->tax_rate ?? ''),
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'preparation_time' => $product->preparation_time ?? 15,
                'has_variants' => $product->has_variants,
                'track_inventory' => $product->track_inventory,
                'is_available' => $product->is_available,
                'sort_order' => $product->sort_order,
            ]);
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'sku' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'stock' => 'integer|min:0',
            'min_stock' => 'integer|min:0',
            'preparation_time' => 'integer|min:0',
            'has_variants' => 'boolean',
            'track_inventory' => 'boolean',
            'is_available' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $productService = app(ProductService::class);

        if ($this->product?->exists) {
            $this->product = $productService->update($this->product, $data);
            session()->flash('success', 'Product updated successfully.');
        } else {
            $this->product = $productService->create($data, auth()->user());
            session()->flash('success', 'Product created successfully.');
        }
    }

    public function requestAiImage(): void
    {
        if (! $this->ensureProductSaved()) {
            return;
        }

        $this->generationMessage = 'Generating your product image...';
        $this->generationPending = true;

        try {
            $generation = app(AiImageService::class)->requestGeneration($this->product, auth()->user());
            $this->generationId = $generation->id;
            $this->checkGenerationStatus();
        } catch (\Throwable $e) {
            $this->generationPending = false;
            $this->generationMessage = $e->getMessage();
        }
    }

    private function ensureProductSaved(): bool
    {
        if ($this->product?->exists) {
            return true;
        }

        if ($this->name === '' || $this->price === '') {
            $this->generationMessage = 'Enter at least a product name and price before generating an image.';

            return false;
        }

        $this->save();
        $this->generationMessage = '';

        return (bool) $this->product?->exists;
    }

    public function checkGenerationStatus(): void
    {
        if (! $this->generationId) {
            return;
        }

        $generation = AiImageGeneration::find($this->generationId);

        if (! $generation) {
            $this->generationPending = false;

            return;
        }

        if (in_array($generation->status, [AiGenerationStatus::Queued, AiGenerationStatus::Processing], true)) {
            $this->generationMessage = $generation->status->label().'...';

            return;
        }

        $this->generationPending = false;

        if ($generation->status === AiGenerationStatus::Completed) {
            app(AiImageService::class)->applyGeneratedImage($generation);
            $this->product = $this->product->fresh();
            $this->generationMessage = 'Image generated and applied!';
            $this->generationId = null;
        } elseif ($generation->status === AiGenerationStatus::Failed) {
            $this->generationMessage = $generation->error_message ?? 'Image generation failed.';
            $this->generationId = null;
        }
    }

    public function render(): View
    {
        return view('livewire.menu.product-form', [
            'categories' => Category::query()->orderBy('sort_order')->get(),
        ]);
    }
}
