<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Title('Users')] class extends Component
{
    use PasswordValidationRules, WithPagination;

    /**
     * Roles that can be assigned through this UI. Deliberately a curated allow-list
     * rather than Role::query()->pluck('name') -- pulling straight from the roles
     * table would let anyone create a role record (e.g. via tinker, a seeder bug,
     * or a future feature) and have it immediately assignable here without review.
     */
    private const ASSIGNABLE_ROLES = ['admin', 'member'];

    public string $search = '';

    public ?User $editingUser = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $role = 'member';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('user-form')->show();
    }

    public function edit(int $userId): void
    {
        $user = User::query()->with('roles')->findOrFail($userId);

        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->role = $user->roles->first()?->name ?? 'member';

        Flux::modal('user-form')->show();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUser?->id)],
            'role' => ['required', 'string', Rule::in(self::ASSIGNABLE_ROLES)],
        ];

        if ($this->editingUser === null || $this->password !== '') {
            $rules['password'] = $this->passwordRules();
        }

        $this->validate($rules);

        if ($this->editingUser?->is(Auth::user()) && $this->role !== 'admin') {
            Flux::toast(variant: 'danger', text: 'You cannot remove your own admin role.');

            return;
        }

        // System-wide guard: block demoting the last remaining admin, even by
        // another admin. Self-demotion is already caught above; this covers the
        // remaining path where Admin A changes Admin B's (the last admin's) role.
        if (
            $this->editingUser
            && $this->editingUser->hasRole('admin')
            && $this->role !== 'admin'
            && $this->isLastAdmin($this->editingUser)
        ) {
            Flux::toast(variant: 'danger', text: 'Cannot remove the role of the last remaining admin.');

            return;
        }

        if ($this->editingUser === null) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles([$this->role]);

            activity('users')
                ->performedOn($user)
                ->withProperties(['role' => $this->role])
                ->event('created')
                ->log("{$user->name} was created with the {$this->role} role");

            Flux::toast(variant: 'success', text: "{$user->name} was created.");
        } else {
            $roleChanged = $this->editingUser->roles->pluck('name')->first() !== $this->role;
            $profileChanged = $this->editingUser->name !== $this->name || $this->editingUser->email !== $this->email;
            $passwordChanged = $this->password !== '';

            $data = [
                'name' => $this->name,
                'email' => $this->email,
            ];

            if ($passwordChanged) {
                $data['password'] = $this->password;
            }

            $this->editingUser->update($data);
            $this->editingUser->syncRoles([$this->role]);

            if ($profileChanged) {
                activity('users')
                    ->performedOn($this->editingUser)
                    ->event('profile_updated')
                    ->log("{$this->editingUser->name}'s profile was updated");
            }

            if ($passwordChanged) {
                activity('users')
                    ->performedOn($this->editingUser)
                    ->event('password_reset')
                    ->log("{$this->editingUser->name}'s password was reset");
            }

            if ($roleChanged) {
                activity('users')
                    ->performedOn($this->editingUser)
                    ->withProperties(['role' => $this->role])
                    ->event('role_changed')
                    ->log("{$this->editingUser->name}'s role was changed to {$this->role}");
            }

            Flux::toast(variant: 'success', text: "{$this->editingUser->name} was updated.");
        }

        Flux::modal('user-form')->close();

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingUser = null;
        $this->reset('name', 'email', 'password', 'password_confirmation');
        $this->role = 'member';
    }

    public function delete(int $userId): void
    {
        $user = User::query()->findOrFail($userId);

        if ($user->is(Auth::user())) {
            Flux::toast(variant: 'danger', text: 'You cannot delete your own account.');

            return;
        }

        if ($user->hasRole('admin') && $this->isLastAdmin($user)) {
            Flux::toast(variant: 'danger', text: 'Cannot delete the last remaining admin.');

            return;
        }

        activity('users')
            ->performedOn($user)
            ->event('deleted')
            ->log("{$user->name} ({$user->email}) was deleted");

        $user->delete();

        Flux::toast(variant: 'success', text: "{$user->name} was deleted.");
    }

    /**
     * Whether $user is the only admin left in the system. Checked before a delete
     * or a role change away from admin, so the system can never end up with zero
     * admins and become unrecoverable without direct database access.
     */
    private function isLastAdmin(User $user): bool
    {
        return $user->hasRole('admin')
            && User::role('admin')->count() === 1;
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function roles()
    {
        return self::ASSIGNABLE_ROLES;
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->with('roles')
            ->orderBy('name')
            ->paginate(10);
    }
}; ?>

<div class="w-full space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Users</flux:heading>
            <flux:subheading>Manage member accounts and their roles.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">Create user</flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by name or email" icon="magnifying-glass" class="max-w-sm" />

    <flux:card class="w-full">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Role</flux:table.column>
                <flux:table.column>Joined</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row :key="$user->id">
                        <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$user->hasRole('admin') ? 'indigo' : 'zinc'">
                                {{ ucfirst($user->roles->first()?->name ?? 'member') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $user->created_at->translatedFormat('l, j F Y') }}</flux:table.cell>
                        <flux:table.cell class="py-0">
                            <div class="flex items-center gap-1">
                                <flux:button type="button" variant="outline" size="sm" icon="pencil" wire:click="edit({{ $user->id }})" />

                                <flux:modal.trigger name="delete-user-{{ $user->id }}">
                                    <flux:button type="button" variant="danger" size="sm" icon="trash" :disabled="$user->is(Auth::user())" />
                                </flux:modal.trigger>
                            </div>

                            <flux:modal name="delete-user-{{ $user->id }}" class="max-w-md" focusable>
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete {{ $user->name }}?</flux:heading>
                                        <flux:subheading>This permanently removes their account. This cannot be undone.</flux:subheading>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="filled">Cancel</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" wire:click="delete({{ $user->id }})">
                                            Delete
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal flyout name="user-form" class="max-w-md" focusable>
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingUser ? 'Edit user' : 'Create user' }}</flux:heading>
                <flux:subheading>{{ $editingUser ? "Update this account's details and role." : 'Add a new account and assign it a role.' }}</flux:subheading>
            </div>

            <flux:input label="Name" wire:model="name" />
            <flux:input type="email" label="Email" wire:model="email" />

            <flux:input
                type="password"
                viewable
                label="Password"
                wire:model="password"
                :description="$editingUser ? 'Leave blank to keep the current password.' : null"
            />
            <flux:input type="password" viewable label="Confirm password" wire:model="password_confirmation" />

            <flux:select wire:model="role" label="Role">
                @foreach ($this->roles as $role)
                    <flux:select.option value="{{ $role }}">{{ ucfirst($role) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ $editingUser ? 'Save' : 'Create user' }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>