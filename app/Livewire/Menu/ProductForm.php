<?php

namespace App\Livewire\Menu;

use App\Enums\AddonSelectionType;
use App\Enums\AiGenerationStatus;
use App\Models\AiImageGeneration;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAddonGroup;
use App\Models\ProductAddonOption;
use App\Models\ProductVariant;
use App\Services\AiImageService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.kotbean')]
class ProductForm extends Component
{
    use WithFileUploads;

    private const IMAGE_RULES = 'nullable|file|mimes:jpg,jpeg,png,webp|max:8192';

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

    public ?int $editingAddonGroupId = null;

    public string $addonGroupName = '';

    public string $addonGroupSelectionType = 'single';

    public bool $addonGroupIsRequired = false;

    public ?int $addonGroupMaxSelections = null;

    public ?int $editingAddonOptionId = null;

    public ?int $addonOptionGroupId = null;

    public string $addonOptionName = '';

    public string $addonOptionPrice = '0';

    public bool $addonOptionIsAvailable = true;

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

        if (! $this->fieldEnabled('inventory')) {
            $this->track_inventory = false;
        }
    }

    public function fieldEnabled(string $field): bool
    {
        return auth()->user()->restaurant->menuProductFieldEnabled($field);
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
        ];

        if ($this->fieldEnabled('description')) {
            $rules['description'] = 'nullable|string';
        }

        if ($this->fieldEnabled('sku')) {
            $rules['sku'] = 'nullable|string|max:100';
        }

        if ($this->fieldEnabled('cost_price')) {
            $rules['cost_price'] = 'nullable|numeric|min:0';
        }

        if ($this->fieldEnabled('tax_rate')) {
            $rules['tax_rate'] = 'nullable|numeric|min:0|max:100';
        }

        if ($this->fieldEnabled('inventory') && $this->track_inventory) {
            $rules['stock'] = 'integer|min:0';
            $rules['min_stock'] = 'integer|min:0';
        }

        if ($this->fieldEnabled('preparation_time')) {
            $rules['preparation_time'] = 'integer|min:0';
        }

        if ($this->fieldEnabled('variants')) {
            $rules['has_variants'] = 'boolean';
        }

        if ($this->fieldEnabled('inventory')) {
            $rules['track_inventory'] = 'boolean';
        }

        if ($this->fieldEnabled('sort_order')) {
            $rules['sort_order'] = 'integer|min:0';
        }

        if ($this->fieldEnabled('image')) {
            $rules['image'] = self::IMAGE_RULES;
        }

        $data = $this->validate($rules);

        if ($this->fieldEnabled('image')) {
            unset($data['image']);
        }

        $data['category_id'] = $data['category_id'] ?: null;

        if (! $this->fieldEnabled('description')) {
            $data['description'] = $this->product?->description;
        } else {
            $data['description'] = ($data['description'] ?? '') === '' ? null : $data['description'];
        }

        if (! $this->fieldEnabled('sku')) {
            $data['sku'] = $this->product?->sku;
        } else {
            $data['sku'] = ($data['sku'] ?? '') === '' ? null : $data['sku'];
        }

        if (! $this->fieldEnabled('cost_price')) {
            $data['cost_price'] = $this->product?->cost_price ?? 0;
        } else {
            $data['cost_price'] = ($data['cost_price'] ?? '') === '' || ($data['cost_price'] ?? null) === null ? 0 : $data['cost_price'];
        }

        if (! $this->fieldEnabled('tax_rate')) {
            $data['tax_rate'] = $this->product?->tax_rate;
        } else {
            $data['tax_rate'] = ($data['tax_rate'] ?? '') === '' ? null : $data['tax_rate'];
        }

        if (! $this->fieldEnabled('preparation_time')) {
            $data['preparation_time'] = $this->product?->preparation_time ?? 15;
        }

        if (! $this->fieldEnabled('inventory')) {
            $data['track_inventory'] = false;
            $data['stock'] = $this->product?->stock ?? 0;
            $data['min_stock'] = $this->product?->min_stock ?? 5;
        } elseif (! ($data['track_inventory'] ?? $this->track_inventory)) {
            $data['track_inventory'] = false;
            $data['stock'] = $this->product?->stock ?? 0;
            $data['min_stock'] = $this->product?->min_stock ?? 5;
        }

        if (! $this->fieldEnabled('variants')) {
            $data['has_variants'] = $this->product?->has_variants ?? false;
        }

        if (! $this->fieldEnabled('sort_order')) {
            $data['sort_order'] = $this->product?->sort_order ?? 0;
        }

        $data['is_available'] = $data['is_available'] ?? $this->is_available;

        $productService = app(ProductService::class);

        if ($this->product?->exists) {
            $this->product = $productService->update($this->product, $data);
            session()->flash('success', 'Product updated successfully.');
        } else {
            $this->product = $productService->create($data, auth()->user());
            session()->flash('success', 'Product created successfully.');
        }

        if ($this->fieldEnabled('image')) {
            $this->product = $this->persistImage($this->product);
        }
    }

    public function updatedImage(): void
    {
        $this->removeImage = false;
        $this->validateOnly('image', [
            'image' => self::IMAGE_RULES,
        ]);

        if ($this->fieldEnabled('image') && $this->image && $this->product?->exists) {
            $this->product = $this->persistImage($this->product);
            session()->flash('success', 'Product image uploaded.');
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

    public function editAddonGroup(int $groupId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $group = ProductAddonGroup::query()
            ->where('product_id', $this->product?->id)
            ->findOrFail($groupId);

        $this->editingAddonGroupId = $group->id;
        $this->addonGroupName = $group->name;
        $this->addonGroupSelectionType = $group->selection_type->value;
        $this->addonGroupIsRequired = $group->is_required;
        $this->addonGroupMaxSelections = $group->max_selections;
    }

    public function saveAddonGroup(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        if (! $this->product?->exists) {
            session()->flash('error', 'Save the product before adding add-on groups.');

            return;
        }

        $data = $this->validate([
            'addonGroupName' => ['required', 'string', 'max:255'],
            'addonGroupSelectionType' => ['required', 'in:single,multiple'],
            'addonGroupIsRequired' => ['boolean'],
            'addonGroupMaxSelections' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $payload = [
            'name' => $data['addonGroupName'],
            'selection_type' => AddonSelectionType::from($data['addonGroupSelectionType']),
            'is_required' => $data['addonGroupIsRequired'],
            'max_selections' => $data['addonGroupSelectionType'] === 'multiple'
                ? ($data['addonGroupMaxSelections'] ?? null)
                : null,
        ];

        if ($this->editingAddonGroupId) {
            ProductAddonGroup::query()
                ->where('product_id', $this->product->id)
                ->findOrFail($this->editingAddonGroupId)
                ->update($payload);
        } else {
            ProductAddonGroup::create([
                'product_id' => $this->product->id,
                ...$payload,
            ]);
        }

        $this->resetAddonGroupForm();
        session()->flash('success', 'Add-on group saved.');
    }

    public function deleteAddonGroup(int $groupId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        ProductAddonGroup::query()
            ->where('product_id', $this->product?->id)
            ->findOrFail($groupId)
            ->delete();

        session()->flash('success', 'Add-on group deleted.');
    }

    public function cancelAddonGroupForm(): void
    {
        $this->resetAddonGroupForm();
    }

    public function editAddonOption(int $optionId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $option = ProductAddonOption::query()
            ->whereHas('group', fn ($query) => $query->where('product_id', $this->product?->id))
            ->findOrFail($optionId);

        $this->editingAddonOptionId = $option->id;
        $this->addonOptionGroupId = $option->product_addon_group_id;
        $this->addonOptionName = $option->name;
        $this->addonOptionPrice = (string) $option->price;
        $this->addonOptionIsAvailable = $option->is_available;
    }

    public function saveAddonOption(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        if (! $this->product?->exists) {
            session()->flash('error', 'Save the product before adding add-on options.');

            return;
        }

        $data = $this->validate([
            'addonOptionGroupId' => ['required', 'exists:product_addon_groups,id'],
            'addonOptionName' => ['required', 'string', 'max:255'],
            'addonOptionPrice' => ['required', 'numeric', 'min:0'],
            'addonOptionIsAvailable' => ['boolean'],
        ]);

        abort_unless(
            ProductAddonGroup::query()
                ->where('product_id', $this->product->id)
                ->whereKey($data['addonOptionGroupId'])
                ->exists(),
            403,
        );

        $payload = [
            'product_addon_group_id' => $data['addonOptionGroupId'],
            'name' => $data['addonOptionName'],
            'price' => $data['addonOptionPrice'],
            'is_available' => $data['addonOptionIsAvailable'],
        ];

        if ($this->editingAddonOptionId) {
            ProductAddonOption::query()
                ->whereHas('group', fn ($query) => $query->where('product_id', $this->product->id))
                ->findOrFail($this->editingAddonOptionId)
                ->update($payload);
        } else {
            ProductAddonOption::create($payload);
        }

        $this->resetAddonOptionForm();
        session()->flash('success', 'Add-on option saved.');
    }

    public function deleteAddonOption(int $optionId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        ProductAddonOption::query()
            ->whereHas('group', fn ($query) => $query->where('product_id', $this->product?->id))
            ->findOrFail($optionId)
            ->delete();

        session()->flash('success', 'Add-on option deleted.');
    }

    public function cancelAddonOptionForm(): void
    {
        $this->resetAddonOptionForm();
    }

    private function resetAddonGroupForm(): void
    {
        $this->editingAddonGroupId = null;
        $this->addonGroupName = '';
        $this->addonGroupSelectionType = 'single';
        $this->addonGroupIsRequired = false;
        $this->addonGroupMaxSelections = null;
    }

    private function resetAddonOptionForm(): void
    {
        $this->editingAddonOptionId = null;
        $this->addonOptionGroupId = null;
        $this->addonOptionName = '';
        $this->addonOptionPrice = '0';
        $this->addonOptionIsAvailable = true;
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
            'addonGroups' => $this->product?->exists
                ? $this->product->addonGroups()->with('options')->get()
                : collect(),
        ]);
    }
}
