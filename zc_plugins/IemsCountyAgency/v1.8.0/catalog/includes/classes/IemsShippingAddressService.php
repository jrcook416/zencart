<?php

declare(strict_types=1);

require_once __DIR__ . '/IemsCheckoutUnitService.php';
require_once __DIR__ . '/IemsShippingEligibilityService.php';

final class IemsShippingAddressService
{
    public const MODULE_PICKUP = 'iemspickup';
    public const MODULE_DELIVERY = 'iemsdelivery';

    /**
     * @return array<string, mixed>|null
     */
    public function getDestination(int $customerId, string $shippingModule): ?array
    {
        if (!$this->isModuleEnabled($shippingModule)) {
            return null;
        }

        $selection = (new IemsCheckoutUnitService())->getValidatedSelection($customerId);
        if (!$selection['valid']) {
            return null;
        }

        $region = $this->getIndianaRegion();
        if ($region === null) {
            return null;
        }

        if ($shippingModule === self::MODULE_DELIVERY) {
            if (!(new IemsShippingEligibilityService())->isDeliveryEligible($customerId)) {
                return null;
            }

            return $this->buildAddress(
                trim($selection['unit_identifier'] . ' ' . $selection['unit_name']),
                $selection['agency_name'],
                $selection['street_address'],
                $selection['city'],
                $selection['postcode'],
                $selection['label'],
                $region
            );
        }

        if ($shippingModule !== self::MODULE_PICKUP || !$this->isPickupConfigured()) {
            return null;
        }

        return $this->buildAddress(
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_RECIPIENT'),
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_COMPANY'),
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS'),
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_CITY'),
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_POSTCODE'),
            $selection['label'],
            $region
        );
    }

    public function isPickupConfigured(): bool
    {
        foreach (
            [
                'MODULE_SHIPPING_IEMSPICKUP_RECIPIENT' => 64,
                'MODULE_SHIPPING_IEMSPICKUP_STREET_ADDRESS' => 128,
                'MODULE_SHIPPING_IEMSPICKUP_CITY' => 128,
                'MODULE_SHIPPING_IEMSPICKUP_POSTCODE' => 64,
            ] as $key => $maxLength
        ) {
            if (!$this->isValidAddressValue($this->configurationValue($key), $maxLength)) {
                return false;
            }
        }

        return $this->isValidAddressValue(
            $this->configurationValue('MODULE_SHIPPING_IEMSPICKUP_COMPANY'),
            64,
            true
        ) && $this->getIndianaRegion() !== null;
    }

    /**
     * @param array<string, mixed> $region
     * @return array<string, mixed>
     */
    private function buildAddress(
        string $name,
        string $company,
        string $streetAddress,
        string $city,
        string $postcode,
        string $suburb,
        array $region
    ): array {
        return [
            'firstname' => $name,
            'lastname' => '',
            'company' => $company,
            'street_address' => $streetAddress,
            'suburb' => $suburb,
            'city' => $city,
            'postcode' => $postcode,
            'state' => $region['zone_name'],
            'zone_id' => $region['zone_id'],
            'country' => [
                'id' => $region['country_id'],
                'title' => $region['country_name'],
                'iso_code_2' => $region['iso_code_2'],
                'iso_code_3' => $region['iso_code_3'],
            ],
            'country_id' => $region['country_id'],
            'format_id' => $region['address_format_id'],
        ];
    }

    /**
     * @return array{
     *     country_id: int,
     *     country_name: string,
     *     iso_code_2: string,
     *     iso_code_3: string,
     *     address_format_id: int,
     *     zone_id: int,
     *     zone_name: string
     * }|null
     */
    private function getIndianaRegion(): ?array
    {
        global $db;

        $result = $db->Execute(
            "SELECT c.countries_id, c.countries_name, c.countries_iso_code_2,
                    c.countries_iso_code_3, c.address_format_id,
                    z.zone_id, z.zone_name
               FROM " . TABLE_COUNTRIES . " c
               JOIN " . TABLE_ZONES . " z
                 ON z.zone_country_id = c.countries_id
                AND z.zone_code = 'IN'
              WHERE c.countries_iso_code_2 = 'US'
                AND c.status = 1
              LIMIT 2"
        );
        if ($result->EOF) {
            return null;
        }

        $region = [
            'country_id' => (int)$result->fields['countries_id'],
            'country_name' => (string)$result->fields['countries_name'],
            'iso_code_2' => (string)$result->fields['countries_iso_code_2'],
            'iso_code_3' => (string)$result->fields['countries_iso_code_3'],
            'address_format_id' => (int)$result->fields['address_format_id'],
            'zone_id' => (int)$result->fields['zone_id'],
            'zone_name' => (string)$result->fields['zone_name'],
        ];
        $result->MoveNext();
        if (
            !$result->EOF
            || $region['country_id'] <= 0
            || $region['address_format_id'] <= 0
            || $region['zone_id'] <= 0
            || $region['country_name'] === ''
            || $region['zone_name'] !== 'Indiana'
        ) {
            return null;
        }

        return $region;
    }

    private function configurationValue(string $key): string
    {
        if (defined($key)) {
            return trim((string)constant($key));
        }
        if (function_exists('zen_config')) {
            return trim((string)(zen_config($key) ?? ''));
        }

        return '';
    }

    private function isModuleEnabled(string $shippingModule): bool
    {
        $statusKey = match ($shippingModule) {
            self::MODULE_PICKUP => 'MODULE_SHIPPING_IEMSPICKUP_STATUS',
            self::MODULE_DELIVERY => 'MODULE_SHIPPING_IEMSDELIVERY_STATUS',
            default => '',
        };

        return $statusKey !== '' && $this->configurationValue($statusKey) === 'True';
    }

    private function isValidAddressValue(string $value, int $maxLength, bool $optional = false): bool
    {
        if ($value === '') {
            return $optional;
        }

        return mb_check_encoding($value, 'UTF-8')
            && mb_strlen($value, 'UTF-8') <= $maxLength
            && preg_match('/[<>\x00-\x1F\x7F]/u', $value) === 0;
    }
}
