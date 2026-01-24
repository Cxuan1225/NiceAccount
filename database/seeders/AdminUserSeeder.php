<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public const DEMO_EMAIL = 'admin@demo.test';
    public const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $admin = User::firstOrCreate(
            [ 'email' => self::DEMO_EMAIL ],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ],
        );

        $role = Role::where('name', 'Super Admin')->first();
        if ($role && !$admin->hasRole($role)) {
            $admin->assignRole($role);
        }
    }
}
