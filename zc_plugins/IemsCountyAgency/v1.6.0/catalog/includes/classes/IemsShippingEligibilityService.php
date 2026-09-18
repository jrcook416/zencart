<?php

declare(strict_types=1);

final class IemsShippingEligibilityService
{
    public function isPickupEligible(int $customerId): bool
    {
        return $this->getEligibility($customerId) !== null;
    }

    public function isDeliveryEligible(int $customerId): bool
    {
        $eligibility = $this->getEligibility($customerId);

        return $eligibility !== null && $eligibility['delivery_enabled'];
    }

    /**
     * @return array{delivery_enabled: bool}|null
     */
    private function getEligibility(int $customerId): ?array
    {
        global $db;

        if ($customerId <= 0 || !$this->deliveryFieldExists()) {
            return null;
        }

        $sql =
            "SELECT a.delivery_enabled
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

        $deliveryEnabled = (string)($result->fields['delivery_enabled'] ?? '');
        $result->MoveNext();
        if (!$result->EOF || !in_array($deliveryEnabled, ['0', '1'], true)) {
            return null;
        }

        return ['delivery_enabled' => $deliveryEnabled === '1'];
    }

    private function deliveryFieldExists(): bool
    {
        global $db;

        $field = $db->Execute(
            "SHOW COLUMNS FROM " . TABLE_IEMS_AGENCIES . " LIKE 'delivery_enabled'"
        );

        return !$field->EOF
            && ($field->fields['Field'] ?? null) === 'delivery_enabled'
            && strtolower((string)($field->fields['Type'] ?? '')) === 'tinyint(1)'
            && ($field->fields['Null'] ?? null) === 'NO'
            && (string)($field->fields['Default'] ?? '') === '0';
    }
}
