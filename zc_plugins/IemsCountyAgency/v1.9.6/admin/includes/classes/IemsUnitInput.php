<?php

declare(strict_types=1);

namespace Zencart\Plugins\Admin\IemsCountyAgency;

final class IemsUnitInput
{
    /**
     * @return array{
     *     values: array{
     *         agency_id: string,
     *         unit_identifier: string,
     *         unit_name: string,
     *         delivery_street_address: string,
     *         delivery_city: string,
     *         delivery_postcode: string,
     *         one_way_miles: ?string
     *     },
     *     errors: string[]
     * }
     */
    public static function validate(array $input): array
    {
        $agencyId = self::scalar($input['agency_id'] ?? null);
        $identifier = self::scalar($input['unit_identifier'] ?? null);
        $name = self::scalar($input['unit_name'] ?? null);
        $streetAddress = self::scalar($input['delivery_street_address'] ?? '');
        $city = self::scalar($input['delivery_city'] ?? '');
        $postcode = self::scalar($input['delivery_postcode'] ?? '');
        $mileageRaw = $input['one_way_miles'] ?? null;
        $mileage = self::mileage($mileageRaw);
        $errors = [];
        if ($mileage === false) {
            $errors[] = 'one_way_miles_invalid';
        }

        if ($agencyId === null || $agencyId === '') {
            $errors[] = 'agency_required';
        } elseif (self::positiveId($agencyId) === null) {
            $errors[] = 'agency_invalid';
        }

        if ($identifier === null || $identifier === '') {
            $errors[] = 'identifier_required';
        } elseif (strlen($identifier) > 10) {
            $errors[] = 'identifier_length';
        } elseif (preg_match('/^[A-Z0-9]+$/D', $identifier) !== 1) {
            $errors[] = 'identifier_format';
        }

        if ($name === null || $name === '') {
            $errors[] = 'name_required';
        } elseif (mb_strlen($name) > 128) {
            $errors[] = 'name_length';
        } elseif (
            !mb_check_encoding($name, 'UTF-8')
            || preg_match('/[<>\x00-\x1F\x7F]/u', $name) !== 0
        ) {
            $errors[] = 'name_format';
        }

        $addressFields = [
            'delivery_street_address' => $streetAddress,
            'delivery_city' => $city,
            'delivery_postcode' => $postcode,
        ];
        $nonblankAddressFields = array_filter(
            $addressFields,
            static fn (?string $value): bool => $value !== null && $value !== ''
        );
        if ($nonblankAddressFields !== [] && count($nonblankAddressFields) !== count($addressFields)) {
            $errors[] = 'address_incomplete';
        }

        foreach (
            [
                'street' => [$streetAddress, 128],
                'city' => [$city, 128],
                'postcode' => [$postcode, 64],
            ] as $field => [$value, $maxLength]
        ) {
            if ($value === null) {
                $errors[] = $field . '_format';
            } elseif (mb_strlen($value) > $maxLength) {
                $errors[] = $field . '_length';
            } elseif (
                $value !== ''
                && (
                    !mb_check_encoding($value, 'UTF-8')
                    || preg_match('/[<>\x00-\x1F\x7F]/u', $value) !== 0
                )
            ) {
                $errors[] = $field . '_format';
            }
        }

        return [
            'values' => [
                'agency_id' => $agencyId ?? '',
                'unit_identifier' => $identifier ?? '',
                'unit_name' => $name ?? '',
                'delivery_street_address' => $streetAddress ?? '',
                'delivery_city' => $city ?? '',
                'delivery_postcode' => $postcode ?? '',
                'one_way_miles' => $mileage === false
                    ? (is_string($mileageRaw) ? $mileageRaw : '')
                    : $mileage,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Keep decimal values as strings: no binary floating-point rounding or exponent coercion.
     */
    public static function mileage(mixed $value): string|null|false
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (
            (!is_string($value) && !is_int($value))
            || preg_match('/^[0-9]{1,5}(?:\.[0-9]{1,2})?$/D', (string)$value) !== 1
        ) {
            return false;
        }
        $parts = explode('.', (string)$value);
        $whole = ltrim($parts[0], '0');

        return ($whole === '' ? '0' : $whole) . '.' . str_pad($parts[1] ?? '', 2, '0');
    }

    public static function scalar(mixed $value): ?string
    {
        return is_scalar($value) ? trim((string)$value) : null;
    }

    public static function positiveId(mixed $value): ?int
    {
        $value = self::scalar($value);
        if ($value === null || $value === '' || !ctype_digit($value)) {
            return null;
        }

        $id = (int)$value;

        return $id > 0 ? $id : null;
    }
}
