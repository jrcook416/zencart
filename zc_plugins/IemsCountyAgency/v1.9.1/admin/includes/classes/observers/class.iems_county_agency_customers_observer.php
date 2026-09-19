<?php

declare(strict_types=1);

class zcObserverIemsCountyAgencyCustomers extends base
{
    private const AFFILIATIONS_TABLE = 'iems_customer_affiliations';
    private const COUNTIES_TABLE = 'iems_counties';
    private const AGENCIES_TABLE = 'iems_agencies';

    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT',
            'NOTIFY_ADMIN_CUSTOMERS_UPDATE_VALIDATE',
            'NOTIFY_ADMIN_CUSTOMER_UPDATE',
        ]);
    }

    public function update(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6, &$p7)
    {
        switch ($eventID) {
            case 'NOTIFY_ADMIN_CUSTOMERS_CUSTOMER_EDIT':
                $this->addAffiliationFields($p1, $p2);
                break;

            case 'NOTIFY_ADMIN_CUSTOMERS_UPDATE_VALIDATE':
                if (!$this->validateSelection(true)) {
                    $p2 = true;
                }
                break;

            case 'NOTIFY_ADMIN_CUSTOMER_UPDATE':
                $this->persistAffiliation((int)$p1);
                break;
        }
    }

    private function addAffiliationFields(object $customer, array &$additionalFields): void
    {
        $customerId = (int)($customer->customers_id ?? 0);
        $submitted = array_key_exists('iems_county_id', $_POST)
            || array_key_exists('iems_agency_id', $_POST);

        if ($submitted) {
            $countyId = $this->getPostedId('iems_county_id');
            $agencyId = $this->getPostedId('iems_agency_id');
        } else {
            $affiliation = $customerId > 0 ? $this->getCurrentAffiliation($customerId) : null;
            $countyId = (int)($affiliation['county_ID'] ?? 0);
            $agencyId = (int)($affiliation['agency_ID'] ?? 0);
        }

        $counties = $this->getActiveCounties();
        $agenciesByCounty = $this->getActiveAgenciesByCounty();

        $countyOptions = $this->buildCountyOptions($counties, $countyId);
        $agencyOptions = $this->buildAgencyOptions($agenciesByCounty[$countyId] ?? [], $agencyId);

        $additionalFields[] = [
            'label' => ENTRY_IEMS_ADMIN_COUNTY,
            'fieldname' => 'iems_county_id',
            'input' => zen_draw_pull_down_menu(
                'iems_county_id',
                $countyOptions,
                $countyId,
                'id="iems_county_id" class="form-control" required'
            ),
        ];
        $additionalFields[] = [
            'label' => ENTRY_IEMS_ADMIN_AGENCY,
            'fieldname' => 'iems_agency_id',
            'input' => zen_draw_pull_down_menu(
                'iems_agency_id',
                $agencyOptions,
                $agencyId,
                'id="iems_agency_id" class="form-control" required'
            ) . $this->buildCascadeScript($agenciesByCounty, $agencyId),
        ];
    }

    private function validateSelection(bool $addMessages): bool
    {
        global $messageStack;

        $countyRaw = $this->getPostedScalar('iems_county_id');
        $agencyRaw = $this->getPostedScalar('iems_agency_id');
        $countyId = $this->getPostedId('iems_county_id');
        $agencyId = $this->getPostedId('iems_agency_id');
        $valid = true;

        if ($countyRaw === '') {
            $valid = false;
            if ($addMessages) {
                $messageStack->add(ERROR_IEMS_ADMIN_COUNTY_REQUIRED, 'error');
            }
        } elseif ($countyId <= 0 || !$this->isActiveCounty($countyId)) {
            $valid = false;
            if ($addMessages) {
                $messageStack->add(ERROR_IEMS_ADMIN_COUNTY_INVALID, 'error');
            }
        }

        if ($agencyRaw === '') {
            $valid = false;
            if ($addMessages) {
                $messageStack->add(ERROR_IEMS_ADMIN_AGENCY_REQUIRED, 'error');
            }
        } elseif ($agencyId <= 0 || !$this->isActiveAgencyInCounty($agencyId, $countyId)) {
            $valid = false;
            if ($addMessages) {
                $messageStack->add(ERROR_IEMS_ADMIN_AGENCY_INVALID, 'error');
            }
        }

        return $valid;
    }

    private function persistAffiliation(int $customerId): void
    {
        global $db;

        if ($customerId <= 0 || !$this->validateSelection(false)) {
            throw new UnexpectedValueException('Invalid IEMS affiliation reached the admin customer update hook.');
        }

        $countyId = $this->getPostedId('iems_county_id');
        $agencyId = $this->getPostedId('iems_agency_id');

        $sql =
            "INSERT INTO " . self::AFFILIATIONS_TABLE . "
                (customer_id, county_ID, agency_ID, date_added, last_modified)
             VALUES
                (:customerId, :countyId, :agencyId, now(), now())
             ON DUPLICATE KEY UPDATE
                county_ID = VALUES(county_ID),
                agency_ID = VALUES(agency_ID),
                last_modified = now()";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
        $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
        $db->Execute($sql);

        zen_record_admin_activity(
            'IEMS affiliation updated for customer ID ' . $customerId
                . ', county ID ' . $countyId
                . ', agency ID ' . $agencyId,
            'notice'
        );
    }

    private function getCurrentAffiliation(int $customerId): ?array
    {
        global $db;

        $sql =
            "SELECT county_ID, agency_ID
               FROM " . self::AFFILIATIONS_TABLE . "
              WHERE customer_id = :customerId
              LIMIT 1";
        $sql = $db->bindVars($sql, ':customerId', $customerId, 'integer');
        $result = $db->Execute($sql);

        return $result->EOF ? null : $result->fields;
    }

    private function getPostedScalar(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_scalar($value) ? trim((string)$value) : '';
    }

    private function getPostedId(string $key): int
    {
        $value = $this->getPostedScalar($key);
        if ($value === '' || !ctype_digit($value)) {
            return 0;
        }

        $id = (int)$value;

        return $id > 0 ? $id : 0;
    }

    private function getActiveCounties(): array
    {
        global $db;

        $counties = [];
        $result = $db->Execute(
            "SELECT county_ID, county_number, county_name
               FROM " . self::COUNTIES_TABLE . "
              WHERE status = 1
              ORDER BY CAST(county_number AS UNSIGNED), county_name"
        );

        while (!$result->EOF) {
            $counties[] = [
                'id' => (int)$result->fields['county_ID'],
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
        $result = $db->Execute(
            "SELECT agency_ID, county_ID, agency_identifier, agency_name
               FROM " . self::AGENCIES_TABLE . "
              WHERE status = 1
              ORDER BY CAST(county_ID AS UNSIGNED), agency_identifier, agency_name"
        );

        while (!$result->EOF) {
            $countyId = (int)$result->fields['county_ID'];
            $agencies[$countyId][] = [
                'id' => (int)$result->fields['agency_ID'],
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
        $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
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
        $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
        $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
        $result = $db->Execute($sql);

        return !$result->EOF;
    }

    private function buildCountyOptions(array $counties, int $selectedCountyId): array
    {
        $options = [['id' => '', 'text' => TEXT_IEMS_ADMIN_SELECT_COUNTY]];
        $options = array_merge($options, $counties);

        if ($selectedCountyId > 0 && !$this->optionExists($counties, $selectedCountyId)) {
            $options[] = [
                'id' => $selectedCountyId,
                'text' => TEXT_IEMS_ADMIN_INVALID_SELECTION,
            ];
        }

        return $options;
    }

    private function buildAgencyOptions(array $agencies, int $selectedAgencyId): array
    {
        $options = [['id' => '', 'text' => TEXT_IEMS_ADMIN_SELECT_AGENCY]];
        $options = array_merge($options, $agencies);

        if ($selectedAgencyId > 0 && !$this->optionExists($agencies, $selectedAgencyId)) {
            $options[] = [
                'id' => $selectedAgencyId,
                'text' => TEXT_IEMS_ADMIN_INVALID_SELECTION,
            ];
        }

        return $options;
    }

    private function optionExists(array $options, int $selectedId): bool
    {
        foreach ($options as $option) {
            if ((int)$option['id'] === $selectedId) {
                return true;
            }
        }

        return false;
    }

    private function buildCascadeScript(array $agenciesByCounty, int $selectedAgencyId): string
    {
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        $agenciesJson = json_encode($agenciesByCounty, $jsonFlags | JSON_THROW_ON_ERROR);
        $agencyPromptJson = json_encode(TEXT_IEMS_ADMIN_SELECT_AGENCY, $jsonFlags | JSON_THROW_ON_ERROR);
        $invalidPromptJson = json_encode(TEXT_IEMS_ADMIN_INVALID_SELECTION, $jsonFlags | JSON_THROW_ON_ERROR);

        return <<<HTML
<script>
(() => {
    'use strict';

    const countySelect = document.getElementById('iems_county_id');
    const agencySelect = document.getElementById('iems_agency_id');
    const agenciesByCounty = {$agenciesJson};
    const agencyPrompt = {$agencyPromptJson};
    const invalidPrompt = {$invalidPromptJson};
    const initialAgencyId = {$selectedAgencyId};

    if (!countySelect || !agencySelect) {
        return;
    }

    const addOption = (value, text, selected = false) => {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = text;
        option.selected = selected;
        agencySelect.appendChild(option);
    };

    const renderAgencies = (selectedAgencyId = 0) => {
        const countyId = countySelect.value;
        const agencies = agenciesByCounty[countyId] || [];
        agencySelect.replaceChildren();
        addOption('', agencyPrompt, selectedAgencyId === 0);

        let selectedFound = false;
        agencies.forEach((agency) => {
            const selected = Number(agency.id) === Number(selectedAgencyId);
            selectedFound ||= selected;
            addOption(agency.id, agency.text, selected);
        });

        if (selectedAgencyId > 0 && !selectedFound) {
            addOption(selectedAgencyId, invalidPrompt, true);
        }
    };

    countySelect.addEventListener('change', () => renderAgencies());
    renderAgencies(initialAgencyId);
})();
</script>
HTML;
    }
}
