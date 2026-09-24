<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\TableStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoRestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Bean & Brew Café',
            'slug' => 'bean-brew-cafe',
            'address' => '42 Coffee Lane, Koramangala, Bengaluru 560034',
            'phone' => '+91 98765 43210',
            'email' => 'hello@beanbrew.cafe',
            'gstin' => '29AABCU9603R1ZX',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'default_tax_rate' => 5.00,
        ]);

        $roles = $this->createRoles($restaurant);
        $this->createUsers($restaurant, $roles);
        $categories = $this->createCategories($restaurant);
        $products = $this->createProducts($restaurant, $categories);
        $this->createTables($restaurant);
        $this->createCustomers($restaurant);
        $this->createHistoricalOrders($restaurant, $products, $roles['owner']);
        $this->createExpenses($restaurant, $roles['owner']);
    }

    private function createRoles(Restaurant $restaurant): array
    {
        $matrix = [
            'owner' => Permission::pluck('slug')->all(),
            'manager' => Permission::whereNotIn('slug', ['staff.manage'])->pluck('slug')->all(),
            'cashier' => ['dashboard.view', 'pos.access', 'orders.view', 'orders.manage', 'menu.view', 'inventory.view', 'tables.view', 'payments.process', 'customers.view'],
            'waiter' => ['dashboard.view', 'pos.access', 'orders.view', 'orders.manage', 'menu.view', 'inventory.view', 'tables.view', 'tables.manage', 'payments.process', 'customers.view'],
            'kitchen' => ['orders.view', 'kitchen.access'],
        ];

        $roles = [];
        foreach ($matrix as $slug => $permissions) {
            $role = Role::create([
                'restaurant_id' => $restaurant->id,
                'slug' => $slug,
                'name' => ucfirst($slug),
                'is_system' => true,
            ]);

            $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id');
            $role->permissions()->sync($permissionIds);
            $roles[$slug] = $role;
        }

        return $roles;
    }

    private function createUsers(Restaurant $restaurant, array $roles): void
    {
        $users = [
            ['name' => 'Aditya Owner', 'email' => 'owner@beanbrew.cafe', 'role' => 'owner'],
            ['name' => 'Priya Manager', 'email' => 'manager@beanbrew.cafe', 'role' => 'manager'],
            ['name' => 'Ravi Cashier', 'email' => 'cashier@beanbrew.cafe', 'role' => 'cashier'],
            ['name' => 'Sneha Waiter', 'email' => 'waiter@beanbrew.cafe', 'role' => 'waiter'],
            ['name' => 'Kumar Kitchen', 'email' => 'kitchen@beanbrew.cafe', 'role' => 'kitchen'],
        ];

        foreach ($users as $userData) {
            User::create([
                'restaurant_id' => $restaurant->id,
                'role_id' => $roles[$userData['role']]->id,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
        }
    }

    private function createCategories(Restaurant $restaurant): array
    {
        $names = ['Coffee', 'Cold Coffee', 'Tea', 'Snacks', 'Bakery', 'Desserts'];
        $categories = [];

        foreach ($names as $index => $name) {
            $categories[$name] = Category::create([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        return $categories;
    }

    private function createProducts(Restaurant $restaurant, array $categories): array
    {
        $menu = [
            'Coffee' => [
                ['Espresso', 80, 25, 30],
                ['Americano', 100, 30, 25],
                ['Cappuccino', 160, 45, 18],
                ['Latte', 150, 42, 22],
                ['Mocha', 180, 55, 15],
            ],
            'Cold Coffee' => [
                ['Vietnamese Iced Coffee', 140, 48, 7],
                ['Cold Brew', 130, 40, 0],
                ['Iced Latte', 160, 50, 20],
            ],
            'Tea' => [
                ['Masala Tea', 60, 15, 40],
                ['Green Tea', 70, 18, 35],
                ['Chai Latte', 120, 35, 28],
            ],
            'Snacks' => [
                ['Veg Sandwich', 120, 40, 12],
                ['Grilled Cheese', 140, 50, 10],
            ],
            'Bakery' => [
                ['Croissant', 120, 35, 16],
                ['Chocolate Muffin', 90, 28, 20],
            ],
            'Desserts' => [
                ['Brownie', 100, 30, 14],
                ['Cheesecake', 180, 70, 8],
            ],
        ];

        $products = [];
        $sortOrder = 0;

        foreach ($menu as $categoryName => $items) {
            foreach ($items as [$name, $price, $cost, $stock]) {
                $product = Product::create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $categories[$categoryName]->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => "Freshly prepared {$name} at Bean & Brew Café.",
                    'sku' => 'BB-'.strtoupper(Str::random(6)),
                    'price' => $price,
                    'cost_price' => $cost,
                    'stock' => $stock,
                    'min_stock' => 5,
                    'preparation_time' => rand(3, 12),
                    'is_available' => $stock > 0,
                    'sort_order' => $sortOrder++,
                ]);

                InventoryTransaction::create([
                    'restaurant_id' => $restaurant->id,
                    'product_id' => $product->id,
                    'type' => InventoryTransactionType::OpeningStock,
                    'quantity' => $stock,
                    'previous_stock' => 0,
                    'new_stock' => $stock,
                    'reason' => 'Initial stock',
                ]);

                $products[] = $product;
            }
        }

        return $products;
    }

    private function createTables(Restaurant $restaurant): void
    {
        for ($i = 1; $i <= 8; $i++) {
            RestaurantTable::create([
                'restaurant_id' => $restaurant->id,
                'name' => "Table {$i}",
                'capacity' => $i <= 4 ? 4 : 6,
                'status' => TableStatus::Available,
                'sort_order' => $i,
                'zone' => $i <= 4 ? 'Indoor' : 'Outdoor',
            ]);
        }
    }

    private function createCustomers(Restaurant $restaurant): void
    {
        $customers = [
            ['Ananya Sharma', '9876543210', 'ananya@email.com'],
            ['Rahul Verma', '9876543211', 'rahul@email.com'],
            ['Meera Patel', '9876543212', null],
        ];

        foreach ($customers as [$name, $phone, $email]) {
            Customer::create([
                'restaurant_id' => $restaurant->id,
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
            ]);
        }
    }

    private function createHistoricalOrders(Restaurant $restaurant, array $products, Role $ownerRole): void
    {
        $owner = User::where('restaurant_id', $restaurant->id)->where('role_id', $ownerRole->id)->first();
        $tables = RestaurantTable::where('restaurant_id', $restaurant->id)->get();

        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $ordersCount = rand(15, 35);

            for ($i = 0; $i < $ordersCount; $i++) {
                $orderProducts = collect($products)->random(rand(1, 4));
                $subtotal = 0;
                $taxAmount = 0;

                $order = Order::create([
                    'restaurant_id' => $restaurant->id,
                    'order_number' => 'ORD-'.$date->format('ymd').'-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'restaurant_table_id' => rand(0, 1) ? $tables->random()->id : null,
                    'created_by' => $owner->id,
                    'type' => OrderType::DineIn,
                    'status' => OrderStatus::Completed,
                    'confirmed_at' => $date->copy()->setHour(rand(8, 21))->setMinute(rand(0, 59)),
                    'completed_at' => $date->copy()->setHour(rand(8, 22))->setMinute(rand(0, 59)),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                foreach ($orderProducts as $product) {
                    $qty = rand(1, 3);
                    $lineSubtotal = $product->price * $qty;
                    $lineTax = round($lineSubtotal * 0.05, 2);
                    $lineTotal = $lineSubtotal + $lineTax;
                    $subtotal += $lineSubtotal;
                    $taxAmount += $lineTax;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $product->price,
                        'tax_rate' => 5,
                        'tax_amount' => $lineTax,
                        'total' => $lineTotal,
                        'status' => 'served',
                    ]);
                }

                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $subtotal + $taxAmount,
                ]);

                Payment::create([
                    'restaurant_id' => $restaurant->id,
                    'order_id' => $order->id,
                    'payment_number' => 'PAY-'.$order->order_number,
                    'method' => collect(PaymentMethod::cases())->random(),
                    'amount' => $order->total,
                    'received_by' => $owner->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }
    }

    private function createExpenses(Restaurant $restaurant, Role $ownerRole): void
    {
        $owner = User::where('restaurant_id', $restaurant->id)->where('role_id', $ownerRole->id)->first();

        $expenses = [
            ['Rent', 'rent', 25000],
            ['Electricity', 'utilities', 4500],
            ['Coffee Beans', 'raw_materials', 12000],
            ['Staff Salaries', 'staff', 45000],
            ['Maintenance', 'maintenance', 2500],
        ];

        foreach ($expenses as [$title, $category, $amount]) {
            Expense::create([
                'restaurant_id' => $restaurant->id,
                'title' => $title,
                'category' => $category,
                'amount' => $amount,
                'expense_date' => now()->startOfMonth(),
                'created_by' => $owner->id,
            ]);
        }
    }
}
