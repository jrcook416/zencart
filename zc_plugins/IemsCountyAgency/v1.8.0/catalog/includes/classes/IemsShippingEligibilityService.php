<?php

declare(strict_types=1);

require_once __DIR__ . '/IemsCheckoutUnitService.php';

final class IemsShippingEligibilityService
{
    public function isPickupEligible(int $customerId): bool
    {
        return (new IemsCheckoutUnitService())->getValidatedSelection($customerId)['valid'];
    }

    public function isDeliveryEligible(int $customerId): bool
    {
        $selection = (new IemsCheckoutUnitService())->getValidatedSelection($customerId);

        return $selection['valid']
            && $selection['selection_type'] === IemsCheckoutUnitService::SELECTION_TYPE_UNIT
            && $selection['delivery_enabled']
            && $this->hasValidDeliveryNames($selection)
            && $this->hasCompleteUnitAddress($selection);
    }

    /**
     * @param array<string, mixed> $selection
     */
    private function hasCompleteUnitAddress(array $selection): bool
    {
        foreach (
            [
                'street_address' => 128,
                'city' => 128,
                'postcode' => 64,
            ] as $field => $maxLength
        ) {
            $value = trim((string)($selection[$field] ?? ''));
            if (
                $value === ''
                || !mb_check_encoding($value, 'UTF-8')
                || mb_strlen($value, 'UTF-8') > $maxLength
                || preg_match('/[<>\x00-\x1F\x7F]/u', $value) !== 0
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $selection
     */
    private function hasValidDeliveryNames(array $selection): bool
    {
        $unitName = trim(
            (string)($selection['unit_identifier'] ?? '')
            . ' '
            . (string)($selection['unit_name'] ?? '')
        );
        $agencyName = trim((string)($selection['agency_name'] ?? ''));

        return $unitName !== ''
            && mb_strlen($unitName, 'UTF-8') <= 64
            && preg_match('/[<>\x00-\x1F\x7F]/u', $unitName) === 0
            && $agencyName !== ''
            && mb_strlen($agencyName, 'UTF-8') <= 64
            && preg_match('/[<>\x00-\x1F\x7F]/u', $agencyName) === 0;
    }
}
