<?php

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('member cannot access the gallery list page', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member)->get(route('content-management.gallery'))->assertForbidden();
});

test('guest is redirected away from the gallery list page', function () {
    $this->get(route('content-management.gallery'))->assertRedirect(route('login'));
});

test('member cannot create a gallery item even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.gallery')
        ->call('create')
        ->assertForbidden();
});

test('member cannot save a gallery item even via direct component call', function () {
    Storage::fake('public');

    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.gallery')
        ->set('title', 'Hacked')
        ->set('imageUpload', UploadedFile::fake()->image('photo.jpg'))
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseMissing('gallery_items', ['title' => 'Hacked']);
});

test('member cannot delete a gallery item even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $item = GalleryItem::create([
        'title' => 'Protected',
        'image' => 'gallery/protected.jpg',
        'order' => 0,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.gallery')
        ->call('delete', $item->id)
        ->assertForbidden();

    $this->assertDatabaseHas('gallery_items', ['id' => $item->id]);
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

test('uploading an svg as gallery image is rejected', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.gallery')
        ->call('create')
        ->set('title', 'Malicious')
        ->set('order', 0)
        ->set('imageUpload', UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml'))
        ->call('save')
        ->assertHasErrors('imageUpload');

    $this->assertDatabaseMissing('gallery_items', ['title' => 'Malicious']);
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