<?php

use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

test('member cannot access the services list page', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member)->get(route('content-management.services'))->assertForbidden();
});

test('guest is redirected away from the services list page', function () {
    $this->get(route('content-management.services'))->assertRedirect(route('login'));
});

test('member cannot create a service even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.services')
        ->call('create')
        ->assertForbidden();
});

test('member cannot save a service even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.services')
        ->set('title', 'Hacked Service')
        ->set('icon', 'bolt')
        ->set('description', 'Malicious description.')
        ->set('order', 0)
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseMissing('services', ['title' => 'Hacked Service']);
});

test('member cannot delete a service even via direct component call', function () {
    $member = User::factory()->create();
    $member->syncRoles(Role::findOrCreate('member'));

    $service = Service::create([
        'title' => 'Protected Service',
        'icon' => 'bolt',
        'description' => 'A service.',
        'order' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($member);

    Livewire::test('pages::app.content-management.services')
        ->call('delete', $service->id)
        ->assertForbidden();

    $this->assertDatabaseHas('services', ['id' => $service->id]);
});

test('admin can view the services list', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $service = Service::create([
        'title' => 'Web Development',
        'icon' => 'code',
        'description' => 'We build websites.',
        'order' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('content-management.services'))
        ->assertOk()
        ->assertSee($service->title);
});

test('admin can create a service', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->call('create')
        ->set('title', 'Consulting')
        ->set('icon', 'briefcase')
        ->set('description', 'We consult on strategy.')
        ->set('order', 2)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::query()->where('title', 'Consulting')->firstOrFail();

    expect($service->icon)->toBe('briefcase');
    expect($service->description)->toBe('We consult on strategy.');
    expect($service->order)->toBe(2);
    expect($service->is_active)->toBeTrue();
});

test('creating a service requires title, icon, description, and order', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->call('create')
        ->set('title', '')
        ->set('icon', '')
        ->set('description', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required', 'icon' => 'required', 'description' => 'required']);
});

test('admin can edit an existing service', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $service = Service::create([
        'title' => 'Original Title',
        'icon' => 'bolt',
        'description' => 'Original description.',
        'order' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->call('edit', $service->id)
        ->set('title', 'Updated Title')
        ->set('description', 'Updated description.')
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    $service->refresh();

    expect($service->title)->toBe('Updated Title');
    expect($service->description)->toBe('Updated description.');
    expect($service->is_active)->toBeFalse();
});

test('admin can delete a service', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $service = Service::create([
        'title' => 'To Delete',
        'icon' => 'bolt',
        'description' => 'Will be deleted.',
        'order' => 0,
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')->call('delete', $service->id);

    $this->assertDatabaseMissing('services', ['id' => $service->id]);
});

test('search matches by title', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Service::create(['title' => 'Web Development', 'icon' => 'code', 'description' => 'Body.', 'order' => 0, 'is_active' => true]);
    Service::create(['title' => 'Graphic Design', 'icon' => 'palette', 'description' => 'Body.', 'order' => 1, 'is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->set('search', 'Web')
        ->assertSee('Web Development')
        ->assertDontSee('Graphic Design');
});

test('services are ordered by the order column', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    Service::create(['title' => 'Third', 'icon' => 'bolt', 'description' => 'Body.', 'order' => 2, 'is_active' => true]);
    Service::create(['title' => 'First', 'icon' => 'bolt', 'description' => 'Body.', 'order' => 0, 'is_active' => true]);
    Service::create(['title' => 'Second', 'icon' => 'bolt', 'description' => 'Body.', 'order' => 1, 'is_active' => true]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->assertSeeInOrder(['First', 'Second', 'Third']);
});

test('an inactive service can still be managed by admin', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $service = Service::create([
        'title' => 'Inactive Service',
        'icon' => 'bolt',
        'description' => 'Currently inactive.',
        'order' => 0,
        'is_active' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test('pages::app.content-management.services')
        ->call('edit', $service->id)
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($service->fresh()->is_active)->toBeTrue();
});