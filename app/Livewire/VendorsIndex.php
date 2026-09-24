<?php

namespace App\Livewire;

use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Vendors')]
class VendorsIndex extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $address = '';

    public string $notes = '';

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $vendorId): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $vendor = Vendor::findOrFail($vendorId);

        $this->editingId = $vendor->id;
        $this->name = $vendor->name;
        $this->phone = $vendor->phone ?? '';
        $this->email = $vendor->email ?? '';
        $this->address = $vendor->address ?? '';
        $this->notes = $vendor->notes ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $service = app(VendorService::class);
        $restaurant = auth()->user()->restaurant;

        if ($this->editingId) {
            $vendor = Vendor::findOrFail($this->editingId);
            $service->update($vendor, $data);
        } else {
            $service->create($data, $restaurant);
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('success', 'Vendor saved successfully.');
    }

    public function delete(int $vendorId): void
    {
        abort_unless(auth()->user()->hasPermission('expenses.manage'), 403);

        $vendor = Vendor::findOrFail($vendorId);
        app(VendorService::class)->delete($vendor);

        session()->flash('success', 'Vendor deleted.');
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->notes = '';
    }

    public function render(): View
    {
        $restaurant = auth()->user()->restaurant;

        return view('livewire.vendors-index', [
            'vendors' => Vendor::query()
                ->withCount('expenses')
                ->where('restaurant_id', $restaurant->id)
                ->orderBy('name')
                ->get(),
            'summary' => app(VendorService::class)->summarizeLedger($restaurant),
        ]);
    }
}
