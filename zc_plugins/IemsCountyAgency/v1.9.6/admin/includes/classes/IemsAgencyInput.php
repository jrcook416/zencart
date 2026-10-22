<?php

declare(strict_types=1);

namespace Zencart\Plugins\Admin\IemsCountyAgency;

final class IemsAgencyInput
{
    public const PAYMENT_MODE_INVOICE = 'invoice';
    public const PAYMENT_MODE_IEMS_UNIT = 'iems_unit';

    /**
     * @return array{
     *     values: array{
     *         county_id: string,
     *         agency_identifier: string,
     *         agency_name: string,
     *         delivery_enabled: string,
     *         payment_mode: string
     *     },
     *     errors: string[]
     * }
     */
    public static function validate(array $input): array
    {
        $countyId = self::scalar($input['county_id'] ?? null);
        $identifier = self::scalar($input['agency_identifier'] ?? null);
        $name = self::scalar($input['agency_name'] ?? null);
        $deliveryEnabled = self::scalar($input['delivery_enabled'] ?? '0');
        $paymentMode = self::scalar($input['payment_mode'] ?? self::PAYMENT_MODE_INVOICE);
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

        if (!in_array($deliveryEnabled, ['0', '1'], true)) {
            $errors[] = 'delivery_enabled_invalid';
        }

        if (!in_array($paymentMode, self::paymentModes(), true)) {
            $errors[] = 'payment_mode_invalid';
        }

        return [
            'values' => [
                'county_id' => $countyId ?? '',
                'agency_identifier' => $identifier ?? '',
                'agency_name' => $name ?? '',
                'delivery_enabled' => in_array($deliveryEnabled, ['0', '1'], true)
                    ? $deliveryEnabled
                    : '0',
                'payment_mode' => in_array($paymentMode, self::paymentModes(), true)
                    ? $paymentMode
                    : self::PAYMENT_MODE_INVOICE,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * @return string[]
     */
    public static function paymentModes(): array
    {
        return [
            self::PAYMENT_MODE_INVOICE,
            self::PAYMENT_MODE_IEMS_UNIT,
        ];
    }

    /**
     * Resolve shipping using county numbers, never display names or database IDs.
     * A stale outside selection on entry to 049 resets to Marion; an explicit IEMS selection is allowed.
     */
    public static function shippingCategory(
        array $input,
        string $countyNumber,
        ?array $existing = null
    ): ?string {
        $categories = ['marion', 'iems', 'out_of_county'];
        $countyNumber = self::countyNumber($countyNumber);
        if ($countyNumber === null) {
            return null;
        }
        $oldCounty = null;
        if ($existing !== null) {
            $oldCategory = $existing['shipping_category'] ?? null;
            $oldCounty = self::countyNumber($existing['county_number'] ?? null);
            if (
                $oldCounty === null
                || !in_array($oldCategory, $categories, true)
                || ($oldCounty === '049'
                    ? !in_array($oldCategory, ['marion', 'iems'], true)
                    : $oldCategory !== 'out_of_county')
            ) {
                return null;
            }
        }
        $posted = $input['shipping_category'] ?? null;
        if (array_key_exists('shipping_category', $input) && !in_array($posted, $categories, true)) {
            return null;
        }
        if ($countyNumber !== '049') {
            return 'out_of_county';
        }
        if ($posted === 'out_of_county') {
            return $existing !== null && $oldCounty !== '049' ? 'marion' : null;
        }

        return $posted ?? ($oldCounty === '049'
            ? $existing['shipping_category']
            : 'marion');
    }

    public static function countyNumber(mixed $value): ?string
    {
        if (
            !is_string($value)
            || preg_match('/^[0-9]{1,3}$/D', $value) !== 1
            || (int)$value < 1
            || (int)$value > 92
        ) {
            return null;
        }

        return str_pad((string)(int)$value, 3, '0', STR_PAD_LEFT);
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
