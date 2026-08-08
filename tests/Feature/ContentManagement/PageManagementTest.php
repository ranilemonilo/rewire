<?php

use App\Models\Page;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('member cannot access the pages list', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member)->get(route('content-management.pages'))->assertForbidden();
});

test('guest is redirected away from the pages list', function () {
    $this->get(route('content-management.pages'))->assertRedirect(route('login'));
});

test('member cannot edit a page even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->assertForbidden();
});

test('member cannot save a page even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.pages')
        ->set('title', 'Hacked title')
        ->call('save')
        ->assertForbidden();

    expect($page->fresh()->title)->toBe('Tentang Kami');
});

test('admin can view the pages list', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create([
        'title' => 'Tentang Kami',
        'content' => 'Company story.',
        'is_published' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('content-management.pages'))
        ->assertOk()
        ->assertSee($page->title);
});

test('search matches by title', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);
    Page::create(['title' => 'Kebijakan Privasi', 'content' => 'Body.']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->set('search', 'Tentang')
        ->assertSee('Tentang Kami')
        ->assertDontSee('Kebijakan Privasi');
});

test('admin can edit a page', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create([
        'title' => 'Tentang Kami',
        'excerpt' => 'Old excerpt.',
        'content' => 'Old content.',
        'is_published' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('title', 'Tentang Perusahaan Kami')
        ->set('excerpt', 'New excerpt.')
        ->set('content', 'New content.')
        ->set('isPublished', true)
        ->call('save')
        ->assertHasNoErrors();

    $page->refresh();

    expect($page->title)->toBe('Tentang Perusahaan Kami');
    expect($page->excerpt)->toBe('New excerpt.');
    expect($page->content)->toBe('New content.');
    expect($page->is_published)->toBeTrue();
    expect($page->slug)->toBe('tentang-kami');
});

test('editing a page requires a title and content', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('title', '')
        ->set('content', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required', 'content' => 'required']);
});

test('admin can upload a featured image while editing a page', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('featuredImageUpload', UploadedFile::fake()->image('about.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists($page->fresh()->featured_image);
});

test('uploading an svg as featured image is rejected', function () {
    Storage::fake('public');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('featuredImageUpload', UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml'))
        ->call('save')
        ->assertHasErrors(['featuredImageUpload' => 'mimes']);

    expect($page->fresh()->featured_image)->toBeNull();
});

test('clicking remove on a persisted featured image does not touch storage until save', function () {
    Storage::fake('public');
    Storage::disk('public')->put('pages/existing.jpg', 'fake-content');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.', 'featured_image' => 'pages/existing.jpg']);

    $this->actingAs($admin);

    $component = Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->call('clearFeaturedImage');

    Storage::disk('public')->assertExists('pages/existing.jpg');
    expect($page->fresh()->featured_image)->toBe('pages/existing.jpg');

    $component->call('save')->assertHasNoErrors();

    Storage::disk('public')->assertMissing('pages/existing.jpg');
    expect($page->fresh()->featured_image)->toBeNull();
});

test('clicking remove on a newly selected upload only cancels that selection', function () {
    Storage::fake('public');
    Storage::disk('public')->put('pages/existing.jpg', 'fake-content');

    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.', 'featured_image' => 'pages/existing.jpg']);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('featuredImageUpload', UploadedFile::fake()->image('replacement.jpg'))
        ->call('clearFeaturedImage')
        ->assertSet('featuredImageUpload', null)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertExists('pages/existing.jpg');
    expect($page->fresh()->featured_image)->toBe('pages/existing.jpg');
});

test('publishing a page logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create(['title' => 'Tentang Kami', 'content' => 'Body.', 'is_published' => false]);

    $this->actingAs($admin);

    $countBeforeEdit = Activity::count();

    Livewire::test('pages::app.content-management.pages')
        ->call('edit', $page->id)
        ->set('isPublished', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Activity::count())->toBe($countBeforeEdit + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->event)->toBe('updated');
    expect($activity->description)->toContain($page->title);
});