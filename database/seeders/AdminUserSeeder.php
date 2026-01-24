<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public const DEMO_EMAIL = 'admin@gmail.com';
    public const DEMO_PASSWORD = 'Admin123';

    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => self::DEMO_EMAIL],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at' => now(),
            ],
        );

        $company = Company::query()->orderBy('id')->first();
        if ($company && !$admin->company_id) {
            $admin->forceFill([
                'company_id' => (int) $company->id,
                'active_company_id' => (int) $company->id,
            ])->save();
        }

        $role = Role::where('name', 'Super Admin')->first();
        if ($role && !$admin->hasRole($role)) {
            $admin->assignRole($role);
        }
    }
}
