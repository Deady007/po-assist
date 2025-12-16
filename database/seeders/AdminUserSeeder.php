<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $password = env('ADMIN_PASSWORD', 'Admin@123');
        $name = env('ADMIN_NAME', 'Admin');

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = new User([
                'name' => $name,
                'email' => $email,
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]);
        }

        $user->password = $password;
        $user->save();
    }
}
