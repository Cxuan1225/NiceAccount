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
        $name = $request->input('name');
        $baseCurrency = $request->input('base_currency');
        $timezone = $request->input('timezone');
        $dateFormat = $request->input('date_format');
        $fyStartMonthRaw = $request->input('fy_start_month');
        $email = $request->input('email');
        $phone = $request->input('phone');
        $addressLine1 = $request->input('address_line1');
        $addressLine2 = $request->input('address_line2');
        $addressLine3 = $request->input('address_line3');
        $city = $request->input('city');
        $state = $request->input('state');
        $postcode = $request->input('postcode');
        $country = $request->input('country');

        return new self(
            name: is_string($name) ? $name : '',
            baseCurrency: is_string($baseCurrency) ? $baseCurrency : '',
            timezone: is_string($timezone) ? $timezone : '',
            dateFormat: is_string($dateFormat) ? $dateFormat : '',
            fyStartMonth: is_numeric($fyStartMonthRaw) ? (int) $fyStartMonthRaw : 1,
            email: is_string($email) ? $email : null,
            phone: is_string($phone) ? $phone : null,
            addressLine1: is_string($addressLine1) ? $addressLine1 : null,
            addressLine2: is_string($addressLine2) ? $addressLine2 : null,
            addressLine3: is_string($addressLine3) ? $addressLine3 : null,
            city: is_string($city) ? $city : null,
            state: is_string($state) ? $state : null,
            postcode: is_string($postcode) ? $postcode : null,
            country: is_string($country) ? $country : null,
        );
    }
}
