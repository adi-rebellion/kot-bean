<?php

namespace App\Livewire\Menu;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kotbean')]
#[Title('Categories')]
class CategoriesIndex extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public function create(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $categoryId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $category = Category::findOrFail($categoryId);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->sortOrder = $category->sort_order;
        $this->isActive = $category->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sortOrder' => ['integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'],
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ];

        $service = app(CategoryService::class);

        if ($this->editingId) {
            $service->update(Category::findOrFail($this->editingId), $payload);
        } else {
            $service->create($payload, auth()->user()->restaurant);
        }

        $this->resetForm();
        $this->showForm = false;
        session()->flash('success', 'Category saved successfully.');
    }

    public function delete(int $categoryId): void
    {
        abort_unless(auth()->user()->hasPermission('menu.manage'), 403);

        try {
            app(CategoryService::class)->delete(Category::findOrFail($categoryId));
            session()->flash('success', 'Category deleted.');
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
        $this->description = '';
        $this->sortOrder = 0;
        $this->isActive = true;
    }

    public function render(): View
    {
        return view('livewire.menu.categories-index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
