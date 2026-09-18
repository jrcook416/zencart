<?php

declare(strict_types=1);

namespace Zencart\Plugins\Admin\IemsCountyAgency;

final class IemsUnitInput
{
    /**
     * @return array{values: array{agency_id: string, unit_identifier: string, unit_name: string}, errors: string[]}
     */
    public static function validate(array $input): array
    {
        $agencyId = self::scalar($input['agency_id'] ?? null);
        $identifier = self::scalar($input['unit_identifier'] ?? null);
        $name = self::scalar($input['unit_name'] ?? null);
        $errors = [];

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

        return [
            'values' => [
                'agency_id' => $agencyId ?? '',
                'unit_identifier' => $identifier ?? '',
                'unit_name' => $name ?? '',
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
