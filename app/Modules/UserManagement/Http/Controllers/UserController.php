<?php

namespace App\Modules\UserManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Modules\UserManagement\Http\Requests\UserStoreRequest;
use App\Modules\UserManagement\Http\Requests\UserUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = auth()->id();

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->role_id = $data['role_id'];
        $user->is_active = $data['is_active'] ?? true;
        $user->password = $data['password'];
        $user->created_by = $actor;
        $user->updated_by = $actor;
        $user->save();

        return redirect()->route('admin.users.index')->with('status', 'User created');
    }

    public function edit(int $user): View
    {
        $model = User::findOrFail($user);
        $users = User::with('role')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'editUser' => $model,
        ]);
    }

    public function update(UserUpdateRequest $request, int $user): RedirectResponse
    {
        $model = User::findOrFail($user);
        $data = $request->validated();
        $actor = auth()->id();

        $model->name = $data['name'];
        $model->email = $data['email'];
        $model->phone = $data['phone'] ?? null;
        $model->role_id = $data['role_id'];
        $model->is_active = $data['is_active'] ?? $model->is_active;
        if (!empty($data['password'])) {
            $model->password = $data['password'];
        }
        $model->updated_by = $actor;
        $model->save();

        return redirect()->route('admin.users.index')->with('status', 'User updated');
    }

    public function destroy(int $user): RedirectResponse
    {
        $model = User::findOrFail($user);
        $model->delete();

        return redirect()->route('admin.users.index')->with('status', 'User removed');
    }
}
