<?php

namespace Database\Seeders;

use Database\Seeders\Security\RolesAndPermissionsSeeder as SecurityRolesAndPermissionsSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SecurityRolesAndPermissionsSeeder::class,
            CompanySeeder::class,
            AdminUserSeeder::class,
            ChartOfAccountsSeeder::class,
            LinkUserCompanySeeder::class,
        ]);
    }
}
