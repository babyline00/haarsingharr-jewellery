<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = $request->input('q');

        $roles = Role::with('permissions')->withCount(['permissions', 'users'])
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('title', 'like', "%{$term}%")
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $actor = auth()->user();
        $availablePermissions = $actor->getAllPermissions()->pluck('name')->all();
        $roles->getCollection()->each(function (Role $role) use ($actor, $availablePermissions) {
            $role->setAttribute('can_clone', $actor->isSuperAdmin()
                || $role->permissions->every(fn ($permission) => in_array($permission->name, $availablePermissions, true)));
        });

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Permission matrix: every role as a column, every permission as a row,
     * grouped by module. Saves re-sync every submitted role in one POST.
     */
    public function matrix(): View
    {
        $roles = Role::with('permissions')->orderBy('sort_order')->orderBy('name')->get();
        $permissions = Permission::orderBy('module')->orderBy('action')->get();
        $manageablePermissionIds = $this->manageablePermissionIds($permissions);

        $matrixRoles = $roles
            ->map(function ($role) {
                return [
                    'id'     => $role->id,
                    'title'  => $role->title ?? $role->name,
                    'slug'   => $role->slug,
                    'system' => (bool) $role->is_system,
                    'locked' => $role->slug === 'super-admin',
                    'owned'  => auth()->user()->hasRole($role->name),
                    'permissions' => $role->permissions->pluck('id')->all(),
                ];
            })
            ->all();

        $modules = $permissions->groupBy('module');

        return view('admin.roles.matrix', [
            'roles'       => $roles,
            'matrixRoles' => $matrixRoles,
            'modules'     => $modules,
            'lockedRole'  => collect($matrixRoles)->firstWhere('slug', 'super-admin'),
            'manageablePermissionIds' => $manageablePermissionIds,
        ]);
    }

    /**
     * Apply matrix edits: re-sync permissions for every submitted role column.
     * The signed-in super-admin's own permissions are untouchable here.
     */
    public function updateMatrix(Request $request)
    {
        $request->validate([
            'matrix'   => 'array',
            'matrix.*' => 'array',
            'matrix.*.*' => 'integer|exists:permissions,id',
            'matrix_roles' => 'array',
            'matrix_roles.*' => 'integer|exists:roles,id',
        ]);

        $submitted = $request->input('matrix', []);
        $editableRoleIds = array_unique(array_map('intval', $request->input('matrix_roles', [])));
        $isSuperAdmin = auth()->user()->isSuperAdmin();
        $roles = Role::with('permissions')->get()->keyBy('id');
        foreach ($editableRoleIds as $roleId) {
            if ($role = $roles->get($roleId)) {
                $this->permissionsForRole($role, Permission::whereIn('id', $submitted[$roleId] ?? [])->pluck('name')->all());
            }
        }
        $changed = 0;

        foreach ($roles as $role) {
            if (! in_array($role->id, $editableRoleIds, true)) {
                continue;
            }

            if ($role->slug === 'super-admin') {
                continue; // the super-admin role is protected from matrix edits
            }

            $names = $this->permissionsForRole(
                $role,
                Permission::whereIn('id', $submitted[$role->id] ?? [])->pluck('name')->all(),
            );
            $current = $role->permissions->pluck('name')->sort()->values()->all();
            $incoming = collect($names)->sort()->values()->all();

            if ($current !== $incoming) {
                $role->syncPermissions($names);
                $this->audit->record('roles.matrix', $role, null, [
                    'granted' => array_values(array_diff($incoming, $current)),
                    'revoked' => array_values(array_diff($current, $incoming)),
                ]);
                $changed++;
            }
        }

        if ($isSuperAdmin) {
            $request->session()->now('info', 'Your own super-admin permissions are not editable here.');
        }

        if ($changed === 0) {
            return back()->with('info', 'Nothing to change — the matrix already matched.');
        }

        return back()->with('success', "Permissions updated for {$changed} role(s).");
    }

    /**
     * Duplicate an existing role (permissions included).
     */
    public function cloneRole(Request $request, Role $role)
    {
        $sourcePermissions = $role->permissions;
        $this->permissionsForRole(null, $sourcePermissions->pluck('name')->all());

        $baseSlug = $role->slug.'-copy';
        $slug = $baseSlug;
        $suffix = 2;
        while (Role::where('slug', $slug)->orWhere('name', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $copy = Role::create([
            'name'        => $slug,
            'slug'        => $slug,
            'title'       => ($role->title ?? $role->name).' (copy)',
            'description' => $role->description,
            'guard_name'  => 'web',
            'sort_order'  => $role->sort_order + 1,
        ]);

        $this->syncPermissions($copy, $sourcePermissions->pluck('id')->all());

        $this->audit->record('roles.clone', $copy, null, [
            'cloned_from' => $role->slug,
            'permissions' => $copy->permissions->count(),
        ], 'roles');

        return redirect()->route('admin.roles.edit', $copy)->with('success', 'Role cloned — review and adjust its permissions.');
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'groups' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);

        $role = Role::create([
            'name'        => $validated['slug'],
            'slug'        => $validated['slug'],
            'title'       => $validated['title'] ?? $validated['slug'],
            'description' => $validated['description'] ?? null,
            'guard_name'  => 'web',
            'sort_order'  => $validated['sort_order'] ?? 0,
        ]);

        $this->syncPermissions($role, $validated['permissions'] ?? []);
        $this->audit->record('roles.create', $role);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function show(Role $role)
    {
        return redirect()->route('admin.roles.edit', $role);
    }

    public function edit(Role $role): View
    {
        abort_unless($role->slug !== 'super-admin' || auth()->user()->isSuperAdmin(), 403);

        return view('admin.roles.edit', [
            'role'   => $role->load('permissions'),
            'groups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($role->slug !== 'super-admin' || auth()->user()->isSuperAdmin(), 403);

        // Guard: the signed-in super-admin cannot edit their own role's
        // permissions (would otherwise lock everyone out in one save).
        if ($role->slug === 'super-admin' && auth()->user()->hasRole('super-admin')) {
            return back()->with('error', 'You cannot remove permissions from your own super-admin role.');
        }

        $validated = $this->validateRole($request, $role);

        $role->update([
            'title'       => $validated['title'] ?? $role->title,
            'description' => $validated['description'] ?? null,
            'sort_order'  => $validated['sort_order'] ?? 0,
        ]);

        if (! $role->is_system) {
            $role->update(['name' => $validated['slug'], 'slug' => $validated['slug']]);
        }

        $this->syncPermissions($role, $validated['permissions'] ?? []);
        $this->audit->record('roles.update', $role);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $this->audit->record('roles.delete', $role);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $slug = $request->input('slug');
        $validated = $request->validate([
            'slug'        => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'title'       => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'sometimes|integer|min:0',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $this->permissionsForRole($role, Permission::whereIn('id', $validated['permissions'] ?? [])->pluck('name')->all());

        return $validated;
    }

    private function permissionGroups()
    {
        $permissions = Permission::orderBy('module')->orderBy('action')->get();
        if (! auth()->user()->isSuperAdmin()) {
            $manageable = auth()->user()->getAllPermissions()->pluck('name')->all();
            $permissions = $permissions->whereIn('name', $manageable);
        }
        return $permissions->groupBy('module');
    }

    private function syncPermissions(Role $role, array $permissionIds): void
    {
        $names = Permission::whereIn('id', $permissionIds)->pluck('name')->all();
        $role->syncPermissions($this->permissionsForRole($role, $names));
    }

    /** A manager can edit held permissions; other existing role permissions are retained. */
    private function permissionsForRole(?Role $role, array $permissionNames): array
    {
        $actor = auth()->user();
        if ($actor?->isSuperAdmin()) {
            return array_values(array_unique($permissionNames));
        }

        $available = $actor?->getAllPermissions()->pluck('name')->all() ?? [];
        $current = $role?->permissions->pluck('name')->all() ?? [];
        $unauthorizedNew = array_diff(array_diff($permissionNames, $current), $available);
        if ($unauthorizedNew !== []) {
            abort(403, 'You cannot grant permissions you do not hold.');
        }

        return array_values(array_unique(array_merge($permissionNames, array_diff($current, $available))));
    }

    private function manageablePermissionIds($permissions): array
    {
        if (auth()->user()->isSuperAdmin()) {
            return $permissions->pluck('id')->all();
        }

        $manageable = auth()->user()->getAllPermissions()->pluck('name')->all();
        return $permissions->whereIn('name', $manageable)->pluck('id')->all();
    }
}
