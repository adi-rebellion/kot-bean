<?php

namespace App\Livewire\Menu;

use App\Enums\AiGenerationStatus;
use App\Models\AiImageGeneration;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AiImageService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.kotbean')]
class ProductForm extends Component
{
    use WithFileUploads;

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

    public ?int $editingVariantId = null;

    public string $variantName = '';

    public string $variantPrice = '';

    public string $variantSku = '';

    public int $variantStock = 0;

    public bool $variantIsDefault = false;

    public bool $variantIsAvailable = true;

    public $image = null;

    public bool $removeImage = false;

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
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
                'preparation_time' => (int) ($product->preparation_time ?? 15),
                'has_variants' => (bool) $product->has_variants,
                'track_inventory' => (bool) $product->track_inventory,
                'is_available' => (bool) $product->is_available,
                'sort_order' => (int) $product->sort_order,
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
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        unset($data['image']);

        $data['category_id'] = $data['category_id'] ?: null;
        $data['cost_price'] = $data['cost_price'] === '' || $data['cost_price'] === null ? 0 : $data['cost_price'];
        $data['tax_rate'] = $data['tax_rate'] === '' ? null : $data['tax_rate'];
        $data['description'] = $data['description'] === '' ? null : $data['description'];
        $data['sku'] = $data['sku'] === '' ? null : $data['sku'];

        $productService = app(ProductService::class);

        if ($this->product?->exists) {
            $this->product = $productService->update($this->product, $data);
            session()->flash('success', 'Product updated successfully.');
        } else {
            $this->product = $productService->create($data, auth()->user());
            session()->flash('success', 'Product created successfully.');
        }

        $this->product = $this->persistImage($this->product);
    }

    public function updatedImage(): void
    {
        $this->removeImage = false;

        try {
            $this->validateOnly('image', [
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);
        } catch (ValidationException $exception) {
            $this->image = null;

            throw $exception;
        }
    }

    public function clearImage(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $this->image = null;
        $this->removeImage = true;
    }

    public function editVariant(int $variantId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $variant = ProductVariant::query()
            ->where('product_id', $this->product?->id)
            ->findOrFail($variantId);

        $this->editingVariantId = $variant->id;
        $this->variantName = $variant->name;
        $this->variantPrice = (string) $variant->price;
        $this->variantSku = $variant->sku ?? '';
        $this->variantStock = $variant->stock;
        $this->variantIsDefault = $variant->is_default;
        $this->variantIsAvailable = $variant->is_available;
    }

    public function saveVariant(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        if (! $this->product?->exists) {
            session()->flash('error', 'Save the product before adding variants.');

            return;
        }

        $data = $this->validate([
            'variantName' => ['required', 'string', 'max:255'],
            'variantPrice' => ['required', 'numeric', 'min:0'],
            'variantSku' => ['nullable', 'string', 'max:100'],
            'variantStock' => ['integer', 'min:0'],
            'variantIsDefault' => ['boolean'],
            'variantIsAvailable' => ['boolean'],
        ]);

        if ($data['variantIsDefault']) {
            ProductVariant::query()
                ->where('product_id', $this->product->id)
                ->when($this->editingVariantId, fn ($q) => $q->where('id', '!=', $this->editingVariantId))
                ->update(['is_default' => false]);
        }

        $payload = [
            'name' => $data['variantName'],
            'price' => $data['variantPrice'],
            'sku' => $data['variantSku'] ?: null,
            'stock' => $data['variantStock'],
            'is_default' => $data['variantIsDefault'],
            'is_available' => $data['variantIsAvailable'],
        ];

        if ($this->editingVariantId) {
            ProductVariant::query()
                ->where('product_id', $this->product->id)
                ->findOrFail($this->editingVariantId)
                ->update($payload);
        } else {
            ProductVariant::create([
                'product_id' => $this->product->id,
                ...$payload,
            ]);
        }

        $this->product->update(['has_variants' => true]);
        $this->resetVariantForm();
        session()->flash('success', 'Variant saved.');
    }

    public function deleteVariant(int $variantId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        ProductVariant::query()
            ->where('product_id', $this->product?->id)
            ->findOrFail($variantId)
            ->delete();

        if ($this->product && ! $this->product->variants()->exists()) {
            $this->product->update(['has_variants' => false]);
        }

        session()->flash('success', 'Variant deleted.');
    }

    public function cancelVariantForm(): void
    {
        $this->resetVariantForm();
    }

    private function resetVariantForm(): void
    {
        $this->editingVariantId = null;
        $this->variantName = '';
        $this->variantPrice = '';
        $this->variantSku = '';
        $this->variantStock = 0;
        $this->variantIsDefault = false;
        $this->variantIsAvailable = true;
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
            $this->image = null;
            $this->removeImage = false;
            $this->generationMessage = 'Image generated and applied!';
            $this->generationId = null;
        } elseif ($generation->status === AiGenerationStatus::Failed) {
            $this->generationMessage = $generation->error_message ?? 'Image generation failed.';
            $this->generationId = null;
        }
    }

    private function persistImage(Product $product): Product
    {
        $path = $product->image_path;

        if ($this->removeImage && $path) {
            Storage::disk('public')->delete($path);
            $path = null;
        }

        if ($this->image) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            $path = $this->image->store('products/'.$product->restaurant_id, 'public');
        }

        if ($path !== $product->image_path) {
            $product = app(ProductService::class)->update($product, ['image_path' => $path]);
        }

        $this->image = null;
        $this->removeImage = false;

        return $product;
    }

    public function render(): View
    {
        return view('livewire.menu.product-form', [
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'variants' => $this->product?->exists
                ? $this->product->variants()->orderBy('sort_order')->orderBy('name')->get()
                : collect(),
        ]);
    }
}
