<?php

use App\Models\Page;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Halaman')] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?Page $editingPage = null;

    public string $title = '';

    public string $excerpt = '';

    public string $content = '';

    public bool $isPublished = false;

    public $featuredImageUpload = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('page-form')->show();
    }

    public function edit(int $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);

        $this->editingPage = $page;
        $this->title = $page->title;
        $this->excerpt = $page->excerpt ?? '';
        $this->content = $page->content;
        $this->isPublished = $page->is_published;
        $this->featuredImageUpload = null;

        Flux::modal('page-form')->show();
    }

    public function removeFeaturedImage(): void
    {
        if ($this->editingPage?->featured_image) {
            Storage::disk('public')->delete($this->editingPage->featured_image);
            $this->editingPage->update(['featured_image' => null]);
        }
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featuredImageUpload' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'title' => $this->title,
            'excerpt' => $this->excerpt ?: null,
            'content' => $this->content,
            'is_published' => $this->isPublished,
        ];

        if ($this->featuredImageUpload) {
            if ($this->editingPage?->featured_image) {
                Storage::disk('public')->delete($this->editingPage->featured_image);
            }

            $data['featured_image'] = $this->featuredImageUpload->store('pages', 'public');
        }

        if ($this->editingPage === null) {
            Page::create($data);
        } else {
            $this->editingPage->update($data);
        }

        Flux::toast(variant: 'success', text: 'Page saved.');
        Flux::modal('page-form')->close();

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingPage = null;
        $this->reset('title', 'excerpt', 'content', 'isPublished', 'featuredImageUpload');
    }

    public function delete(int $pageId): void
    {
        $page = Page::query()->findOrFail($pageId);

        if ($page->featured_image) {
            Storage::disk('public')->delete($page->featured_image);
        }

        $title = $page->title;

        $page->delete();

        Flux::toast(variant: 'success', text: "\"{$title}\" was deleted.");
    }

    #[Computed]
    public function pages()
    {
        return Page::query()
            ->when($this->search, fn ($query) => $query->where('title', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Pages</flux:heading>
            <flux:subheading>Manage published and draft pages.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">New page</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->pages">
            <flux:table.columns>
                <flux:table.column>Image</flux:table.column>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->pages as $page)
                    <flux:table.row :key="$page->id">
                        <flux:table.cell class="py-2">
                            @if ($page->featured_image)
                                <img src="{{ Storage::disk('public')->url($page->featured_image) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                            @else
                                <div class="flex h-10 w-16 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400">
                                    <flux:icon icon="photo" variant="micro" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $page->title }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($page->is_published)
                                <flux:badge color="lime" size="sm">Published</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Draft</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $page->updated_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <div class="flex items-center gap-2">
                                <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $page->id }})" />

                                <flux:modal.trigger name="delete-page-{{ $page->id }}">
                                    <flux:button type="button" variant="danger" size="sm" icon="trash" />
                                </flux:modal.trigger>

                                <flux:modal name="delete-page-{{ $page->id }}" class="max-w-md" focusable>
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete "{{ $page->title }}"?</flux:heading>
                                            <flux:subheading>This permanently removes the page. This cannot be undone.</flux:subheading>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="filled">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:button variant="danger" wire:click="delete({{ $page->id }})">
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

    <flux:modal flyout name="page-form" class="w-3xl" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingPage ? 'Edit page' : 'Create page' }}</flux:heading>

            <flux:input wire:model="title" label="Title" />

            <flux:textarea wire:model="excerpt" label="Excerpt" rows="3" />

            <flux:textarea wire:model="content" label="Content" rows="10" />

            <div class="space-y-3">
                <flux:heading size="sm">Featured image</flux:heading>
                @if ($editingPage?->featured_image)
                    <div class="flex items-center gap-4">
                        <img src="{{ Storage::disk('public')->url($editingPage->featured_image) }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                        <flux:button type="button" variant="danger" size="sm" wire:click="removeFeaturedImage">Remove</flux:button>
                    </div>
                @endif
                <flux:input type="file" wire:model="featuredImageUpload" label="{{ $editingPage?->featured_image ? 'Replace image' : 'Upload image' }}" accept="image/*" />
                @if ($featuredImageUpload)
                    <img src="{{ $featuredImageUpload->temporaryUrl() }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                @endif
            </div>

            <flux:switch wire:model="isPublished" label="Published" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
