<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/classes/IemsAgencyPaymentModule.php';

final class iemsinvoice extends IemsAgencyPaymentModule
{
    public function __construct(bool $uninstalling = false)
    {
        parent::__construct(
            'iemsinvoice',
            $uninstalling ? '' : MODULE_PAYMENT_IEMSINVOICE_TEXT_TITLE,
            $uninstalling ? '' : MODULE_PAYMENT_IEMSINVOICE_TEXT_DESCRIPTION,
            'MODULE_PAYMENT_IEMSINVOICE_STATUS',
            'MODULE_PAYMENT_IEMSINVOICE_SORT_ORDER',
            'MODULE_PAYMENT_IEMSINVOICE_ORDER_STATUS_ID',
            IemsPaymentEligibilityService::MODE_INVOICE,
            'Invoice Billing to Agency',
            $uninstalling ? '' : MODULE_PAYMENT_IEMSINVOICE_TEXT_INELIGIBLE,
            $uninstalling
        );
    }
}
