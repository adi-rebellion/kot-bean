<?php

namespace App\Livewire;

use App\Services\InventoryService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Dashboard')]
class DashboardPage extends Component
{
    public bool $ready = false;

    public array $metrics = [];

    /** @var Collection<int, mixed> */
    public $bestSellers;

    /** @var Collection<int, mixed> */
    public $lowStockProducts;

    /** @var Collection<int, mixed> */
    public $paymentBreakdown;

    /** @var Collection<int, mixed> */
    public $recentOrders;

    /** @var Collection<int, mixed> */
    public $topCategories;

    public array $orderStatusCounts = [];

    public array $chartData = [];

    public string $chartPeriod = 'today';

    public function mount(): void
    {
        $this->bestSellers = collect();
        $this->lowStockProducts = collect();
        $this->paymentBreakdown = collect();
        $this->recentOrders = collect();
        $this->topCategories = collect();

        $this->loadDashboard();
    }

    public function loadDashboard(): void
    {
        $restaurant = auth()->user()?->restaurant;

        if (! $restaurant) {
            return;
        }
        $reportService = app(ReportService::class);
        $today = now()->startOfDay();
        $endOfToday = now()->endOfDay();

        $this->metrics = $reportService->getDashboardMetrics($restaurant);
        $this->bestSellers = $reportService->getBestSellers($restaurant, 8, $today, $endOfToday);
        $this->lowStockProducts = app(InventoryService::class)->getLowStockProducts($restaurant)->take(8);
        $this->paymentBreakdown = $reportService->getPaymentReport($restaurant, $today, $endOfToday);
        $this->recentOrders = $reportService->getRecentOrders($restaurant, $today, 10);
        $this->topCategories = $reportService->getTopCategoriesToday($restaurant, 5);
        $this->orderStatusCounts = $reportService->getActiveOrderStatusCounts($restaurant);
        $this->chartData = $reportService->getSalesChartData($restaurant, $this->chartPeriod);
        $this->ready = true;
    }

    public function updatedChartPeriod(): void
    {
        if (! $this->ready) {
            return;
        }

        $this->chartData = app(ReportService::class)->getSalesChartData(
            auth()->user()->restaurant,
            $this->chartPeriod,
        );
    }

    public function render(): View
    {
        return view('livewire.dashboard-page');
    }
}
