<?php

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('any verified member can access the gallery list page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('content-management.gallery'))->assertOk();
});

test('admin can create a gallery item with an image', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')
        ->call('create')
        ->set('title', 'Test Photo')
        ->set('caption', 'A caption')
        ->set('order', 3)
        ->set('imageUpload', UploadedFile::fake()->image('photo.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $item = GalleryItem::query()->where('title', 'Test Photo')->firstOrFail();

    expect($item->caption)->toBe('A caption');
    expect($item->order)->toBe(3);
    Storage::disk('public')->assertExists($item->image);
});

test('creating a gallery item without an image fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')
        ->call('create')
        ->set('title', 'No Image')
        ->set('order', 0)
        ->call('save')
        ->assertHasErrors(['imageUpload' => 'required']);
});

test('admin can edit a gallery item without replacing the image', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $item = GalleryItem::create([
        'title' => 'Original',
        'image' => 'gallery/original.jpg',
        'order' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')
        ->call('edit', $item->id)
        ->set('title', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->title)->toBe('Updated');
    expect($item->fresh()->image)->toBe('gallery/original.jpg');
});

test('removeImage discards only the pending upload, not the persisted image', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $item = GalleryItem::create([
        'title' => 'Original',
        'image' => 'gallery/original.jpg',
        'order' => 0,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')
        ->call('edit', $item->id)
        ->set('imageUpload', UploadedFile::fake()->image('replacement.jpg'))
        ->call('removeImage')
        ->assertSet('imageUpload', null)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->image)->toBe('gallery/original.jpg');
});

test('admin can delete a gallery item and its stored image', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $item = GalleryItem::create([
        'title' => 'To Delete',
        'image' => 'gallery/to-delete.jpg',
        'order' => 0,
    ]);
    Storage::disk('public')->put($item->image, 'fake-content');

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')->call('delete', $item->id);

    $this->assertDatabaseMissing('gallery_items', ['id' => $item->id]);
    Storage::disk('public')->assertMissing('gallery/to-delete.jpg');
});
