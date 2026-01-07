<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'JC Clothing Sdn Bhd',
                'currency' => 'MYR',
            ]
        );
    }
}
