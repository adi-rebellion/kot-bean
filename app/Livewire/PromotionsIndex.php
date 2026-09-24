<?php

namespace App\Livewire;

use App\Enums\PromotionRule;
use App\Enums\PromotionType;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Discounts & Promotions')]
class PromotionsIndex extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $type = 'percentage';

    public string $value = '';

    public bool $autoApply = false;

    public ?string $expiresAt = null;

    public ?string $maxUses = null;

    public string $rule = 'none';

    public ?string $minOrderAmount = null;

    public bool $isActive = true;

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermission('promotions.manage'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $promotionId): void
    {
        abort_unless(auth()->user()->hasPermission('promotions.manage'), 403);

        $promotion = Promotion::findOrFail($promotionId);

        $this->editingId = $promotion->id;
        $this->name = $promotion->name;
        $this->code = $promotion->code ?? '';
        $this->type = $promotion->type->value;
        $this->value = (string) $promotion->value;
        $this->autoApply = $promotion->auto_apply;
        $this->expiresAt = $promotion->expires_at?->format('Y-m-d\TH:i');
        $this->maxUses = $promotion->max_uses !== null ? (string) $promotion->max_uses : null;
        $this->rule = $promotion->rule->value;
        $this->minOrderAmount = $promotion->min_order_amount !== null ? (string) $promotion->min_order_amount : null;
        $this->isActive = $promotion->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('promotions.manage'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'autoApply' => ['boolean'],
            'expiresAt' => ['nullable', 'date'],
            'maxUses' => ['nullable', 'integer', 'min:1'],
            'rule' => ['required', 'in:none,first_customer_daily,first_order_ever'],
            'minOrderAmount' => ['nullable', 'numeric', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        if ($data['type'] === PromotionType::Percentage->value && (float) $data['value'] > 100) {
            $this->addError('value', 'Percentage cannot exceed 100.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'value' => $data['value'],
            'auto_apply' => $data['autoApply'],
            'expires_at' => $data['expiresAt'],
            'max_uses' => $data['maxUses'],
            'rule' => $data['rule'],
            'min_order_amount' => $data['minOrderAmount'],
            'is_active' => $data['isActive'],
        ];

        $service = app(PromotionService::class);
        $restaurant = auth()->user()->restaurant;

        if ($this->editingId) {
            $promotion = Promotion::findOrFail($this->editingId);
            $service->update($promotion, $payload);
        } else {
            $service->create($payload, $restaurant);
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('success', 'Promotion saved successfully.');
    }

    public function delete(int $promotionId): void
    {
        abort_unless(auth()->user()->hasPermission('promotions.manage'), 403);

        $promotion = Promotion::findOrFail($promotionId);

        try {
            app(PromotionService::class)->delete($promotion);
            session()->flash('success', 'Promotion deleted.');
        } catch (\InvalidArgumentException $exception) {
            session()->flash('error', $exception->getMessage());
        }
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
        $this->code = '';
        $this->type = PromotionType::Percentage->value;
        $this->value = '';
        $this->autoApply = false;
        $this->expiresAt = null;
        $this->maxUses = null;
        $this->rule = PromotionRule::None->value;
        $this->minOrderAmount = null;
        $this->isActive = true;
    }

    public function render(): View
    {
        return view('livewire.promotions-index', [
            'promotions' => Promotion::query()
                ->orderByDesc('is_active')
                ->orderByDesc('auto_apply')
                ->orderBy('name')
                ->get(),
            'rules' => PromotionRule::cases(),
            'types' => PromotionType::cases(),
        ]);
    }
}
