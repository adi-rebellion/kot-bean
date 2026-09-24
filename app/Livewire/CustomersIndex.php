<?php

namespace App\Livewire;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Customers')]
class CustomersIndex extends Component
{
    #[Url]
    public string $search = '';

    public function render(): View
    {
        $query = Customer::query()->orderByDesc('last_order_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.customers-index', [
            'customers' => $query->get(),
        ]);
    }
}
