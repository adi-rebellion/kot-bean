<?php

namespace App\Livewire;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Reports')]
class ReportsIndex extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public array $salesReport = [];

    /** @var Collection<int, mixed> */
    public $productReport;

    /** @var Collection<int, mixed> */
    public $paymentReport;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->productReport = collect();
        $this->paymentReport = collect();
        $this->loadReport();
    }

    public function updatedDateFrom(): void
    {
        $this->loadReport();
    }

    public function updatedDateTo(): void
    {
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $restaurant = auth()->user()->restaurant;
        $reportService = app(ReportService::class);

        $from = Carbon::parse($this->dateFrom);
        $to = Carbon::parse($this->dateTo);

        $this->salesReport = $reportService->getSalesReport($restaurant, $from, $to);
        $this->productReport = $reportService->getProductReport($restaurant, $from, $to);
        $this->paymentReport = $reportService->getPaymentReport($restaurant, $from, $to);
    }

    public function render(): View
    {
        return view('livewire.reports-index');
    }
}
