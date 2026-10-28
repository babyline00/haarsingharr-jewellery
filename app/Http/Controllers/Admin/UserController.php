<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $users = User::with('roles')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('q'), function ($q, $term) {
                $q->where(function ($matches) use ($term) {
                    $matches->where('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
                });
            })
            ->latest()
            ->paginate(20);

        return view('admin.users.index', [
            'users'   => $users,
            'filters' => $request->only(['type', 'status', 'q']),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'addresses']);
        $user->loadCount(['orders', 'reviews']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->isSuperAdmin() || (! $user->isSuperAdmin() && $user->type !== UserType::Admin)), 403);

        $actorPermissions = $actor?->getAllPermissions()->pluck('name')->all() ?? [];
        $currentRoleNames = $user->load('roles')->roles->pluck('name')->all();
        $roles = Role::with('permissions')
            ->orderBy('sort_order')
            ->get()
            ->reject(function (Role $role) use ($actor, $actorPermissions, $currentRoleNames) {
                $manageable = $actor->isSuperAdmin() || ($role->name !== 'super-admin'
                    && $role->permissions->every(fn ($permission) => in_array($permission->name, $actorPermissions, true)));
                return ! $manageable && ! in_array($role->name, $currentRoleNames, true);
            });

        return view('admin.users.edit', [
            'user'  => $user->load('roles'),
            'roles' => $roles,
            'canManageRoles' => $user->id !== $actor->id && ($actor->isSuperAdmin() || $actor->can('roles.update')),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        abort_unless($actor && ($actor->isSuperAdmin() || (! $user->isSuperAdmin() && $user->type !== UserType::Admin)), 403);

        $validated = $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'required|string|max:60',
            'email'      => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'      => 'nullable|string|max:20',
            'type'       => 'required|in:customer,staff,admin',
            'status'     => 'required|in:active,suspended,invited,deactivated',
            'password'   => 'nullable|string|min:8|confirmed',
            'roles'      => 'array',
            'roles.*'    => 'exists:roles,id',
        ]);

        if ($validated['type'] === UserType::Admin->value && ! $actor->isSuperAdmin()) {
            abort(403, 'Only a super administrator can promote an account to administrator.');
        }

        $canManageRoles = $user->id !== $actor->id && ($actor->isSuperAdmin() || $actor->can('roles.update'));
        if ($canManageRoles) {
            $currentRoles = $user->roles()->with('permissions')->get();
            $currentRoleIds = $currentRoles->pluck('id')->all();
            $roleQuery = Role::with('permissions')->whereIn('id', $validated['roles'] ?? []);
            $requestedRoles = $roleQuery->get();
            if (! $actor->isSuperAdmin()) {
                $actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
                foreach ($requestedRoles as $role) {
                    if (! in_array($role->id, $currentRoleIds, true) && ($role->name === 'super-admin'
                        || $role->permissions->contains(fn ($permission) => ! in_array($permission->name, $actorPermissions, true)))) {
                        abort(403, 'You cannot grant a role with permissions you do not hold.');
                    }
                }
                $preserve = $currentRoles->filter(fn (Role $role) => $role->name === 'super-admin'
                    || $role->permissions->contains(fn ($permission) => ! in_array($permission->name, $actorPermissions, true)));
                $names = $requestedRoles->pluck('name')->merge($preserve->pluck('name'))->unique()->all();
            } else {
                $names = $requestedRoles->pluck('name')->all();
            }
        }

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'type'       => $user->id === $actor->id ? $user->type : UserType::from($validated['type']),
            'status'     => $user->id === $actor->id ? $user->status : UserStatus::from($validated['status']),
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        if ($canManageRoles) {
            $user->syncRoles($names);
        }

        $this->audit->record('users.update', $user);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $actor = auth()->user();
        if ($user->id === $actor?->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        abort_unless($actor && ($actor->isSuperAdmin() || (! $user->isSuperAdmin() && $user->type !== UserType::Admin)), 403);

        $this->audit->record('users.delete', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User removed.');
    }
}
