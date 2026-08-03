<?php

use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('non-admin cannot access the activity log page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.activity'))->assertForbidden();
});

test('admin can view the activity log page', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin)->get(route('admin.activity'))->assertOk();
});

test('creating a user logs activity with the acting admin as causer', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));
    Role::findOrCreate('editor');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', 'editor')
        ->call('save')
        ->assertHasNoErrors();

    $activity = Activity::query()->latest()->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer->is($admin))->toBeTrue();
});

test('changing a role logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('role', 'admin')
        ->call('save');

    $activity = Activity::query()->latest()->first();

    expect($activity)->not->toBeNull();
});

test('deleting a user logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')->call('delete', $member->id);

    $activity = Activity::query()->latest()->first();

    expect($activity)->not->toBeNull();
});

test('search matches by description', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    activity('test')->log('Unique Searchable Description');
    activity('test')->log('Something else entirely');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->set('search', 'Unique Searchable')
        ->assertSee('Unique Searchable Description')
        ->assertDontSee('Something else entirely');
});

test('search matches by log name', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    activity('special-log-name')->log('Description A');
    activity('other-log-name')->log('Description B');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->set('search', 'special-log-name')
        ->assertSee('Description A')
        ->assertDontSee('Description B');
});

test('search matches by causer name', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $causer = User::factory()->create(['name' => 'Distinctive Causer Name']);
    $otherCauser = User::factory()->create(['name' => 'Someone Else']);

    activity('test')->causedBy($causer)->log('Action by distinctive causer');
    activity('test')->causedBy($otherCauser)->log('Action by someone else');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->set('search', 'Distinctive Causer')
        ->assertSee('Action by distinctive causer')
        ->assertDontSee('Action by someone else');
});

test('the subject is displayed for an activity with a subject', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create(['title' => 'My Great Post']);

    activity('blog')->performedOn($post)->log('The post was updated');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->assertSee('Post: My Great Post');
});

test('the subject shows as deleted when the underlying record no longer exists', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $post = Post::factory()->create();
    activity('blog')->performedOn($post)->log('The post was updated');
    $post->delete();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->assertSee('Post (deleted)');
});

test('the log name is displayed', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    activity('gallery')->log('A gallery item was created');

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.activity')
        ->assertSee('Gallery');
});

test('pagination shows twenty activities per page', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    foreach (range(1, 25) as $i) {
        activity('test')->log("Paginated activity {$i}");
    }

    $this->actingAs($admin);

    $component = Livewire::test('pages::app.admin.activity');

    expect($component->instance()->activities->count())->toBe(20);
    expect($component->instance()->activities->total())->toBeGreaterThanOrEqual(25);

    $component->call('nextPage');

    expect($component->instance()->activities->currentPage())->toBe(2);
    expect($component->instance()->activities->count())->toBeGreaterThanOrEqual(5);
});
