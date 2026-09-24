<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.kotbean')]
#[Title('Settings')]
class SettingsPage extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $gstin = '';

    public string $default_tax_rate = '';

    public string $timezone = '';

    public bool $tables_enabled = true;

    public bool $loyalty_enabled = false;

    public string $loyalty_points_per_100 = '1';

    public string $loyalty_rupees_per_point = '1';

    public string $theme_color = '#f59e0b';

    public $logo = null;

    public ?string $current_logo_url = null;

    public bool $remove_logo = false;

    /** @var array<string, string> */
    public array $themePresets = [
        'amber' => '#f59e0b',
        'orange' => '#f97316',
        'red' => '#ef4444',
        'rose' => '#f43f5e',
        'emerald' => '#10b981',
        'teal' => '#14b8a6',
        'blue' => '#3b82f6',
        'violet' => '#8b5cf6',
        'slate' => '#475569',
    ];

    public function mount(): void
    {
        $restaurant = auth()->user()->restaurant;

        $this->fill([
            'name' => $restaurant->name,
            'address' => $restaurant->address ?? '',
            'phone' => $restaurant->phone ?? '',
            'email' => $restaurant->email ?? '',
            'gstin' => $restaurant->gstin ?? '',
            'default_tax_rate' => (string) ($restaurant->default_tax_rate ?? 0),
            'timezone' => $restaurant->timezone ?? config('app.timezone'),
            'tables_enabled' => $restaurant->usesTables(),
            'loyalty_enabled' => (bool) ($restaurant->settings['loyalty_enabled'] ?? false),
            'loyalty_points_per_100' => (string) ($restaurant->settings['loyalty_points_per_100'] ?? 1),
            'loyalty_rupees_per_point' => (string) ($restaurant->settings['loyalty_rupees_per_point'] ?? 1),
            'theme_color' => $restaurant->themeColor(),
            'current_logo_url' => $restaurant->logoUrl(),
        ]);
    }

    public function updatedLogo(): void
    {
        $this->remove_logo = false;
        $this->validateOnly('logo', [
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);
    }

    public function selectThemeColor(string $color): void
    {
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $this->theme_color = $color;
        }
    }

    public function removeLogo(): void
    {
        $this->logo = null;
        $this->remove_logo = true;
        $this->current_logo_url = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:20',
            'default_tax_rate' => 'nullable|numeric|min:0|max:100',
            'timezone' => 'required|string|max:50',
            'loyalty_points_per_100' => 'nullable|integer|min:1|max:100',
            'loyalty_rupees_per_point' => 'nullable|numeric|min:0.01|max:1000',
            'theme_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        $restaurant = auth()->user()->restaurant;
        $settings = $restaurant->settings ?? [];
        $settings['tables_enabled'] = $this->tables_enabled;
        $settings['loyalty_enabled'] = $this->loyalty_enabled;
        $settings['loyalty_points_per_100'] = (int) ($data['loyalty_points_per_100'] ?? 1);
        $settings['loyalty_rupees_per_point'] = (float) ($data['loyalty_rupees_per_point'] ?? 1);
        $settings['theme_color'] = $data['theme_color'];

        $logoPath = $restaurant->logo_path;

        if ($this->remove_logo && $logoPath) {
            Storage::disk('public')->delete($logoPath);
            $logoPath = null;
        }

        if ($this->logo) {
            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $this->logo->store('restaurants/'.$restaurant->id, 'public');
        }

        $restaurant->update([
            'name' => $data['name'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'gstin' => $data['gstin'],
            'default_tax_rate' => $data['default_tax_rate'],
            'timezone' => $data['timezone'],
            'logo_path' => $logoPath,
            'settings' => $settings,
        ]);

        $this->logo = null;
        $this->remove_logo = false;
        $this->current_logo_url = $restaurant->fresh()->logoUrl();

        session()->flash('success', 'Settings saved successfully.');
    }

    public function render(): View
    {
        return view('livewire.settings-page');
    }
}
