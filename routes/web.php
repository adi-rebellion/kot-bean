<?php

use App\Http\Controllers\PrintController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\CashRegisterIndex;
use App\Livewire\CustomersIndex;
use App\Livewire\DashboardPage;
use App\Livewire\ExpensesIndex;
use App\Livewire\InventoryIndex;
use App\Livewire\KitchenDisplay;
use App\Livewire\Menu\CategoriesIndex;
use App\Livewire\Menu\ProductForm;
use App\Livewire\Menu\ProductsIndex;
use App\Livewire\OrdersIndex;
use App\Livewire\PosPage;
use App\Livewire\PromotionsIndex;
use App\Livewire\ReportsIndex;
use App\Livewire\SettingsPage;
use App\Livewire\StaffIndex;
use App\Livewire\TablesIndex;
use App\Livewire\VendorLedger;
use App\Livewire\VendorsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('/dashboard', DashboardPage::class)
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::livewire('/pos', PosPage::class)
        ->middleware('permission:pos.access')
        ->name('pos');

    Route::livewire('/pos/{order}', PosPage::class)
        ->middleware('permission:pos.access')
        ->name('pos.order');

    Route::livewire('/orders', OrdersIndex::class)
        ->middleware('permission:orders.view')
        ->name('orders.index');

    Route::livewire('/kitchen', KitchenDisplay::class)
        ->middleware('permission:kitchen.access')
        ->name('kitchen');

    Route::livewire('/tables', TablesIndex::class)
        ->middleware('permission:tables.view')
        ->name('tables.index');

    Route::livewire('/menu', ProductsIndex::class)
        ->middleware('permission:menu.view')
        ->name('menu.index');

    Route::livewire('/menu/categories', CategoriesIndex::class)
        ->middleware('permission:menu.view')
        ->name('menu.categories');

    Route::livewire('/menu/create', ProductForm::class)
        ->middleware('permission:menu.manage')
        ->name('menu.create');

    Route::livewire('/menu/{product}/edit', ProductForm::class)
        ->middleware('permission:menu.manage')
        ->name('menu.edit');

    Route::livewire('/inventory', InventoryIndex::class)
        ->middleware('permission:inventory.view')
        ->name('inventory.index');

    Route::livewire('/customers', CustomersIndex::class)
        ->middleware('permission:customers.view')
        ->name('customers.index');

    Route::livewire('/promotions', PromotionsIndex::class)
        ->middleware('permission:promotions.view')
        ->name('promotions.index');

    Route::livewire('/cash-register', CashRegisterIndex::class)
        ->middleware('permission:payments.process')
        ->name('cash-register.index');

    Route::livewire('/reports', ReportsIndex::class)
        ->middleware('permission:reports.view')
        ->name('reports.index');

    Route::get('/reports/download', [ReportExportController::class, 'download'])
        ->middleware('permission:reports.view')
        ->name('reports.download');

    Route::livewire('/expenses', ExpensesIndex::class)
        ->middleware('permission:expenses.view')
        ->name('expenses.index');

    Route::livewire('/vendors', VendorsIndex::class)
        ->middleware('permission:expenses.view')
        ->name('vendors.index');

    Route::livewire('/vendors/ledger', VendorLedger::class)
        ->middleware('permission:expenses.view')
        ->name('vendors.ledger');

    Route::livewire('/staff', StaffIndex::class)
        ->middleware('permission:staff.manage')
        ->name('staff.index');

    Route::livewire('/settings', SettingsPage::class)
        ->middleware('permission:settings.manage')
        ->name('settings');

    Route::get('/orders/{order}/invoice', [PrintController::class, 'invoice'])
        ->middleware('permission:orders.view')
        ->name('orders.invoice');

    Route::get('/kots/{kot}/print', [PrintController::class, 'kot'])
        ->middleware('permission:kitchen.access')
        ->name('kots.print');
});

require __DIR__.'/auth.php';
