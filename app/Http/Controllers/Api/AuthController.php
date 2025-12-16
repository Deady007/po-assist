<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function __construct(private JwtService $jwt) {}

    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $user = User::with('role')->where('email', $data['email'])->first();
        if (!$user || !$user->is_active || !Hash::check($data['password'], $user->password)) {
            return $this->failure([['code' => 'INVALID_CREDENTIALS', 'message' => 'Invalid email or password']], 401);
        }

        $token = $this->jwt->generate($user);

        return $this->success([
            'access_token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
            ],
        ]);
    }
}
