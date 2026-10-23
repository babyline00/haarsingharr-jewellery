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
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.roles.index', compact('roles'));
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
        return view('admin.roles.edit', [
            'role'   => $role->load('permissions'),
            'groups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
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

        return $request->validate([
            'slug'        => ['required', 'string', 'max:60', 'regex:/^[a-z0-9\-]+$/', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'title'       => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'sometimes|integer|min:0',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);
    }

    private function permissionGroups()
    {
        return Permission::orderBy('module')->orderBy('action')->get()->groupBy('module');
    }

    private function syncPermissions(Role $role, array $permissionIds): void
    {
        $names = Permission::whereIn('id', $permissionIds)->pluck('name')->all();
        $role->syncPermissions($names);
    }
}
