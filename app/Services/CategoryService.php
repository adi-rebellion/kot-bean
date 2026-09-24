<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Restaurant;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CategoryService
{
    public function create(array $data, Restaurant $restaurant): Category
    {
        return Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $restaurant->id),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->restaurant_id, $category->id);
        }

        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        if ($category->products()->exists()) {
            throw new InvalidArgumentException('Cannot delete a category that has products. Reassign products first.');
        }

        $category->delete();
    }

    private function uniqueSlug(string $name, int $restaurantId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            Category::query()
                ->where('restaurant_id', $restaurantId)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
