<?php

declare(strict_types=1);

class IemsCheckoutUnitService
{
    public const SESSION_KEY = 'iems_checkout_unit';
    public const SELECTION_TYPE_UNIT = 'unit';
    public const SELECTION_TYPE_AGENCY = 'agency';
    public const AGENCY_FALLBACK_TOKEN = 'agency-fallback';
    public const ERROR_MISSING = 'missing';
    public const ERROR_MALFORMED = 'malformed';
    public const ERROR_AFFILIATION = 'affiliation';
    public const ERROR_UNIT = 'unit';
    public const ERROR_FALLBACK = 'fallback';
    public const ERROR_LABEL_LENGTH = 'label_length';
    public const ERROR_CART = 'cart';
    public const ERROR_ADDRESS = 'address';

    public static function normalizeCountyNumber(mixed $value): ?string
    {
        if (
            !is_string($value)
            || preg_match('/^[0-9]{1,3}$/D', $value) !== 1
            || (int)$value < 1
            || (int)$value > 92
        ) {
            return null;
        }

        return str_pad($value, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, array{id: int|string, text: string}>
     */
    public function getCheckoutOptionsForCustomer(int $customerId): array
    {
        $affiliation = $this->getActiveAffiliation($customerId);
        if ($affiliation === null) {
            return [];
        }

        $units = $this->getActiveUnitsForAffiliation(
            $affiliation['county_id'],
            $affiliation['agency_id']
        );
        if ($units !== []) {
            return $units;
        }

        return [[
            'id' => self::AGENCY_FALLBACK_TOKEN,
            'text' => $this->buildAgencyLabel($affiliation),
        ]];
    }

    /**
     * @return array<int, array{id: int, text: string}>
     */
    public function getActiveUnitsForCustomer(int $customerId): array
    {
        $affiliation = $this->getActiveAffiliation($customerId);
        if ($affiliation === null) {
            return [];
        }

        return $this->getActiveUnitsForAffiliation(
            $affiliation['county_id'],
            $affiliation['agency_id']
        );
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    public function validateSelection(int $customerId, mixed $rawSelection): array
    {
        if ($rawSelection === null || $rawSelection === '') {
            return $this->invalid(self::ERROR_MISSING);
        }
        if (!is_scalar($rawSelection)) {
            return $this->invalid(self::ERROR_MALFORMED);
        }

        $selectionValue = trim((string)$rawSelection);
        if (hash_equals(self::AGENCY_FALLBACK_TOKEN, $selectionValue)) {
            return $this->validateAgencyFallbackForCustomer($customerId);
        }

        $unitId = $this->parsePositiveId($selectionValue);
        if ($unitId === null) {
            return $this->invalid(self::ERROR_MALFORMED);
        }

        return $this->validateUnitForCustomer($customerId, $unitId);
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    public function saveSelection(int $customerId, mixed $rawSelection, string $flow): array
    {
        $selection = $this->validateSelection($customerId, $rawSelection);
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
            'selection_type' => $selection['selection_type'],
            'reference_id' => $selection['reference_id'],
            'flow' => $flow,
        ];

        return $selection;
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    public function getValidatedSelection(int $customerId): array
    {
        $state = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($state)) {
            return $this->invalid(self::ERROR_MISSING);
        }

        $stateCustomerId = $this->parsePositiveId($state['customer_id'] ?? null);
        $selectionType = is_string($state['selection_type'] ?? null)
            ? $state['selection_type']
            : '';
        $referenceId = $this->parsePositiveId($state['reference_id'] ?? null);
        $cartId = is_scalar($state['cart_id'] ?? null) ? (string)$state['cart_id'] : '';
        if (
            $stateCustomerId !== $customerId
            || !in_array($selectionType, [self::SELECTION_TYPE_UNIT, self::SELECTION_TYPE_AGENCY], true)
            || $referenceId === null
            || $cartId === ''
            || !hash_equals($cartId, $this->currentCartId())
        ) {
            $this->clearSelection();
            return $this->invalid(self::ERROR_CART);
        }

        if ($selectionType === self::SELECTION_TYPE_AGENCY) {
            $selection = $this->validateAgencyFallbackForCustomer($customerId);
            if ($selection['valid'] && $selection['reference_id'] !== $referenceId) {
                $selection = $this->invalid(self::ERROR_AFFILIATION);
            }
        } else {
            $selection = $this->validateUnitForCustomer($customerId, $referenceId);
        }

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

    /**
     * @return array{
     *     county_id: int,
     *     county_number: string,
     *     agency_id: int,
     *     agency_identifier: string,
     *     agency_name: string,
     *     delivery_enabled: bool
     * }|null
     */
    private function getActiveAffiliation(int $customerId): ?array
    {
        global $db;

        if ($customerId <= 0 || !$this->agencyDeliveryFieldExists()) {
            return null;
        }

        $sql =
            "SELECT c.county_ID, c.county_number,
                    a.agency_ID, a.agency_identifier, a.agency_name, a.delivery_enabled
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
        if (
            !in_array($deliveryEnabled, ['0', '1'], true)
            || self::normalizeCountyNumber($result->fields['county_number'] ?? null) === null
        ) {
            return null;
        }

        $affiliation = [
            'county_id' => (int)$result->fields['county_ID'],
            'county_number' => (string)$result->fields['county_number'],
            'agency_id' => (int)$result->fields['agency_ID'],
            'agency_identifier' => (string)$result->fields['agency_identifier'],
            'agency_name' => (string)$result->fields['agency_name'],
            'delivery_enabled' => $deliveryEnabled === '1',
        ];
        $result->MoveNext();

        return $result->EOF ? $affiliation : null;
    }

    /**
     * @return array<int, array{id: int, text: string}>
     */
    private function getActiveUnitsForAffiliation(int $countyId, int $agencyId): array
    {
        global $db;

        $sql =
            "SELECT u.unit_ID, u.unit_identifier, u.unit_name
               FROM " . TABLE_IEMS_UNITS . " u
              WHERE u.county_ID = :countyId
                AND u.agency_ID = :agencyId
                AND u.status = 1
              ORDER BY u.unit_identifier, u.unit_name";
        $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
        $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
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
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    private function validateUnitForCustomer(int $customerId, int $unitId): array
    {
        global $db;

        $affiliation = $this->getActiveAffiliation($customerId);
        if ($affiliation === null) {
            return $this->invalid(self::ERROR_AFFILIATION);
        }
        if (!$this->unitAddressSchemaReady()) {
            return $this->invalid(self::ERROR_ADDRESS);
        }

        $sql =
            "SELECT u.unit_identifier, u.unit_name,
                    u.delivery_street_address, u.delivery_city, u.delivery_postcode
               FROM " . TABLE_IEMS_UNITS . " u
              WHERE u.unit_ID = :unitId
                AND u.agency_ID = :agencyId
                AND u.county_ID = :countyId
                AND u.status = 1
              LIMIT 1";
        $sql = $db->bindVars($sql, ':unitId', $unitId, 'integer');
        $sql = $db->bindVars($sql, ':agencyId', $affiliation['agency_id'], 'integer');
        $sql = $db->bindVars($sql, ':countyId', $affiliation['county_id'], 'integer');
        $result = $db->Execute($sql);
        if ($result->EOF) {
            return $this->invalid(self::ERROR_UNIT);
        }

        $streetAddress = trim((string)($result->fields['delivery_street_address'] ?? ''));
        $city = trim((string)($result->fields['delivery_city'] ?? ''));
        $postcode = trim((string)($result->fields['delivery_postcode'] ?? ''));

        $label = trim(implode(' ', [
            $affiliation['county_number'],
            $affiliation['agency_identifier'],
            $result->fields['unit_identifier'],
            $result->fields['unit_name'],
        ]));
        if (!$this->hasValidLabelLength($label)) {
            return $this->invalid(self::ERROR_LABEL_LENGTH);
        }

        return $this->valid(
            self::SELECTION_TYPE_UNIT,
            $unitId,
            (string)$unitId,
            $unitId,
            $label,
            [
                'agency_name' => $affiliation['agency_name'],
                'delivery_enabled' => $affiliation['delivery_enabled'],
                'unit_identifier' => (string)$result->fields['unit_identifier'],
                'unit_name' => (string)$result->fields['unit_name'],
                'street_address' => $streetAddress,
                'city' => $city,
                'postcode' => $postcode,
            ]
        );
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    private function validateAgencyFallbackForCustomer(int $customerId): array
    {
        $affiliation = $this->getActiveAffiliation($customerId);
        if ($affiliation === null) {
            return $this->invalid(self::ERROR_AFFILIATION);
        }
        if (
            $this->getActiveUnitsForAffiliation(
                $affiliation['county_id'],
                $affiliation['agency_id']
            ) !== []
        ) {
            return $this->invalid(self::ERROR_FALLBACK);
        }

        $label = $this->buildAgencyLabel($affiliation);
        if (!$this->hasValidLabelLength($label)) {
            return $this->invalid(self::ERROR_LABEL_LENGTH);
        }

        return $this->valid(
            self::SELECTION_TYPE_AGENCY,
            $affiliation['agency_id'],
            self::AGENCY_FALLBACK_TOKEN,
            0,
            $label,
            [
                'agency_name' => $affiliation['agency_name'],
                'delivery_enabled' => $affiliation['delivery_enabled'],
            ]
        );
    }

    /**
     * @param array{
     *     county_id: int,
     *     county_number: string,
     *     agency_id: int,
     *     agency_identifier: string,
     *     agency_name: string,
     *     delivery_enabled: bool
     * } $affiliation
     */
    private function buildAgencyLabel(array $affiliation): string
    {
        return trim(implode(' ', [
            $affiliation['county_number'],
            $affiliation['agency_identifier'],
            $affiliation['agency_name'],
        ]));
    }

    private function hasValidLabelLength(string $label): bool
    {
        $labelLength = function_exists('mb_strlen') ? mb_strlen($label, 'UTF-8') : strlen($label);
        return $label !== '' && $labelLength <= 128;
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

    private function currentCartId(): string
    {
        $cartId = $_SESSION['cart']->cartID ?? $_SESSION['cartID'] ?? '';
        return is_scalar($cartId) ? (string)$cartId : '';
    }

    private function agencyDeliveryFieldExists(): bool
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

    private function unitAddressSchemaReady(): bool
    {
        global $db;

        foreach (
            [
                'delivery_street_address' => 'varchar(128)',
                'delivery_city' => 'varchar(128)',
                'delivery_postcode' => 'varchar(64)',
            ] as $column => $type
        ) {
            $field = $db->Execute(
                "SHOW COLUMNS FROM " . TABLE_IEMS_UNITS . " LIKE '" . $column . "'"
            );
            if (
                $field->EOF
                || ($field->fields['Field'] ?? null) !== $column
                || strtolower((string)($field->fields['Type'] ?? '')) !== $type
                || ($field->fields['Null'] ?? null) !== 'YES'
                || ($field->fields['Default'] ?? null) !== null
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    private function valid(
        string $selectionType,
        int $referenceId,
        string $selectionValue,
        int $unitId,
        string $label,
        array $details = []
    ): array {
        return [
            'valid' => true,
            'error' => '',
            'selection_type' => $selectionType,
            'reference_id' => $referenceId,
            'selection_value' => $selectionValue,
            'unit_id' => $unitId,
            'label' => $label,
            'agency_name' => (string)($details['agency_name'] ?? ''),
            'delivery_enabled' => (bool)($details['delivery_enabled'] ?? false),
            'unit_identifier' => (string)($details['unit_identifier'] ?? ''),
            'unit_name' => (string)($details['unit_name'] ?? ''),
            'street_address' => (string)($details['street_address'] ?? ''),
            'city' => (string)($details['city'] ?? ''),
            'postcode' => (string)($details['postcode'] ?? ''),
        ];
    }

    /**
     * @return array{
     *     valid: bool,
     *     error: string,
     *     selection_type: string,
     *     reference_id: int,
     *     selection_value: string,
     *     unit_id: int,
     *     label: string,
     *     agency_name: string,
     *     delivery_enabled: bool,
     *     unit_identifier: string,
     *     unit_name: string,
     *     street_address: string,
     *     city: string,
     *     postcode: string
     * }
     */
    private function invalid(string $error): array
    {
        return [
            'valid' => false,
            'error' => $error,
            'selection_type' => '',
            'reference_id' => 0,
            'selection_value' => '',
            'unit_id' => 0,
            'label' => '',
            'agency_name' => '',
            'delivery_enabled' => false,
            'unit_identifier' => '',
            'unit_name' => '',
            'street_address' => '',
            'city' => '',
            'postcode' => '',
        ];
    }
}
