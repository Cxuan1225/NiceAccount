<?php

namespace App\Console\Commands;

use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoReset extends Command
{
    protected $signature = 'demo:reset';
    protected $description = 'Reset demo data and reseed the database';

    public function handle(): int
    {
        $tables = [
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'company_user',
            'chart_of_accounts',
            'journal_entry_lines',
            'journal_entries',
            'financial_years',
            'posting_periods',
            'customers',
            'audit_trails',
            'companies',
            'users',
            'password_reset_tokens',
            'sessions',
            'failed_jobs',
            'jobs',
            'job_batches',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        Artisan::call('db:seed', [], $this->getOutput());

        $this->newLine();
        $this->info(
            sprintf('Demo admin: %s / %s', AdminUserSeeder::DEMO_EMAIL, AdminUserSeeder::DEMO_PASSWORD),
        );

        return self::SUCCESS;
    }
}
