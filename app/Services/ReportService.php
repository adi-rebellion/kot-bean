<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function getDashboardMetrics(Restaurant $restaurant, ?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $completedStatuses = [OrderStatus::Paid, OrderStatus::Completed];

        $ordersQuery = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$date, $endOfDay]);

        $todayOrders = (clone $ordersQuery)->count();

        $todaySales = (float) (clone $ordersQuery)
            ->whereIn('status', $completedStatuses)
            ->sum('total');

        $itemsSold = (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($restaurant, $date, $endOfDay, $completedStatuses) {
                $query->where('restaurant_id', $restaurant->id)
                    ->whereBetween('created_at', [$date, $endOfDay])
                    ->whereIn('status', $completedStatuses);
            })
            ->sum('quantity');

        $completedCount = (clone $ordersQuery)
            ->whereIn('status', $completedStatuses)
            ->count();

        $avgOrderValue = $completedCount > 0 ? round($todaySales / $completedCount, 2) : 0.0;

        $pendingOrders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled])
            ->count();

        $lowStockCount = $this->inventoryService
            ->getLowStockProducts($restaurant)
            ->count();

        $completedOrders = (clone $ordersQuery)
            ->whereIn('status', $completedStatuses);

        $todayTax = (float) (clone $completedOrders)->sum('tax_amount');
        $todayDiscounts = (float) (clone $completedOrders)->sum('discount_amount');
        $todaySubtotal = (float) (clone $completedOrders)->sum('subtotal');
        $completedOrderCount = (clone $completedOrders)->count();

        $yesterday = $date->copy()->subDay();
        $yesterdaySales = (float) Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', $completedStatuses)
            ->whereBetween('created_at', [$yesterday, $yesterday->copy()->endOfDay()])
            ->sum('total');

        $salesChange = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100.0 : 0.0);

        $outOfStockCount = Product::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('track_inventory', true)
            ->where('stock', '<=', 0)
            ->count();

        $todayExpenses = (float) \App\Models\Expense::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('expense_date', $date)
            ->sum('amount');

        return [
            'today_sales' => round($todaySales, 2),
            'today_orders' => $todayOrders,
            'completed_orders' => $completedOrderCount,
            'items_sold' => $itemsSold,
            'avg_order_value' => $avgOrderValue,
            'pending_orders' => $pendingOrders,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'today_tax' => round($todayTax, 2),
            'today_discounts' => round($todayDiscounts, 2),
            'today_subtotal' => round($todaySubtotal, 2),
            'today_expenses' => round($todayExpenses, 2),
            'gross_profit' => round($todaySales - $todayExpenses, 2),
            'yesterday_sales' => round($yesterdaySales, 2),
            'sales_change_percent' => $salesChange,
        ];
    }

    public function getSalesChartData(Restaurant $restaurant, string $period): array
    {
        $completedStatuses = [OrderStatus::Paid, OrderStatus::Completed];

        return match ($period) {
            'today' => $this->hourlySalesChart($restaurant, $completedStatuses),
            'yesterday' => $this->hourlySalesChart($restaurant, $completedStatuses, now()->subDay()),
            '7d' => $this->dailySalesSeries($restaurant, 7, $completedStatuses),
            '30d' => $this->dailySalesSeries($restaurant, 30, $completedStatuses),
            default => $this->hourlySalesChart($restaurant, $completedStatuses),
        };
    }

    public function getRecentOrders(Restaurant $restaurant, ?Carbon $date = null, int $limit = 8): Collection
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return Order::query()
            ->with(['table', 'items'])
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$date, $endOfDay])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getActiveOrderStatusCounts(Restaurant $restaurant): array
    {
        $counts = Order::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('restaurant_id', $restaurant->id)
            ->whereNotIn('status', [OrderStatus::Completed, OrderStatus::Cancelled])
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'draft' => (int) ($counts[OrderStatus::Draft->value] ?? 0),
            'preparing' => (int) ($counts[OrderStatus::Preparing->value] ?? 0)
                + (int) ($counts[OrderStatus::KotCreated->value] ?? 0),
            'ready' => (int) ($counts[OrderStatus::Ready->value] ?? 0),
            'payment_pending' => (int) ($counts[OrderStatus::PaymentPending->value] ?? 0)
                + (int) ($counts[OrderStatus::Served->value] ?? 0),
        ];
    }

    public function getTopCategoriesToday(Restaurant $restaurant, int $limit = 5): Collection
    {
        $date = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        return OrderItem::query()
            ->select([
                'products.category_id',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as units_sold'),
                DB::raw('SUM(order_items.total) as revenue'),
            ])
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', function ($query) use ($restaurant, $date, $endOfDay) {
                $query->where('restaurant_id', $restaurant->id)
                    ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
                    ->whereBetween('created_at', [$date, $endOfDay]);
            })
            ->groupBy('products.category_id', 'categories.name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    public function getBestSellers(
        Restaurant $restaurant,
        int $limit = 10,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): Collection {
        $from = $from?->copy()->startOfDay() ?? now()->startOfDay();
        $to = $to?->copy()->endOfDay() ?? now()->endOfDay();

        $items = OrderItem::query()
            ->select([
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total) as total_revenue'),
            ])
            ->whereHas('order', function ($query) use ($restaurant, $from, $to) {
                $query->where('restaurant_id', $restaurant->id)
                    ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        $products = Product::query()
            ->whereIn('id', $items->pluck('product_id'))
            ->get()
            ->keyBy('id');

        return $items->map(function ($item) use ($products) {
            $item->product = $products->get($item->product_id);

            return $item;
        });
    }

    public function getSalesReport(Restaurant $restaurant, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $orders = Order::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'order_count' => $orders->count(),
            'gross_sales' => round((float) $orders->sum('subtotal'), 2),
            'tax_collected' => round((float) $orders->sum('tax_amount'), 2),
            'discounts' => round((float) $orders->sum('discount_amount'), 2),
            'net_sales' => round((float) $orders->sum('total'), 2),
        ];
    }

    public function getProductReport(Restaurant $restaurant, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return OrderItem::query()
            ->select([
                'product_id',
                'product_name',
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('AVG(unit_price) as avg_unit_price'),
            ])
            ->whereHas('order', function ($query) use ($restaurant, $from, $to) {
                $query->where('restaurant_id', $restaurant->id)
                    ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('revenue')
            ->get();
    }

    public function getPaymentReport(Restaurant $restaurant, Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        return Payment::query()
            ->select([
                'method',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(amount) as total_amount'),
            ])
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function getHourlySales(Restaurant $restaurant, ?Carbon $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        $rows = Order::query()
            ->select([
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_sales'),
            ])
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Completed])
            ->whereBetween('created_at', [$date, $endOfDay])
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $hours = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $row = $rows->get($hour);
            $hours[] = [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'order_count' => (int) ($row->order_count ?? 0),
                'total_sales' => round((float) ($row->total_sales ?? 0), 2),
            ];
        }

        return $hours;
    }

    private function hourlySalesChart(Restaurant $restaurant, array $statuses, ?Carbon $date = null): array
    {
        $hours = $this->getHourlySales($restaurant, $date);
        $activeHours = collect($hours)->filter(fn (array $h): bool => $h['order_count'] > 0 || $h['total_sales'] > 0);

        if ($activeHours->isEmpty()) {
            $hours = collect($hours)->filter(fn (array $h): bool => $h['hour'] >= 8 && $h['hour'] <= 22)->values()->all();
        } else {
            $minHour = max(0, $activeHours->min('hour') - 1);
            $maxHour = min(23, $activeHours->max('hour') + 1);
            $hours = collect($hours)->filter(fn (array $h): bool => $h['hour'] >= $minHour && $h['hour'] <= $maxHour)->values()->all();
        }

        return [
            'labels' => array_column($hours, 'label'),
            'sales' => array_column($hours, 'total_sales'),
            'orders' => array_column($hours, 'order_count'),
            'type' => 'hourly',
        ];
    }

    private function dailySalesSeries(Restaurant $restaurant, int $days, array $statuses): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_sales'),
            ])
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', $statuses)
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $sales = [];
        $orders = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $row = $rows->get($key);

            $labels[] = $day->format('M j');
            $sales[] = round((float) ($row->total_sales ?? 0), 2);
            $orders[] = (int) ($row->order_count ?? 0);
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'orders' => $orders,
            'type' => 'daily',
        ];
    }

    private function monthlySalesSeries(Restaurant $restaurant, int $months, array $statuses): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $rows = Order::query()
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total) as total_sales'),
            ])
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('status', $statuses)
            ->where('created_at', '>=', $start)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels = [];
        $sales = [];
        $orders = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $row = $rows->get($key);

            $labels[] = $month->format('M Y');
            $sales[] = round((float) ($row->total_sales ?? 0), 2);
            $orders[] = (int) ($row->order_count ?? 0);
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'orders' => $orders,
            'type' => 'monthly',
        ];
    }
}
