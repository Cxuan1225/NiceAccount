<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'code' => (string) ($this->code ?? ''),
            'name' => (string) $this->name,
            'base_currency' => (string) $this->base_currency,
            'timezone' => (string) $this->timezone,
            'date_format' => (string) $this->date_format,
            'fy_start_month' => (int) $this->fy_start_month,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'address_line3' => $this->address_line3,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
