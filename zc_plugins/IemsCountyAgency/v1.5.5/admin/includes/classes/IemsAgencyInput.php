<?php

declare(strict_types=1);

namespace Zencart\Plugins\Admin\IemsCountyAgency;

final class IemsAgencyInput
{
    /**
     * @return array{values: array{county_id: string, agency_identifier: string, agency_name: string}, errors: string[]}
     */
    public static function validate(array $input): array
    {
        $countyId = self::scalar($input['county_id'] ?? null);
        $identifier = self::scalar($input['agency_identifier'] ?? null);
        $name = self::scalar($input['agency_name'] ?? null);
        $errors = [];

        if ($countyId === null || $countyId === '') {
            $errors[] = 'county_required';
        } elseif (self::positiveId($countyId) === null) {
            $errors[] = 'county_invalid';
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

        return [
            'values' => [
                'county_id' => $countyId ?? '',
                'agency_identifier' => $identifier ?? '',
                'agency_name' => $name ?? '',
            ],
            'errors' => $errors,
        ];
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
