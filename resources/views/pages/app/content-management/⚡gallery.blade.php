<?php

use App\Models\GalleryItem;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Gallery')] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?GalleryItem $editingGalleryItem = null;

    public string $title = '';

    public string $caption = '';

    public int $order = 0;

    public $imageUpload = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $this->resetForm();

        Flux::modal('gallery-form')->show();
    }

    public function edit(int $galleryItemId): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $galleryItem = GalleryItem::query()->findOrFail($galleryItemId);

        $this->editingGalleryItem = $galleryItem;
        $this->title = $galleryItem->title ?? '';
        $this->caption = $galleryItem->caption ?? '';
        $this->order = $galleryItem->order;
        $this->imageUpload = null;

        Flux::modal('gallery-form')->show();
    }

    public function removeImage(): void
    {
        // The image column is required (unlike Post's nullable featured_image), so this
        // only discards the newly selected file that hasn't been saved yet -- it reverts
        // the form back to the existing persisted image, never leaves the item imageless.
        $this->reset('imageUpload');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        $this->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'order' => ['required', 'integer', 'min:0'],
            'imageUpload' => [$this->editingGalleryItem === null ? 'required' : 'nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'title' => $this->title ?: null,
            'caption' => $this->caption ?: null,
            'order' => $this->order,
        ];

        if ($this->imageUpload) {
            if ($this->editingGalleryItem?->image) {
                Storage::disk('public')->delete($this->editingGalleryItem->image);
            }

            $data['image'] = $this->imageUpload->store('gallery', 'public');
        }

        if ($this->editingGalleryItem === null) {
            GalleryItem::create($data);
        } else {
            $this->editingGalleryItem->update($data);
        }

        Flux::toast(variant: 'success', text: 'Photo saved.');
        Flux::modal('gallery-form')->close();

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingGalleryItem = null;
        $this->reset('title', 'caption', 'order', 'imageUpload');
    }

    public function delete(int $galleryItemId): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        
        $galleryItem = GalleryItem::query()->findOrFail($galleryItemId);

        Storage::disk('public')->delete($galleryItem->image);

        $title = $galleryItem->title;

        $galleryItem->delete();

        Flux::toast(variant: 'success', text: '"'.($title ?: 'Untitled').'" was deleted.');
    }

    #[Computed]
    public function galleryItems()
    {
        return GalleryItem::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->ordered()
            ->paginate(10);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Gallery</flux:heading>
            <flux:subheading>Manage the photos shown in the public gallery.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">New photo</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->galleryItems">
            <flux:table.columns>
                <flux:table.column>Image</flux:table.column>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Caption</flux:table.column>
                <flux:table.column>Order</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->galleryItems as $galleryItem)
                    <flux:table.row :key="$galleryItem->id">
                        <flux:table.cell class="py-2">
                            <img src="{{ Storage::disk('public')->url($galleryItem->image) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $galleryItem->title ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $galleryItem->caption ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $galleryItem->order }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $galleryItem->updated_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <div class="flex items-center gap-2">
                                <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $galleryItem->id }})" />

                                <flux:modal.trigger name="delete-gallery-item-{{ $galleryItem->id }}">
                                    <flux:button type="button" variant="danger" size="sm" icon="trash" />
                                </flux:modal.trigger>

                                <flux:modal name="delete-gallery-item-{{ $galleryItem->id }}" class="max-w-md" focusable>
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete "{{ $galleryItem->title ?? 'Untitled' }}"?</flux:heading>
                                            <flux:subheading>This permanently removes the photo. This cannot be undone.</flux:subheading>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="filled">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:button variant="danger" wire:click="delete({{ $galleryItem->id }})">
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

    <flux:modal flyout name="gallery-form" class="w-3xl" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingGalleryItem ? 'Edit photo' : 'Create photo' }}</flux:heading>

            <flux:input wire:model="title" label="Title" />

            <flux:input wire:model="caption" label="Caption" />

            <flux:input type="number" wire:model="order" label="Order" />

            <div class="space-y-3">
                <flux:heading size="sm">Image</flux:heading>

                @if ($editingGalleryItem?->image && ! $imageUpload)
                    <div class="flex items-center gap-4">
                        <img src="{{ Storage::disk('public')->url($editingGalleryItem->image) }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                    </div>
                @endif

                <flux:field>
                    <flux:label>{{ $editingGalleryItem?->image ? 'Replace image' : 'Upload image' }}</flux:label>
                    {{-- A plain native input on purpose: Flux's <flux:input type="file"> wraps the real
                         <input> in a `wire:ignore` div (see vendor/livewire/flux/stubs/resources/views/flux/input/file.blade.php).
                         That div exists so Alpine can manage the "Choose file" button/label without Livewire's
                         morph fighting it, but it also means Livewire's own re-render after the upload finishes
                         can lose track of the file input for the *next* request -- the temp file is uploaded
                         (which is why the preview works), but the property comes back empty when save() runs.
                         Bypassing the Flux file variant avoids that wire:ignore subtree entirely. --}}
                    <input
                        type="file"
                        wire:model="imageUpload"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-700 file:mr-4 file:rounded-lg file:border-0 file:bg-zinc-800/5 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 hover:file:bg-zinc-800/10 dark:text-zinc-300 dark:file:bg-white/10 dark:file:text-zinc-200"
                    >
                    <flux:error name="imageUpload" />
                </flux:field>

                @if ($imageUpload)
                    <div class="flex items-center gap-4">
                        <img src="{{ $imageUpload->temporaryUrl() }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                        <flux:button type="button" variant="danger" size="sm" wire:click="removeImage">Remove</flux:button>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
