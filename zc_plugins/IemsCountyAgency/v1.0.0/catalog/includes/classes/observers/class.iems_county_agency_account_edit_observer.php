<?php

class zcObserverIemsCountyAgencyAccountEdit extends base
{
    private const AFFILIATIONS_TABLE = 'iems_customer_affiliations';
    private const COUNTIES_TABLE     = 'iems_counties';
    private const AGENCIES_TABLE     = 'iems_agencies';

    public function __construct()
    {
        $this->loadLanguageFile();

        $this->attach($this, [
            'NOTIFY_HEADER_START_ACCOUNT_EDIT',
            'NOTIFY_HEADER_ACCOUNT_EDIT_VERIFY_COMPLETE',
            'NOTIFY_HEADER_ACCOUNT_EDIT_UPDATES_COMPLETE',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_HEADER_START_ACCOUNT_EDIT':
                $this->setTemplateData();
                break;

            case 'NOTIFY_HEADER_ACCOUNT_EDIT_VERIFY_COMPLETE':
                $this->validateAndRedirectIfInvalid();
                break;

            case 'NOTIFY_HEADER_ACCOUNT_EDIT_UPDATES_COMPLETE':
                $customerId = (int)($_SESSION['customer_id'] ?? 0);
                if ($customerId > 0) {
                    $this->persistAffiliation($customerId);
                }
                break;
        }
    }

    private function loadLanguageFile(): void
    {
        $language = $_SESSION['language'] ?? 'english';
        $basePath = __DIR__ . '/../../../languages/';
        $langFile = $basePath . $language . '/extra_definitions/lang.iems_county_agency_account_edit.php';
        if (!file_exists($langFile)) {
            $langFile = $basePath . 'english/extra_definitions/lang.iems_county_agency_account_edit.php';
        }
        if (file_exists($langFile)) {
            require_once $langFile;
        }
    }

    private function setTemplateData(): void
    {
        // On POST re-render (validation failure redirect), use the posted IDs.
        // On GET (initial load), read the customer's current affiliation from DB.
        $isPost = (!empty($_POST['action']) && $_POST['action'] === 'process');

        if ($isPost) {
            $countyId = $this->getPostedId('iems_county_id');
            $agencyId = $this->getPostedId('iems_agency_id');
        } else {
            $customerId = (int)($_SESSION['customer_id'] ?? 0);
            $current    = ($customerId > 0) ? $this->getCurrentAffiliation($customerId) : null;
            $countyId   = $current ? (int)$current['county_ID'] : 0;
            $agencyId   = $current ? (int)$current['agency_ID'] : 0;
        }

        $GLOBALS['iems_selected_county_id']        = $countyId;
        $GLOBALS['iems_selected_agency_id']         = $agencyId;
        $GLOBALS['iems_county_options']             = $this->getActiveCounties();
        $GLOBALS['iems_agency_options_by_county']   = $this->getActiveAgenciesByCounty();
    }

    private function validateAndRedirectIfInvalid(): void
    {
        // Only run during a POST submit; ignore GET renders.
        if (empty($_POST['action']) || $_POST['action'] !== 'process') {
            return;
        }

        if ($this->validateSelection()) {
            return;
        }

        // Validation failed — redirect back so the error appears above the form.
        // Messages were added as session messages inside validateSelection().
        zen_redirect(zen_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL'));
    }

    private function validateSelection(): bool
    {
        global $messageStack;

        $countyRaw = $_POST['iems_county_id'] ?? '';
        $agencyRaw = $_POST['iems_agency_id'] ?? '';

        $countyRaw = is_scalar($countyRaw) ? trim((string)$countyRaw) : '';
        $agencyRaw = is_scalar($agencyRaw) ? trim((string)$agencyRaw) : '';

        $countyId = $this->getPostedId('iems_county_id');
        $agencyId = $this->getPostedId('iems_agency_id');
        $valid    = true;

        if ($countyRaw === '') {
            $valid = false;
            $messageStack->add_session('account_edit', ERROR_IEMS_COUNTY_REQUIRED, 'error');
        } elseif ($countyId <= 0 || !$this->isActiveCounty($countyId)) {
            $valid = false;
            $messageStack->add_session('account_edit', ERROR_IEMS_COUNTY_INVALID, 'error');
        }

        if ($agencyRaw === '') {
            $valid = false;
            $messageStack->add_session('account_edit', ERROR_IEMS_AGENCY_REQUIRED, 'error');
        } elseif ($agencyId <= 0 || !$this->isActiveAgencyInCounty($agencyId, $countyId)) {
            $valid = false;
            $messageStack->add_session('account_edit', ERROR_IEMS_AGENCY_INVALID, 'error');
        }

        return $valid;
    }

    private function persistAffiliation(int $customerId): void
    {
        global $db;

        $countyId = $this->getPostedId('iems_county_id');
        $agencyId = $this->getPostedId('iems_agency_id');
        if ($countyId <= 0 || $agencyId <= 0) {
            return;
        }
        // Double-check integrity before write (validateAndRedirectIfInvalid already ran, but be safe).
        if (!$this->isActiveCounty($countyId) || !$this->isActiveAgencyInCounty($agencyId, $countyId)) {
            return;
        }

        $sql =
            "INSERT INTO " . self::AFFILIATIONS_TABLE . "
                (customer_id, county_ID, agency_ID, date_added, last_modified)
             VALUES
                (:customerId, :countyId, :agencyId, now(), now())
             ON DUPLICATE KEY UPDATE
                county_ID     = VALUES(county_ID),
                agency_ID     = VALUES(agency_ID),
                last_modified = now()";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $sql = $db->bindVars($sql, ':countyId',   $countyId,   'integer');
        $sql = $db->bindVars($sql, ':agencyId',   $agencyId,   'integer');
        $db->Execute($sql);
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

    private function getPostedId(string $key): int
    {
        $value = $_POST[$key] ?? '';
        $value = is_scalar($value) ? trim((string)$value) : '';
        if ($value === '' || !ctype_digit($value)) {
            return 0;
        }
        $id = (int)$value;
        return ($id > 0) ? $id : 0;
    }

    private function getActiveCounties(): array
    {
        global $db;

        $counties = [];
        $result   = $db->Execute(
            "SELECT county_ID, county_number, county_name
               FROM " . self::COUNTIES_TABLE . "
              WHERE status = 1
              ORDER BY CAST(county_number AS UNSIGNED), county_name"
        );

        while (!$result->EOF) {
            $counties[] = [
                'id'   => (int)$result->fields['county_ID'],
                'text' => trim($result->fields['county_number'] . ' ' . $result->fields['county_name']),
            ];
            $result->MoveNext();
        }

        return $counties;
    }

    private function getActiveAgenciesByCounty(): array
    {
        global $db;

        $agencies = [];
        $result   = $db->Execute(
            "SELECT agency_ID, county_ID, agency_identifier, agency_name
               FROM " . self::AGENCIES_TABLE . "
              WHERE status = 1
              ORDER BY CAST(county_ID AS UNSIGNED), agency_identifier, agency_name"
        );

        while (!$result->EOF) {
            $countyId           = (int)$result->fields['county_ID'];
            $agencies[$countyId][] = [
                'id'   => (int)$result->fields['agency_ID'],
                'text' => trim($result->fields['agency_identifier'] . ' ' . $result->fields['agency_name']),
            ];
            $result->MoveNext();
        }

        return $agencies;
    }

    private function isActiveCounty(int $countyId): bool
    {
        global $db;

        if ($countyId <= 0) {
            return false;
        }

        $sql =
            "SELECT county_ID
               FROM " . self::COUNTIES_TABLE . "
              WHERE county_ID = :countyId
                AND status = 1
              LIMIT 1";
        $sql    = $db->bindVars($sql, ':countyId', $countyId, 'integer');
        $result = $db->Execute($sql);

        return !$result->EOF;
    }

    private function isActiveAgencyInCounty(int $agencyId, int $countyId): bool
    {
        global $db;

        if ($agencyId <= 0 || $countyId <= 0) {
            return false;
        }

        $sql =
            "SELECT agency_ID
               FROM " . self::AGENCIES_TABLE . "
              WHERE agency_ID = :agencyId
                AND county_ID = :countyId
                AND status = 1
              LIMIT 1";
        $sql    = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
        $sql    = $db->bindVars($sql, ':countyId', $countyId, 'integer');
        $result = $db->Execute($sql);

        return !$result->EOF;
    }
}
