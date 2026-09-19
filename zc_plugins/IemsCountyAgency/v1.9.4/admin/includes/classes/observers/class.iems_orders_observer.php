<?php

declare(strict_types=1);

final class zcObserverIemsOrders extends base
{
    private const DELIVERY_ADDRESS_MARKER = 'iems-v1.9.4-admin-orders-delivery';

    /** @var array<string, mixed>|null */
    private ?array $deliveryAddress = null;
    private bool $deliveryAddressFormatted = false;

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN',
            'NOTIFY_END_ZEN_ADDRESS_FORMAT',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_ADMIN_ORDERS_EDIT_BEGIN':
                $this->captureDeliveryAddress($p2);
                break;

            case 'NOTIFY_END_ZEN_ADDRESS_FORMAT':
                $this->formatDeliveryAddress($p1, $p2);
                break;
        }
    }

    private function captureDeliveryAddress(mixed $order): void
    {
        $this->deliveryAddress = null;
        $this->deliveryAddressFormatted = false;
        if (
            !is_object($order)
            || !is_array($order->delivery ?? null)
            || !in_array(
                $order->info['shipping_module_code'] ?? '',
                ['iemsdelivery', 'iemspickup'],
                true
            )
        ) {
            return;
        }

        $order->delivery['iems_admin_orders_marker'] = self::DELIVERY_ADDRESS_MARKER;
        $this->deliveryAddress = $order->delivery;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatDeliveryAddress(array $details, string &$formattedAddress): void
    {
        if (
            $this->deliveryAddressFormatted
            || $this->deliveryAddress === null
            || !$this->isCapturedDeliveryAddress($details['address'] ?? null)
        ) {
            return;
        }

        $suburb = trim((string)($this->deliveryAddress['suburb'] ?? ''));
        if (
            preg_match('/^([0-9]{1,3})\s+([A-Z0-9]{1,10})\s+(.+)$/Du', $suburb, $matches) !== 1
            || (int)$matches[1] < 1
            || (int)$matches[1] > 92
        ) {
            return;
        }

        $unitOrAgency = trim($matches[3]);
        if ($unitOrAgency === '') {
            return;
        }

        $separator = is_string($details['cr'] ?? null) ? $details['cr'] : '<br>';
        $formattedAddress =
            zen_output_string_protected($matches[1] . ', ' . $matches[2])
            . $separator
            . zen_output_string_protected($unitOrAgency);
        $this->deliveryAddressFormatted = true;
    }

    private function isCapturedDeliveryAddress(mixed $address): bool
    {
        return is_array($address)
            && ($address['iems_admin_orders_marker'] ?? null) === self::DELIVERY_ADDRESS_MARKER;
    }
}
