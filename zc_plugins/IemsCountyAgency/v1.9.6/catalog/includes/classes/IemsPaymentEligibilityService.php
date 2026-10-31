<?php

declare(strict_types=1);

final class IemsPaymentEligibilityService
{
    public const MODE_INVOICE = 'invoice';
    public const MODE_IEMS_UNIT = 'iems_unit';

    public function isEligible(int $customerId, string $requiredMode): bool
    {
        if ($customerId <= 0 || !in_array($requiredMode, self::paymentModes(), true)) {
            return false;
        }

        $paymentMode = $this->getPaymentModeForCustomer($customerId);

        return $paymentMode !== null && hash_equals($requiredMode, $paymentMode);
    }

    /**
     * @return string[]
     */
    public static function paymentModes(): array
    {
        return [
            self::MODE_INVOICE,
            self::MODE_IEMS_UNIT,
        ];
    }

    private function getPaymentModeForCustomer(int $customerId): ?string
    {
        global $db;

        if (!$this->paymentModeSchemaReady()) {
            return null;
        }

        $sql =
            "SELECT f.county_ID AS affiliation_county_ID,
                    f.agency_ID AS affiliation_agency_ID,
                    c.county_ID,
                    a.agency_ID,
                    a.payment_mode
               FROM " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . " f
               JOIN " . TABLE_IEMS_COUNTIES . " c
                 ON c.county_ID = f.county_ID
                AND c.status = 1
               JOIN " . TABLE_IEMS_AGENCIES . " a
                 ON a.agency_ID = f.agency_ID
                AND a.county_ID = f.county_ID
                AND a.status = 1
              WHERE f.customer_id = :customerId
              LIMIT 2";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $result = $db->Execute($sql);
        if ($result->EOF) {
            return null;
        }

        $fields = $result->fields;
        $paymentMode = is_string($fields['payment_mode'] ?? null)
            ? $fields['payment_mode']
            : '';
        if (
            !$this->isPositiveDatabaseId($fields['affiliation_county_ID'] ?? null)
            || !$this->isPositiveDatabaseId($fields['affiliation_agency_ID'] ?? null)
            || !$this->isPositiveDatabaseId($fields['county_ID'] ?? null)
            || !$this->isPositiveDatabaseId($fields['agency_ID'] ?? null)
            || (int)$fields['affiliation_county_ID'] !== (int)$fields['county_ID']
            || (int)$fields['affiliation_agency_ID'] !== (int)$fields['agency_ID']
            || !in_array($paymentMode, self::paymentModes(), true)
        ) {
            return null;
        }

        $result->MoveNext();

        return $result->EOF ? $paymentMode : null;
    }

    private function paymentModeSchemaReady(): bool
    {
        global $db;

        $field = $db->Execute(
            "SHOW COLUMNS FROM " . TABLE_IEMS_AGENCIES . " LIKE 'payment_mode'"
        );

        return !$field->EOF
            && ($field->fields['Field'] ?? null) === 'payment_mode'
            && strtolower((string)($field->fields['Type'] ?? '')) === 'varchar(16)'
            && ($field->fields['Null'] ?? null) === 'NO'
            && ($field->fields['Default'] ?? null) === self::MODE_INVOICE;
    }

    private function isPositiveDatabaseId(mixed $value): bool
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value)))
            && (int)$value > 0;
    }
}
