<?php

declare(strict_types=1);

class IemsCheckoutUnitService
{
    public const SESSION_KEY = 'iems_checkout_unit';
    public const ERROR_MISSING = 'missing';
    public const ERROR_MALFORMED = 'malformed';
    public const ERROR_AFFILIATION = 'affiliation';
    public const ERROR_UNIT = 'unit';
    public const ERROR_LABEL_LENGTH = 'label_length';
    public const ERROR_CART = 'cart';

    /**
     * @return array<int, array{id: int, text: string}>
     */
    public function getActiveUnitsForCustomer(int $customerId): array
    {
        global $db;

        if ($customerId <= 0) {
            return [];
        }

        $sql =
            "SELECT u.unit_ID, u.unit_identifier, u.unit_name
               FROM " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . " f
               JOIN " . TABLE_IEMS_COUNTIES . " c
                 ON c.county_ID = f.county_ID
                AND c.status = 1
               JOIN " . TABLE_IEMS_AGENCIES . " a
                 ON a.agency_ID = f.agency_ID
                AND a.county_ID = f.county_ID
                AND a.status = 1
               JOIN " . TABLE_IEMS_UNITS . " u
                 ON u.agency_ID = a.agency_ID
                AND u.county_ID = c.county_ID
                AND u.status = 1
              WHERE f.customer_id = :customerId
              ORDER BY u.unit_identifier, u.unit_name";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $result = $db->Execute($sql);

        $units = [];
        while (!$result->EOF) {
            $units[] = [
                'id' => (int)$result->fields['unit_ID'],
                'text' => trim($result->fields['unit_identifier'] . ' ' . $result->fields['unit_name']),
            ];
            $result->MoveNext();
        }

        return $units;
    }

    /**
     * @return array{valid: bool, error: string, unit_id: int, label: string}
     */
    public function validateSelection(int $customerId, mixed $rawUnitId): array
    {
        $unitId = $this->parsePositiveId($rawUnitId);
        if ($rawUnitId === null || $rawUnitId === '') {
            return $this->invalid(self::ERROR_MISSING);
        }
        if ($unitId === null) {
            return $this->invalid(self::ERROR_MALFORMED);
        }

        return $this->validateUnitForCustomer($customerId, $unitId);
    }

    /**
     * @return array{valid: bool, error: string, unit_id: int, label: string}
     */
    public function saveSelection(int $customerId, mixed $rawUnitId, string $flow): array
    {
        $selection = $this->validateSelection($customerId, $rawUnitId);
        if (!$selection['valid']) {
            $this->clearSelection();
            return $selection;
        }

        $cartId = $this->currentCartId();
        if ($cartId === '') {
            $this->clearSelection();
            return $this->invalid(self::ERROR_CART);
        }

        $_SESSION[self::SESSION_KEY] = [
            'customer_id' => $customerId,
            'cart_id' => $cartId,
            'unit_id' => $selection['unit_id'],
            'flow' => $flow,
        ];

        return $selection;
    }

    /**
     * @return array{valid: bool, error: string, unit_id: int, label: string}
     */
    public function getValidatedSelection(int $customerId): array
    {
        $state = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($state)) {
            return $this->invalid(self::ERROR_MISSING);
        }

        $stateCustomerId = $this->parsePositiveId($state['customer_id'] ?? null);
        $unitId = $this->parsePositiveId($state['unit_id'] ?? null);
        $cartId = is_scalar($state['cart_id'] ?? null) ? (string)$state['cart_id'] : '';
        if (
            $stateCustomerId !== $customerId
            || $unitId === null
            || $cartId === ''
            || !hash_equals($cartId, $this->currentCartId())
        ) {
            $this->clearSelection();
            return $this->invalid(self::ERROR_CART);
        }

        $selection = $this->validateUnitForCustomer($customerId, $unitId);
        if (!$selection['valid']) {
            $this->clearSelection();
        }

        return $selection;
    }

    public function getSelectionFlow(): string
    {
        $flow = $_SESSION[self::SESSION_KEY]['flow'] ?? '';
        return is_string($flow) && in_array($flow, ['standard', 'opc'], true) ? $flow : 'standard';
    }

    public function clearSelection(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function parsePositiveId(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string)$value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        $id = (int)$value;
        return $id > 0 ? $id : null;
    }

    /**
     * @return array{valid: bool, error: string, unit_id: int, label: string}
     */
    private function validateUnitForCustomer(int $customerId, int $unitId): array
    {
        global $db;

        if ($customerId <= 0) {
            return $this->invalid(self::ERROR_AFFILIATION);
        }

        $affiliationSql =
            "SELECT f.county_ID, f.agency_ID
               FROM " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . " f
              WHERE f.customer_id = :customerId
              LIMIT 1";
        $affiliationSql = $db->bindVars($affiliationSql, ':customerId', $customerId, 'integer');
        $affiliation = $db->Execute($affiliationSql);
        if ($affiliation->EOF) {
            return $this->invalid(self::ERROR_AFFILIATION);
        }

        $sql =
            "SELECT c.county_number, a.agency_identifier, u.unit_identifier, u.unit_name
               FROM " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . " f
               JOIN " . TABLE_IEMS_COUNTIES . " c
                 ON c.county_ID = f.county_ID
                AND c.status = 1
               JOIN " . TABLE_IEMS_AGENCIES . " a
                 ON a.agency_ID = f.agency_ID
                AND a.county_ID = f.county_ID
                AND a.status = 1
               JOIN " . TABLE_IEMS_UNITS . " u
                 ON u.unit_ID = :unitId
                AND u.agency_ID = a.agency_ID
                AND u.county_ID = c.county_ID
                AND u.status = 1
              WHERE f.customer_id = :customerId
              LIMIT 1";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $sql = $db->bindVars($sql, ':unitId', $unitId, 'integer');
        $result = $db->Execute($sql);
        if ($result->EOF) {
            return $this->invalid(self::ERROR_UNIT);
        }

        $label = trim(implode(' ', [
            $result->fields['county_number'],
            $result->fields['agency_identifier'],
            $result->fields['unit_identifier'],
            $result->fields['unit_name'],
        ]));
        $labelLength = function_exists('mb_strlen') ? mb_strlen($label, 'UTF-8') : strlen($label);
        if ($label === '' || $labelLength > 128) {
            return $this->invalid(self::ERROR_LABEL_LENGTH);
        }

        return [
            'valid' => true,
            'error' => '',
            'unit_id' => $unitId,
            'label' => $label,
        ];
    }

    private function currentCartId(): string
    {
        $cartId = $_SESSION['cart']->cartID ?? $_SESSION['cartID'] ?? '';
        return is_scalar($cartId) ? (string)$cartId : '';
    }

    /**
     * @return array{valid: bool, error: string, unit_id: int, label: string}
     */
    private function invalid(string $error): array
    {
        return [
            'valid' => false,
            'error' => $error,
            'unit_id' => 0,
            'label' => '',
        ];
    }
}
