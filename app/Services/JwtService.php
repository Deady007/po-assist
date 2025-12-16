<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use RuntimeException;

class JwtService
{
    private string $algo = 'HS256';

    private function secret(): string
    {
        $secret = Config::get('app.jwt_secret') ?: env('JWT_SECRET');
        if (!$secret) {
            $secret = Config::get('app.key');
        }

        if (str_starts_with((string) $secret, 'base64:')) {
            $secret = base64_decode(substr((string) $secret, 7));
        }

        return $secret;
    }

    public function generate(User $user): string
    {
        $now = Date::now();
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role?->name,
            'iat' => $now->unix(),
            'exp' => $now->copy()->addHours(8)->unix(),
        ];

        return JWT::encode($payload, $this->secret(), $this->algo);
    }

    public function decode(string $token): object
    {
        try {
            return JWT::decode($token, new Key($this->secret(), $this->algo));
        } catch (\Throwable $e) {
            throw new RuntimeException('Invalid token', previous: $e);
        }
    }
}
