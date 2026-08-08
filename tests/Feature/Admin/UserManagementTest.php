<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

test('non-admin cannot access the users page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
});

test('admin can view the users list', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $other = User::factory()->create(['name' => 'Jane Doe']);

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Jane Doe');
});

test('search matches by name or email', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    User::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->set('search', 'jane@example.com')
        ->assertSee('Jane Doe')
        ->assertDontSee('John Smith');
});

test('the create form starts empty even after a previous edit was opened', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $other = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $other->id)
        ->call('create')
        ->assertSet('name', '')
        ->assertSet('email', '')
        ->assertSet('role', 'member');
});

test('admin can create a new user with a chosen role', function () {
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

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->hasRole('editor'))->toBeTrue();
    expect($user->hasRole('member'))->toBeFalse();
    expect($user->email_verified_at)->not->toBeNull();
});

test('creating a user logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    $countBefore = Activity::count();

    Livewire::test('pages::app.admin.users')
        ->call('create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save');

    expect(Activity::count())->toBe($countBefore + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toContain('was created');
    expect($activity->event)->toBe('created');
});

test('creating a user with an already taken email fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $existing = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('create')
        ->set('name', 'Jane Doe')
        ->set('email', $existing->email)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('creating a user with a mismatched password confirmation fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('create')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'different')
        ->call('save')
        ->assertHasErrors(['password']);
});

test('admin can edit a user\'s name and email', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('name', 'New Name')
        ->set('email', 'new@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->name)->toBe('New Name');
    expect($member->email)->toBe('new@example.com');
});

test('editing a user to an email already used by someone else fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $taken = User::factory()->create(['email' => 'taken@example.com']);
    $member = User::factory()->create(['email' => 'member@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('editing a user without changing their own email does not fail uniqueness validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create(['name' => 'Original Name', 'email' => 'member@example.com']);

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->email)->toBe('member@example.com');
});

test('admin can reset another user\'s password', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();
    $originalHash = $member->password;

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->password)->not->toBe($originalHash);
    expect(Hash::check('new-password', $member->fresh()->password))->toBeTrue();
});

test('leaving the password field blank while editing keeps the current password', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();
    $originalHash = $member->password;

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('name', 'Renamed Only')
        ->call('save')
        ->assertHasNoErrors();

    expect($member->fresh()->password)->toBe($originalHash);
});

test('resetting a password with a mismatched confirmation fails validation', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();
    $originalHash = $member->password;

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'different')
        ->call('save')
        ->assertHasErrors(['password']);

    expect($member->fresh()->password)->toBe($originalHash);
});

test('resetting a password logs activity without exposing the password itself', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('save');

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toContain('password was reset');
    expect($activity->description)->not->toContain('new-password');
    expect($activity->attribute_changes)->toBeEmpty();
    expect($activity->event)->toBe('password_reset');
});
test('editing a user\'s profile logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin);

    $countBefore = Activity::count();

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('name', 'New Name')
        ->call('save');

    expect(Activity::count())->toBe($countBefore + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toContain('profile was updated');
    expect($activity->event)->toBe('profile_updated');
});

test('admin can change another user\'s role', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('role', 'admin')
        ->call('save');

    expect($member->fresh()->hasRole('admin'))->toBeTrue();
    expect($member->fresh()->hasRole('member'))->toBeFalse();
});

test('changing a role to one that does not exist is rejected', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('role', 'superuser')
        ->call('save')
        ->assertHasErrors(['role']);

    expect($member->fresh()->hasRole('member'))->toBeTrue();
    expect($member->fresh()->hasRole('superuser'))->toBeFalse();
});

test('admin cannot remove their own admin role', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $admin->id)
        ->set('role', 'member')
        ->call('save');

    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('admin can still update their own name while keeping the admin role', function () {
    $admin = User::factory()->create(['name' => 'Old Admin Name']);
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')
        ->call('edit', $admin->id)
        ->set('name', 'New Admin Name')
        ->set('role', 'admin')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->fresh()->name)->toBe('New Admin Name');
    expect($admin->fresh()->hasRole('admin'))->toBeTrue();
});

test('changing a role logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    $countBefore = Activity::count();

    Livewire::test('pages::app.admin.users')
        ->call('edit', $member->id)
        ->set('role', 'admin')
        ->call('save');

    expect(Activity::count())->toBe($countBefore + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toContain('role was changed');
    expect($activity->event)->toBe('role_changed');
});

test('admin can delete another user', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')->call('delete', $member->id);

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

test('admin cannot delete their own account', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $this->actingAs($admin);

    Livewire::test('pages::app.admin.users')->call('delete', $admin->id);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

test('deleting a user logs activity', function () {
    $admin = User::factory()->create();
    $admin->syncRoles(Role::findOrCreate('admin'));

    $member = User::factory()->create();

    $this->actingAs($admin);

    $countBefore = Activity::count();

    Livewire::test('pages::app.admin.users')->call('delete', $member->id);

    expect(Activity::count())->toBe($countBefore + 1);

    $activity = Activity::query()->latest('id')->first();

    expect($activity->description)->toContain('was deleted');
    expect($activity->event)->toBe('deleted');
});