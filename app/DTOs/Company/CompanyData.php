<?php

namespace App\DTOs\Company;

use Illuminate\Http\Request;

class CompanyData
{
    public function __construct(
        public readonly string $name,
        public readonly string $baseCurrency,
        public readonly string $timezone,
        public readonly string $dateFormat,
        public readonly int $fyStartMonth,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly ?string $addressLine3,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $postcode,
        public readonly ?string $country,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: (string) $request->input('name'),
            baseCurrency: (string) $request->input('base_currency'),
            timezone: (string) $request->input('timezone'),
            dateFormat: (string) $request->input('date_format'),
            fyStartMonth: (int) $request->input('fy_start_month'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            addressLine1: $request->input('address_line1'),
            addressLine2: $request->input('address_line2'),
            addressLine3: $request->input('address_line3'),
            city: $request->input('city'),
            state: $request->input('state'),
            postcode: $request->input('postcode'),
            country: $request->input('country'),
        );
    }
}
