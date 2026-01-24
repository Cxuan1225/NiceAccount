<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'code' => Str::upper(Str::substr(preg_replace('/\s+/', '', $name), 0, 10)),
            'name' => $name,
            'base_currency' => 'MYR',
            'timezone' => 'Asia/Kuala_Lumpur',
            'date_format' => 'd/m/Y',
            'fy_start_month' => 1,
            'is_active' => true,
        ];
    }
}
