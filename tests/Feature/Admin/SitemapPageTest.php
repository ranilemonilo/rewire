<?php

use App\Models\GalleryItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Service;
use App\Models\User;
use Spatie\Permission\Models\Role;

test('non-admin cannot access the sitemap page', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member)->get(route('admin.sitemap'))->assertForbidden();
});

test('guest is redirected away from the sitemap page', function () {
    $this->get(route('admin.sitemap'))->assertRedirect(route('login'));
});

test('admin can view the sitemap page and only sees published posts', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $published = Post::factory()->create([
        'title' => 'Published Post',
        'is_published' => true,
    ]);

    $draft = Post::factory()->create([
        'title' => 'Draft Post',
        'is_published' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee(route('blog.detail', $published->slug))
        ->assertDontSee(route('blog.detail', $draft->slug));
});

test('home entry lastModified reflects the most recently updated published page', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $page = Page::create([
        'title' => 'About Us',
        'content' => 'Our story.',
        'is_published' => true,
    ]);

    $this->travelTo(now()->addHour());
    $page->touch();

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee($page->fresh()->updated_at->diffForHumans());
});

test('home entry lastModified reflects the most recently updated active service', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $service = Service::create([
        'title' => 'Consulting',
        'icon' => 'briefcase',
        'description' => 'We consult.',
        'order' => 0,
        'is_active' => true,
    ]);

    $this->travelTo(now()->addHour());
    $service->touch();

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee($service->fresh()->updated_at->diffForHumans());
});

test('home entry lastModified reflects the most recently updated gallery item', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $item = GalleryItem::create([
        'title' => 'A Photo',
        'image' => 'gallery/photo.jpg',
        'order' => 0,
    ]);

    $this->travelTo(now()->addHour());
    $item->touch();

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee($item->fresh()->updated_at->diffForHumans());
});

test('unpublished page and inactive service do not affect home entry lastModified', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Page::create([
        'title' => 'Draft Page',
        'content' => 'Not published yet.',
        'is_published' => false,
    ]);

    Service::create([
        'title' => 'Inactive Service',
        'icon' => 'bolt',
        'description' => 'Not active.',
        'order' => 0,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sitemap'))
        ->assertOk()
        ->assertSee('—');
});