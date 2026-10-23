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

class StaffController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $staff = User::staff()
            ->with('roles')
            ->when($request->input('q'), function ($q, $term) {
                $q->where('email', 'like', "%{$term}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
            })
            ->when($request->input('role'), fn ($q, $role) => $q->whereHas('roles', fn ($q) => $q->where('slug', $role)))
            ->latest()
            ->paginate(20);

        return view('admin.staff.index', [
            'staff'   => $staff,
            'roles'   => Role::orderBy('sort_order')->get(),
            'filters' => $request->only(['q', 'role']),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff.create', ['roles' => Role::orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateStaff($request);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'password'   => $validated['password'],
            'type'       => UserType::from($validated['type']),
            'status'     => UserStatus::from($validated['status']),
        ]);

        $this->syncRoles($user, $validated['roles'] ?? []);
        $this->audit->record('staff.create', $user);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created.');
    }

    public function show(User $staff)
    {
        return redirect()->route('admin.staff.edit', $staff);
    }

    public function edit(User $staff): View
    {
        return view('admin.staff.edit', [
            'member' => $staff->load('roles'),
            'roles'  => Role::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $this->validateStaff($request, $staff);

        $staff->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'type'       => UserType::from($validated['type']),
            'status'     => UserStatus::from($validated['status']),
        ]);

        if (! empty($validated['password'])) {
            $staff->update(['password' => $validated['password']]);
        }

        $this->syncRoles($staff, $validated['roles'] ?? []);
        $this->audit->record('staff.update', $staff);

        return back()->with('success', 'Staff member updated.');
    }

    public function destroy(User $staff)
    {
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $this->audit->record('staff.delete', $staff);
        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member removed.');
    }

    private function validateStaff(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'first_name' => 'required|string|max:60',
            'last_name'  => 'required|string|max:60',
            'email'      => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone'      => 'nullable|string|max:20',
            'password'   => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'type'       => 'required|in:staff,admin',
            'status'     => 'required|in:active,suspended,invited,deactivated',
            'roles'      => 'array',
            'roles.*'    => 'exists:roles,id',
        ]);
    }

    private function syncRoles(User $user, array $roleIds): void
    {
        if (empty($roleIds)) {
            return;
        }
        $names = Role::whereIn('id', $roleIds)->pluck('name')->all();
        $user->syncRoles($names);
    }
}
