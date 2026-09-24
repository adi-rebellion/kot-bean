<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Staff')]
class StaffIndex extends Component
{
    public function render(): View
    {
        $staff = User::query()
            ->with('role')
            ->where('restaurant_id', auth()->user()->restaurant_id)
            ->orderBy('name')
            ->get();

        return view('livewire.staff-index', [
            'staff' => $staff,
        ]);
    }
}
