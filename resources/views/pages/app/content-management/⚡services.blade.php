<?php

use App\Models\Service;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Services')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?Service $editingService = null;

    public string $title = '';

    public string $icon = '';

    public string $description = '';

    public int $order = 0;

    public bool $isActive = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('service-form')->show();
    }

    public function edit(int $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);

        $this->editingService = $service;
        $this->title = $service->title;
        $this->icon = $service->icon;
        $this->description = $service->description;
        $this->order = $service->order;
        $this->isActive = $service->is_active;

        Flux::modal('service-form')->show();
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:64'],
            'description' => ['required', 'string', 'max:1000'],
            'order' => ['required', 'integer', 'min:0'],
        ]);

        $data = [
            'title' => $this->title,
            'icon' => $this->icon,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->isActive,
        ];

        if ($this->editingService === null) {
            Service::create($data);
        } else {
            $this->editingService->update($data);
        }

        Flux::toast(variant: 'success', text: 'Service saved.');
        Flux::modal('service-form')->close();

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingService = null;
        $this->reset('title', 'icon', 'description', 'order', 'isActive');
    }

    public function delete(int $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);

        $title = $service->title;

        $service->delete();

        Flux::toast(variant: 'success', text: "\"{$title}\" was deleted.");
    }

    #[Computed]
    public function services()
    {
        return Service::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->ordered()
            ->paginate(10);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Services</flux:heading>
            <flux:subheading>Manage the services shown on the public site.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">New service</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->services">
            <flux:table.columns>
                <flux:table.column>Icon</flux:table.column>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->services as $service)
                    <flux:table.row :key="$service->id">
                        <flux:table.cell>{{ $service->icon }}</flux:table.cell>
                        <flux:table.cell variant="strong">{{ $service->title }}</flux:table.cell>
                        <flux:table.cell>{{ $service->order }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($service->is_active)
                                <flux:badge color="lime" size="sm">Active</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $service->updated_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <div class="flex items-center gap-2">
                                <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $service->id }})" />

                                <flux:modal.trigger name="delete-service-{{ $service->id }}">
                                    <flux:button type="button" variant="danger" size="sm" icon="trash" />
                                </flux:modal.trigger>

                                <flux:modal name="delete-service-{{ $service->id }}" class="max-w-md" focusable>
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete "{{ $service->title }}"?</flux:heading>
                                            <flux:subheading>This permanently removes the service. This cannot be undone.</flux:subheading>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="filled">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:button variant="danger" wire:click="delete({{ $service->id }})">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:modal>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal flyout name="service-form" class="w-3xl" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingService ? 'Edit service' : 'Create service' }}</flux:heading>

            <flux:input wire:model="title" label="Title" />

            <flux:input wire:model="icon" label="Icon" />

            <flux:textarea wire:model="description" label="Description" rows="4" />

            <flux:input type="number" wire:model="order" label="Order" />

            <flux:switch wire:model="isActive" label="Active" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
