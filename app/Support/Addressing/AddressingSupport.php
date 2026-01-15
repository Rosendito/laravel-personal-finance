<?php

declare(strict_types=1);

namespace App\Support\Addressing;

use const SORT_FLAG_CASE;
use const SORT_NATURAL;

use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use Throwable;

final class AddressingSupport
{
    /**
     * @var array<string, array<int, string>>
     */
    private static array $requiredFieldsCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    private static array $countryOptionsCache = [];

    /**
     * @return array<int, string>
     */
    public static function requiredFields(?string $countryCode): array
    {
        $countryCode = self::normalizeCountryCode($countryCode);

        if ($countryCode === null) {
            return [];
        }

        if (array_key_exists($countryCode, self::$requiredFieldsCache)) {
            return self::$requiredFieldsCache[$countryCode];
        }

        try {
            /** @var array<int, string> $requiredFields */
            $requiredFields = new AddressFormatRepository()
                ->get($countryCode)
                ->getRequiredFields();

            return self::$requiredFieldsCache[$countryCode] = $requiredFields;
        } catch (Throwable) {
            return self::$requiredFieldsCache[$countryCode] = [];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function requiredColumns(?string $countryCode): array
    {
        $requiredFields = self::requiredFields($countryCode);
        $requiredColumns = [];

        foreach ($requiredFields as $requiredField) {
            $column = self::addressingFieldToColumn($requiredField);

            if ($column === null) {
                continue;
            }

            $requiredColumns[] = $column;
        }

        return array_values(array_unique($requiredColumns));
    }

    public static function isColumnRequired(?string $countryCode, string $column): bool
    {
        $addressingField = self::columnToAddressingField($column);

        if ($addressingField === null) {
            return false;
        }

        return in_array($addressingField, self::requiredFields($countryCode), true);
    }

    /**
     * @return array<string, string>
     */
    public static function countryOptions(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        if (array_key_exists($locale, self::$countryOptionsCache)) {
            return self::$countryOptionsCache[$locale];
        }

        /** @var array<string, string> $options */
        $options = new CountryRepository()->getList($locale);

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return self::$countryOptionsCache[$locale] = $options;
    }

    private static function normalizeCountryCode(?string $countryCode): ?string
    {
        if ($countryCode === null) {
            return null;
        }

        $countryCode = mb_strtoupper(mb_trim($countryCode));

        if ($countryCode === '') {
            return null;
        }

        return $countryCode;
    }

    private static function columnToAddressingField(string $column): ?string
    {
        return match ($column) {
            'administrative_area' => 'administrativeArea',
            'locality' => 'locality',
            'dependent_locality' => 'dependentLocality',
            'postal_code' => 'postalCode',
            'sorting_code' => 'sortingCode',
            'address_line1' => 'addressLine1',
            'address_line2' => 'addressLine2',
            'address_line3' => 'addressLine3',
            'organization' => 'organization',
            'given_name' => 'givenName',
            'additional_name' => 'additionalName',
            'family_name' => 'familyName',
            default => null,
        };
    }

    private static function addressingFieldToColumn(string $addressingField): ?string
    {
        return match ($addressingField) {
            'administrativeArea' => 'administrative_area',
            'locality' => 'locality',
            'dependentLocality' => 'dependent_locality',
            'postalCode' => 'postal_code',
            'sortingCode' => 'sorting_code',
            'addressLine1' => 'address_line1',
            'addressLine2' => 'address_line2',
            'addressLine3' => 'address_line3',
            'organization' => 'organization',
            'givenName' => 'given_name',
            'additionalName' => 'additional_name',
            'familyName' => 'family_name',
            default => null,
        };
    }
}
