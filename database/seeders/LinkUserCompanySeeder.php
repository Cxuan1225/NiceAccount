<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class LinkUserCompanySeeder extends Seeder {
    public function run() : void {
        $user    = User::first();
        $company = Company::first();

        if (!$user || !$company) {
            $this->command->warn('No user or company found.');
            return;
        }

        // Check if already linked
        if ($user->companies()->where('companies.id', $company->id)->exists()) {
            $this->command->info("User {$user->id} already linked to company {$company->id}");
            return;
        }

        // Link user to company
        $user->companies()->attach($company->id, [
            'status'     => 'active',
            'is_default' => true,
            'joined_at'  => now(),
        ]);

        // Set as active company
        $user->forceFill([ 'active_company_id' => $company->id ])->save();
        if (!$user->company_id) {
            $user->forceFill([ 'company_id' => $company->id ])->save();
        }

        $this->command->info("User {$user->id} ({$user->name}) linked to company {$company->id} ({$company->name})");
    }
}
