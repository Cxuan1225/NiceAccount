<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class BackfillCompanyDefaults extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-company-defaults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        Company::whereNull('code')->each(function ($company) {
            $name = (string) $company->name;
            $normalized = preg_replace('/\s+/', '', $name) ?? '';
            $company->update([
                'code'           => strtoupper(substr($normalized, 0, 10)),
                'timezone'       => 'Asia/Kuala_Lumpur',
                'date_format'    => 'd/m/Y',
                'fy_start_month' => 1,
                'is_active'      => true,
            ]);
        });

        return self::SUCCESS;
    }
}
