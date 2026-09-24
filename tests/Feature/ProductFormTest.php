<?php

namespace Tests\Feature;

use App\Livewire\Menu\ProductForm;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $restaurant = Restaurant::create([
            'name' => 'Test Café',
            'slug' => 'test-cafe',
            'default_tax_rate' => 5,
        ]);

        $managerRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'slug' => 'manager',
            'name' => 'Manager',
            'is_system' => true,
        ]);
        $managerRole->givePermissionTo('menu.manage');
        $managerRole->givePermissionTo('menu.view');

        $this->manager = User::factory()->create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $managerRole->id,
        ]);

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Coffee',
            'slug' => 'coffee',
        ]);

        $this->category = $category;

        $this->product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 100,
            'cost_price' => 30,
            'stock' => 10,
            'min_stock' => 5,
            'has_variants' => false,
            'track_inventory' => true,
            'is_available' => true,
            'sort_order' => 0,
            'preparation_time' => 15,
        ]);
    }

    public function test_manager_can_create_product_with_uploaded_image(): void
    {
        Storage::fake('public');

        $image = $this->uploadedProductImage('latte.jpg');

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class)
            ->set('name', 'Latte')
            ->set('category_id', $this->category->id)
            ->set('price', '120')
            ->set('image', $image)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('name', 'Latte')->first();

        $this->assertNotNull($product);
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
        $this->assertNotNull($product->image_url);
    }

    public function test_manager_can_replace_product_image_with_upload(): void
    {
        Storage::fake('public');

        $oldPath = 'products/'.$this->product->restaurant_id.'/old.jpg';
        Storage::disk('public')->put($oldPath, 'old-image');
        $this->product->update(['image_path' => $oldPath]);

        $image = $this->uploadedProductImage('new.jpg');

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('image', $image)
            ->call('save')
            ->assertHasNoErrors();

        $this->product->refresh();

        $this->assertNotSame($oldPath, $this->product->image_path);
        $this->assertNotNull($this->product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($this->product->image_path);
    }

    public function test_manager_can_remove_product_image(): void
    {
        Storage::fake('public');

        $path = 'products/'.$this->product->restaurant_id.'/photo.jpg';
        Storage::disk('public')->put($path, 'fake');
        $this->product->update(['image_path' => $path]);

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->call('clearImage')
            ->call('save')
            ->assertHasNoErrors();

        $this->product->refresh();

        $this->assertNull($this->product->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_rejects_product_image_larger_than_eight_megabytes(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('huge.jpg', 9000, 'image/jpeg');

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('image', $file)
            ->assertHasErrors(['image']);

        $this->product->refresh();

        $this->assertNull($this->product->image_path);
    }

    public function test_rejects_non_image_product_upload(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        Livewire::actingAs($this->manager)
            ->test(ProductForm::class, ['product' => $this->product])
            ->set('image', $file)
            ->assertHasErrors(['image']);

        $this->product->refresh();

        $this->assertNull($this->product->image_path);
    }

    private function uploadedProductImage(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            file_get_contents(base_path('tests/Fixtures/product.jpg')),
        );
    }
}
