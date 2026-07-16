<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\UserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('organization')
            ->orderBy('name')
            ->paginate(20);

        return view('coordinator.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('coordinator.users.create', [
            'user' => new User(['is_active' => true]),
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = new User();
        $this->fillUser($user, $request);
        $user->save();

        return redirect()->route('coordinator.users.index')
            ->with('success', 'تمت إضافة المستخدم.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('coordinator.users.edit', [
            'user' => $user,
            'organizations' => Organization::orderBy('name')->get(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->fillUser($user, $request);
        $user->save();

        return redirect()->route('coordinator.users.index')
            ->with('success', 'تم تحديث المستخدم.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('coordinator.users.index')
            ->with('success', 'تم حذف المستخدم.');
    }

    /**
     * Apply request data to a user. `role` is set explicitly here (never mass
     * assignable); the password cast hashes it; org is only kept for field
     * supervisors; a blank password on edit leaves it unchanged.
     */
    private function fillUser(User $user, UserRequest $request): void
    {
        $data = $request->validated();
        $role = $request->enum('role', UserRole::class);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'student_number' => $role === UserRole::Student ? ($data['student_number'] ?? null) : null,
            'organization_id' => $role === UserRole::FieldSupervisor ? ($data['organization_id'] ?? null) : null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->role = $role;

        if (! empty($data['password'])) {
            $user->password = $data['password']; // 'hashed' cast handles bcrypt
        }
    }
}
