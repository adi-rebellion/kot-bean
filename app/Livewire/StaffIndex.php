<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Staff')]
class StaffIndex extends Component
{
    public bool $showForm = false;

    public string $name = '';

    public string $phone = '';

    public ?int $role_id = null;

    public ?string $generatedPassword = null;

    public ?string $createdStaffName = null;

    public ?string $createdStaffPhone = null;

    public function create(): void
    {
        $this->resetForm();
        $this->generatedPassword = null;
        $this->createdStaffName = null;
        $this->createdStaffPhone = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->phone = app(StaffService::class)->normalizePhone($this->phone);

        $restaurantId = auth()->user()->restaurant_id;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('users', 'phone')->where(
                    fn ($query) => $query->where('restaurant_id', $restaurantId),
                ),
            ],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query->where('restaurant_id', $restaurantId),
                ),
            ],
        ]);

        $result = app(StaffService::class)->create($data, auth()->user());

        $this->generatedPassword = $result['password'];
        $this->createdStaffName = $result['user']->name;
        $this->createdStaffPhone = $result['user']->phone;
        $this->showForm = false;
        $this->resetForm();
    }

    public function dismissCredentials(): void
    {
        $this->generatedPassword = null;
        $this->createdStaffName = null;
        $this->createdStaffPhone = null;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->phone = '';
        $this->role_id = null;
    }

    public function render(): View
    {
        $rolesQuery = Role::query()
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->orderBy('name');

        if (! auth()->user()->isOwner()) {
            $rolesQuery->where('slug', '!=', 'owner');
        }

        return view('livewire.staff-index', [
            'staff' => User::query()
                ->with('role')
                ->where('restaurant_id', auth()->user()->restaurant_id)
                ->orderBy('name')
                ->get(),
            'roles' => $rolesQuery->get(),
        ]);
    }
}
