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

    public string $email = '';

    public ?int $role_id = null;

    public bool $invitedExisting = false;

    public ?string $generatedPassword = null;

    public ?string $createdStaffName = null;

    public ?string $createdStaffPhone = null;

    public function create(): void
    {
        $this->resetForm();
        $this->generatedPassword = null;
        $this->createdStaffName = null;
        $this->createdStaffPhone = null;
        $this->invitedExisting = false;
        $this->showForm = true;
    }

    public function save(): void
    {
        $restaurantId = auth()->user()->restaurant_id;
        $staffService = app(StaffService::class);
        $memberIds = auth()->user()->restaurant
            ->members()
            ->pluck('users.id');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'lowercase', 'email', 'exists:users,email'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where(
                    fn ($query) => $query->where('restaurant_id', $restaurantId),
                ),
            ],
        ]);

        if ($this->email !== '') {
            $existing = User::query()->where('email', $this->email)->firstOrFail();
            $role = Role::query()->findOrFail($this->role_id);

            try {
                $result = $staffService->inviteExisting($existing, $role, auth()->user());
            } catch (\InvalidArgumentException $exception) {
                session()->flash('error', $exception->getMessage());

                return;
            }

            $this->invitedExisting = true;
            $this->generatedPassword = null;
            $this->createdStaffName = $result['user']->name;
            $this->createdStaffPhone = $result['user']->phone;
            $this->showForm = false;
            $this->resetForm();

            return;
        }

        $this->phone = $staffService->normalizePhone($this->phone);

        $data = $this->validate([
            'phone' => [
                'required',
                'string',
                'digits:10',
                Rule::unique('users', 'phone')->where(
                    fn ($query) => $query->whereIn('id', $memberIds),
                ),
            ],
        ]);

        $result = $staffService->create([
            'name' => $this->name,
            'phone' => $data['phone'],
            'role_id' => $this->role_id,
        ], auth()->user());

        $this->invitedExisting = false;
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
        $this->invitedExisting = false;
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
        $this->email = '';
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

        $restaurant = auth()->user()->restaurant;
        $rolesById = Role::query()
            ->where('restaurant_id', $restaurant->id)
            ->get()
            ->keyBy('id');

        $staff = $restaurant->members()
            ->orderBy('name')
            ->get()
            ->each(function (User $member) use ($rolesById): void {
                $member->setRelation('role', $rolesById->get($member->pivot->role_id));
            });

        return view('livewire.staff-index', [
            'staff' => $staff,
            'roles' => $rolesQuery->get(),
        ]);
    }
}
