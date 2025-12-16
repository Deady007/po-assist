<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\User;
use App\Modules\UserManagement\Http\Requests\UserStoreRequest;
use App\Modules\UserManagement\Http\Requests\UserUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $query = User::with('role');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        $paginator = $query->orderBy('name')->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $user = new User($data);
        $user->password = $data['password'];
        $user->save();

        $this->audit->logModel($user, AuditLogger::ACTION_CREATE);

        return $this->success(['user' => $user->fresh('role')], status: 201);
    }

    public function show(int $user)
    {
        $model = User::with('role')->findOrFail($user);
        return $this->success(['user' => $model]);
    }

    public function update(UserUpdateRequest $request, int $user)
    {
        $model = User::findOrFail($user);
        $data = $request->validated();

        $model->fill($data);
        if (!empty($data['password'])) {
            $model->password = $data['password'];
        }
        $model->save();

        $this->audit->logModel($model, AuditLogger::ACTION_UPDATE);

        return $this->success(['user' => $model->fresh('role')]);
    }

    public function activate(int $user)
    {
        $model = User::findOrFail($user);
        $model->is_active = !$model->is_active;
        $model->save();

        $action = $model->is_active ? AuditLogger::ACTION_ACTIVATE : AuditLogger::ACTION_DEACTIVATE;
        $this->audit->logModel($model, $action);

        return $this->success(['user' => $model]);
    }

    public function destroy(int $user)
    {
        $model = User::findOrFail($user);
        if ($model->is_active) {
            $model->is_active = false;
            $model->save();
            $this->audit->logModel($model, AuditLogger::ACTION_DEACTIVATE);
        }

        return $this->success(['message' => 'User deactivated']);
    }
}
