<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function create(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user) {
            $payload = $this->preparePayload($data, $user->restaurant_id);

            $product = Product::create($payload);

            $this->auditLogService->log(
                'product.created',
                $product,
                null,
                $product->toArray(),
                "Product {$product->name} created",
            );

            return $product->fresh();
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $old = $product->toArray();
            $payload = $this->preparePayload($data, $product->restaurant_id, $product);

            $product->update($payload);

            $this->auditLogService->log(
                'product.updated',
                $product,
                $old,
                $product->fresh()->toArray(),
                "Product {$product->name} updated",
            );

            return $product->fresh();
        });
    }

    public function toggleAvailability(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $old = $product->only(['is_available']);

            $product->update(['is_available' => ! $product->is_available]);

            $this->auditLogService->log(
                'product.availability_toggled',
                $product,
                $old,
                $product->only(['is_available']),
                "Product {$product->name} availability toggled",
            );

            return $product->fresh();
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            $old = $product->toArray();

            $product->delete();

            $this->auditLogService->log(
                'product.deleted',
                $product,
                $old,
                null,
                "Product {$product->name} deleted",
            );
        });
    }

    private function preparePayload(array $data, int $restaurantId, ?Product $existing = null): array
    {
        $payload = array_intersect_key($data, array_flip([
            'category_id',
            'name',
            'slug',
            'description',
            'sku',
            'image_path',
            'price',
            'cost_price',
            'tax_rate',
            'stock',
            'min_stock',
            'preparation_time',
            'has_variants',
            'track_inventory',
            'is_available',
            'sort_order',
        ]));

        if (! isset($payload['slug']) && isset($payload['name'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        if (! $existing) {
            $payload['restaurant_id'] = $restaurantId;
        }

        return $payload;
    }
}
