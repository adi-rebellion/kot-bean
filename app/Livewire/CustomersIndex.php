<?php

namespace App\Livewire;

use App\Models\Customer;
use App\Services\CustomerWhatsAppService;
use App\Services\TwilioWhatsAppService;
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

    #[Url]
    public string $tab = 'list';

    #[Url]
    public ?int $selectedCustomerId = null;

    public string $extraNote = '';

    public function selectCustomer(int $customerId): void
    {
        $this->selectedCustomerId = $customerId;
    }

    public function sendMessage(int $customerId, CustomerWhatsAppService $customerWhatsAppService): void
    {
        abort_unless(auth()->user()->hasPermission('customers.manage'), 403);

        $customer = Customer::query()
            ->with(['latestOrder.items', 'restaurant'])
            ->findOrFail($customerId);

        try {
            $customerWhatsAppService->sendLastOrderMessage(
                $customer,
                $this->extraNote !== '' ? $this->extraNote : null,
            );

            session()->flash('success', "WhatsApp message sent to {$customer->name}.");
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render(TwilioWhatsAppService $twilioWhatsAppService, CustomerWhatsAppService $customerWhatsAppService): View
    {
        $query = Customer::query()->orderByDesc('last_order_at');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->tab === 'whatsapp') {
            $query
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->where('total_orders', '>', 0)
                ->with(['latestOrder.items', 'restaurant']);
        }

        $customers = $query->get();

        $selectedCustomer = null;
        $messagePreview = null;

        if ($this->tab === 'whatsapp' && $this->selectedCustomerId) {
            $selectedCustomer = $customers->firstWhere('id', $this->selectedCustomerId)
                ?? Customer::query()
                    ->with(['latestOrder.items', 'restaurant'])
                    ->find($this->selectedCustomerId);

            if ($selectedCustomer?->latestOrder) {
                try {
                    $messagePreview = $customerWhatsAppService->buildLastOrderMessage(
                        $selectedCustomer,
                        extraNote: $this->extraNote !== '' ? $this->extraNote : null,
                    );
                } catch (\Throwable) {
                    $messagePreview = null;
                }
            }
        }

        return view('livewire.customers-index', [
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'messagePreview' => $messagePreview,
            'twilioConfigured' => $twilioWhatsAppService->isConfigured(),
            'canSendMessages' => auth()->user()->hasPermission('customers.manage'),
        ]);
    }
}
