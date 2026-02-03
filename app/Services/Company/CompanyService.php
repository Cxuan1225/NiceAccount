<?php

namespace App\Services\Company;

use App\DTOs\Company\CompanyData;
use App\DTOs\Company\CompanyIndexFiltersDTO;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class CompanyService
{
    /**
     * @return LengthAwarePaginator<int, Company>
     */
    public function list(User $actor, CompanyIndexFiltersDTO $filters): LengthAwarePaginator
    {
        return Company::query()
            ->when(!$actor->isSuperAdmin(), function ($query) use ($actor) {
                $query->where('id', (int) $actor->company_id);
            })
            ->when($filters->q !== '', function ($query) use ($filters) {
                $query->where(function ($sub) use ($filters) {
                    $sub->where('name', 'like', "%{$filters->q}%")
                        ->orWhere('code', 'like', "%{$filters->q}%");
                });
            })
            ->orderBy('name')
            ->paginate($filters->perPage)
            ->withQueryString();
    }

    public function create(User $actor, CompanyData $dto): Company
    {
        $normalizedName = preg_replace('/\s+/', '', $dto->name) ?? '';
        $code = strtoupper(substr($normalizedName, 0, 10));

        $company = Company::create([
            'code' => $code ?: Str::upper(Str::random(6)),
            'name' => $dto->name,
            'base_currency' => $dto->baseCurrency,
            'timezone' => $dto->timezone,
            'date_format' => $dto->dateFormat,
            'fy_start_month' => $dto->fyStartMonth,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address_line1' => $dto->addressLine1,
            'address_line2' => $dto->addressLine2,
            'address_line3' => $dto->addressLine3,
            'city' => $dto->city,
            'state' => $dto->state,
            'postcode' => $dto->postcode,
            'country' => $dto->country ?? 'MY',
            'is_active' => true,
        ]);

        if (!$actor->isSuperAdmin() && !$actor->company_id) {
            $actor->forceFill([
                'company_id' => (int) $company->id,
                'active_company_id' => (int) $company->id,
            ])->save();
        }

        if (!$actor->companies()->where('companies.id', $company->id)->exists()) {
            $actor->companies()->attach($company->id, [
                'status' => 'active',
                'is_default' => true,
                'joined_at' => now(),
            ]);
        }

        return $company;
    }

    public function update(User $actor, Company $company, CompanyData $dto): Company
    {
        if (!$actor->isSuperAdmin() && (int) $actor->company_id !== (int) $company->id) {
            abort(403);
        }

        $company->update([
            'name' => $dto->name,
            'base_currency' => $dto->baseCurrency,
            'timezone' => $dto->timezone,
            'date_format' => $dto->dateFormat,
            'fy_start_month' => $dto->fyStartMonth,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'address_line1' => $dto->addressLine1,
            'address_line2' => $dto->addressLine2,
            'address_line3' => $dto->addressLine3,
            'city' => $dto->city,
            'state' => $dto->state,
            'postcode' => $dto->postcode,
            'country' => $dto->country ?? 'MY',
        ]);

        return $company;
    }

    public function delete(User $actor, Company $company): void
    {
        if (!$actor->isSuperAdmin() && (int) $actor->company_id !== (int) $company->id) {
            abort(403);
        }

        $company->delete();
    }
}
