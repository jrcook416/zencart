<?php

class zcObserverIemsAccountEditLock extends base
{
    private const AFFILIATIONS_TABLE = 'iems_customer_affiliations';

    public function __construct()
    {
        // Register only when the admin has turned the lock on.
        if (!$this->isLockEnabled()) {
            return;
        }

        $this->attach($this, ['NOTIFY_HEADER_START_ACCOUNT_EDIT']);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        if ($eventID === 'NOTIFY_HEADER_START_ACCOUNT_EDIT') {
            $this->handleAccountEditStart();
        }
    }

    // -------------------------------------------------------------------------
    // Core logic
    // -------------------------------------------------------------------------

    private function handleAccountEditStart(): void
    {
        if (empty($_SESSION['customer_id'])) {
            return;
        }

        // Signal to the template and sibling observers that lock is active.
        $GLOBALS['iems_account_edit_locked'] = true;

        // On form submit: replace every locked field in POST with the current
        // DB value so the core update saves unchanged data for those fields.
        if (!empty($_POST['action']) && $_POST['action'] === 'process') {
            $this->overrideLockedPostFields();
        }
    }

    private function overrideLockedPostFields(): void
    {
        $customer = new Customer();
        $data     = $customer->getData();

        // Fields the customer is NOT allowed to change.
        $_POST['firstname']     = $data['customers_firstname'];
        $_POST['lastname']      = $data['customers_lastname'];
        $_POST['email_address'] = $data['customers_email_address'];
        $_POST['fax']           = $data['customers_fax'] ?? '';
        $_POST['nick']          = '';

        $emailFormat           = $data['customers_email_format'] ?? 'TEXT';
        $_POST['email_format'] = in_array($emailFormat, ['HTML', 'TEXT', 'NONE', 'OUT'], true) ? $emailFormat : 'TEXT';

        if (defined('ACCOUNT_GENDER') && ACCOUNT_GENDER === 'true') {
            $_POST['gender'] = $data['customers_gender'] ?? '';
        }

        if (defined('ACCOUNT_DOB') && ACCOUNT_DOB === 'true') {
            $dob           = (string)($data['customers_dob'] ?? '');
            $_POST['dob']  = (empty($dob) || str_starts_with($dob, '0001-01-01')) ? '' : zen_date_short($dob);
        }

        if (defined('CUSTOMERS_REFERRAL_STATUS') && CUSTOMERS_REFERRAL_STATUS === '2') {
            $_POST['customers_referral'] = $data['customers_referral'] ?? '';
        }

        // Lock county/agency — override with the customer's existing affiliation.
        // The county/agency observer will skip validation when the lock is active,
        // so a '0' here (no affiliation on file) is also safe.
        $customerId = (int)$_SESSION['customer_id'];
        $affil      = $this->getCurrentAffiliation($customerId);
        if ($affil !== null) {
            $_POST['iems_county_id'] = (string)(int)$affil['county_ID'];
            $_POST['iems_agency_id'] = (string)(int)$affil['agency_ID'];
        } else {
            $_POST['iems_county_id'] = '0';
            $_POST['iems_agency_id'] = '0';
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function isLockEnabled(): bool
    {
        return defined('IEMS_ACCOUNT_EDIT_LOCK_ENABLED') && IEMS_ACCOUNT_EDIT_LOCK_ENABLED === 'true';
    }

    private function getCurrentAffiliation(int $customerId): ?array
    {
        global $db;

        $sql    = "SELECT county_ID, agency_ID
                     FROM " . self::AFFILIATIONS_TABLE . "
                    WHERE customer_id = :customerId
                    LIMIT 1";
        $sql    = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $result = $db->Execute($sql);

        return $result->EOF ? null : $result->fields;
    }
}
