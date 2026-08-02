<?php

use App\Models\Post;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Blog')] class extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public ?Post $editingPost = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public bool $isPublished = false;

    public string $publishedAt = '';

    public $featuredImageUpload = null;

    public bool $removeFeaturedImage = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTitle(): void
    {
        // Only while creating: once a post exists, the slug is its public URL, so
        // typing in the title afterwards must never silently change it underneath the admin.
        if ($this->editingPost === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('post-form')->show();
    }

    public function edit(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        $this->editingPost = $post;
        $this->title = $post->title;
        $this->slug = $post->slug;
        $this->excerpt = $post->excerpt ?? '';
        $this->body = $post->body;
        $this->isPublished = $post->is_published;
        $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->featuredImageUpload = null;
        $this->removeFeaturedImage = false;

        Flux::modal('post-form')->show();
    }

    /**
     * Cancels a not-yet-saved upload if one is staged, otherwise marks the persisted
     * featured image for deletion. Either way, nothing touches storage or the database
     * until save() actually runs -- clicking this button alone changes nothing permanent.
     */
    public function clearFeaturedImage(): void
    {
        if ($this->featuredImageUpload) {
            $this->reset('featuredImageUpload');

            return;
        }

        $this->removeFeaturedImage = true;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($this->editingPost?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'featuredImageUpload' => [$this->editingPost === null ? 'required' : 'nullable', 'image', 'max:2048'],
            'publishedAt' => ['nullable', 'date'],
        ]);

        // A post marked published must always have a publish date -- default it to now()
        // rather than let a published post silently carry a null published_at.
        if ($this->isPublished && ! $this->publishedAt) {
            $this->publishedAt = now()->format('Y-m-d\TH:i');
        }

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt ?: null,
            'body' => $this->body,
            'is_published' => $this->isPublished,
            'published_at' => $this->publishedAt ?: null,
        ];

        if ($this->featuredImageUpload) {
            if ($this->editingPost?->featured_image) {
                Storage::disk('public')->delete($this->editingPost->featured_image);
            }

            $data['featured_image'] = $this->featuredImageUpload->store('blog', 'public');
        } elseif ($this->removeFeaturedImage && $this->editingPost?->featured_image) {
            Storage::disk('public')->delete($this->editingPost->featured_image);
            $data['featured_image'] = null;
        }

        if ($this->editingPost === null) {
            $data['author_id'] = Auth::id();

            Post::create($data);
        } else {
            $this->editingPost->update($data);
        }

        Flux::toast(variant: 'success', text: 'Blog saved.');
        Flux::modal('post-form')->close();

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingPost = null;
        $this->reset('title', 'slug', 'excerpt', 'body', 'isPublished', 'publishedAt', 'featuredImageUpload', 'removeFeaturedImage');
    }

    public function delete(int $postId): void
    {
        $post = Post::query()->findOrFail($postId);

        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        Flux::toast(variant: 'success', text: 'Blog deleted.');
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")))
            ->with('author')
            ->latest()
            ->paginate(10);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Blog posts</flux:heading>
            <flux:subheading>Manage published and draft blog posts.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">New post</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by title or slug" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->posts">
            <flux:table.columns>
                <flux:table.column>Image</flux:table.column>
                <flux:table.column>Title</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Author</flux:table.column>
                <flux:table.column>Published</flux:table.column>
                <flux:table.column>Updated</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->posts as $post)
                    <flux:table.row :key="$post->id">
                        <flux:table.cell class="py-2">
                            @if ($post->featured_image)
                                <img src="{{ Storage::disk('public')->url($post->featured_image) }}" alt="" class="h-10 w-16 rounded-lg object-cover">
                            @else
                                <div class="flex h-10 w-16 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400">
                                    <flux:icon icon="photo" variant="micro" />
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $post->title }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap font-mono text-xs text-zinc-500">{{ $post->slug }}</flux:table.cell>
                        <flux:table.cell>
                            @if (! $post->is_published)
                                <flux:badge color="zinc" size="sm">Draft</flux:badge>
                            @elseif ($post->published_at?->isFuture())
                                <flux:badge color="amber" size="sm">Scheduled</flux:badge>
                            @else
                                <flux:badge color="lime" size="sm">Published</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $post->author?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $post->published_at?->translatedFormat('l, j F Y') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $post->updated_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <div class="flex items-center gap-2">
                                <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $post->id }})" />

                                <flux:modal.trigger name="delete-post-{{ $post->id }}">
                                    <flux:button type="button" variant="danger" size="sm" icon="trash" />
                                </flux:modal.trigger>

                                <flux:modal name="delete-post-{{ $post->id }}" class="max-w-md" focusable>
                                    <div class="space-y-6">
                                        <div>
                                            <flux:heading size="lg">Delete "{{ $post->title }}"?</flux:heading>
                                            <flux:subheading>This permanently removes the post. This cannot be undone.</flux:subheading>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <flux:modal.close>
                                                <flux:button variant="filled">Cancel</flux:button>
                                            </flux:modal.close>

                                            <flux:button variant="danger" wire:click="delete({{ $post->id }})">
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

    <flux:modal flyout name="post-form" class="w-3xl" focusable>
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingPost ? 'Edit post' : 'Create post' }}</flux:heading>

            <flux:input wire:model.live.debounce.300ms="title" label="Title" />

            <flux:input wire:model="slug" label="Slug" description="Auto-filled from the title while creating. Editable any time." />

            <flux:textarea wire:model="excerpt" label="Excerpt" rows="3" />

            <flux:textarea wire:model="body" label="Content" rows="10" />

            <div class="space-y-3">
                <flux:heading size="sm">Featured image</flux:heading>

                @if ($editingPost?->featured_image && ! $removeFeaturedImage && ! $featuredImageUpload)
                    <div class="flex items-center gap-4">
                        <img src="{{ Storage::disk('public')->url($editingPost->featured_image) }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                        <flux:button type="button" variant="danger" size="sm" wire:click="clearFeaturedImage">Remove</flux:button>
                    </div>
                @endif

                <flux:input type="file" wire:model="featuredImageUpload" label="{{ $editingPost?->featured_image && ! $removeFeaturedImage ? 'Replace image' : 'Upload image' }}" accept="image/*" />

                @if ($featuredImageUpload)
                    <div class="flex items-center gap-4">
                        <img src="{{ $featuredImageUpload->temporaryUrl() }}" class="h-20 w-32 rounded-lg object-cover" alt="">
                        <flux:button type="button" variant="danger" size="sm" wire:click="clearFeaturedImage">Remove</flux:button>
                    </div>
                @endif
            </div>

            <flux:switch wire:model.live="isPublished" label="Published" />

            @if ($isPublished)
                <flux:input type="datetime-local" wire:model="publishedAt" label="Published at" description="Leave blank to use the current date and time." />
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
