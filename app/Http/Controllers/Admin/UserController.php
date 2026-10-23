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
                $q->where('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
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
        return view('admin.users.edit', [
            'user'  => $user->load('roles'),
            'roles' => Role::orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
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

        $user->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'] ?? null,
            'type'       => UserType::from($validated['type']),
            'status'     => UserStatus::from($validated['status']),
        ]);

        if (! empty($validated['password'])) {
            $user->update(['password' => $validated['password']]);
        }

        $names = Role::whereIn('id', $validated['roles'] ?? [])->pluck('name')->all();
        $user->syncRoles($names);

        $this->audit->record('users.update', $user);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $this->audit->record('users.delete', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User removed.');
    }
}
