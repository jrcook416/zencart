<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsUnitInput;

require 'includes/application_top.php';

if (!zen_is_superuser() && !check_page(FILENAME_IEMS_UNITS, [])) {
    zen_record_admin_activity(
        'Attempted access to unauthorized page [iems_units]. Redirected to DENIED page instead.',
        'notice'
    );
    zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'));
}

/**
 * @return array<string, mixed>|null
 */
function iems_units_get_unit(int $unitId): ?array
{
    global $db;

    $sql =
        "SELECT unit_ID, county_ID, agency_ID, unit_identifier, unit_name, status
           FROM " . TABLE_IEMS_UNITS . "
          WHERE unit_ID = :unitId
          LIMIT 1";
    $sql = $db->bindVars($sql, ':unitId', $unitId, 'integer');
    $result = $db->Execute($sql);

    return $result->EOF ? null : $result->fields;
}

/**
 * @return array<string, mixed>|null
 */
function iems_units_get_agency(int $agencyId): ?array
{
    global $db;

    $sql =
        "SELECT a.agency_ID, a.county_ID, a.agency_identifier, a.agency_name, a.status,
                c.county_number, c.county_name, c.status AS county_status
           FROM " . TABLE_IEMS_AGENCIES . " a
           JOIN " . TABLE_IEMS_COUNTIES . " c ON c.county_ID = a.county_ID
          WHERE a.agency_ID = :agencyId
          LIMIT 1";
    $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
    $result = $db->Execute($sql);

    return $result->EOF ? null : $result->fields;
}

function iems_units_county_exists(int $countyId): bool
{
    global $db;

    $sql =
        "SELECT county_ID
           FROM " . TABLE_IEMS_COUNTIES . "
          WHERE county_ID = :countyId
          LIMIT 1";
    $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');

    return !$db->Execute($sql)->EOF;
}

function iems_units_identifier_exists(int $agencyId, string $identifier, int $excludeUnitId = 0): bool
{
    global $db;

    $sql =
        "SELECT unit_ID
           FROM " . TABLE_IEMS_UNITS . "
          WHERE agency_ID = :agencyId
            AND unit_identifier = :identifier";
    $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
    $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
    if ($excludeUnitId > 0) {
        $sql .= " AND unit_ID <> :unitId";
        $sql = $db->bindVars($sql, ':unitId', $excludeUnitId, 'integer');
    }
    $sql .= " LIMIT 1";

    return !$db->Execute($sql)->EOF;
}

/**
 * @return array<int, array{id: int, county_id: int, text: string, status: int, county_status: int}>
 */
function iems_units_get_agencies(): array
{
    global $db;

    $agencies = [];
    $result = $db->Execute(
        "SELECT a.agency_ID, a.county_ID, a.agency_identifier, a.agency_name, a.status,
                c.county_number, c.county_name, c.status AS county_status
           FROM " . TABLE_IEMS_AGENCIES . " a
           JOIN " . TABLE_IEMS_COUNTIES . " c ON c.county_ID = a.county_ID
          ORDER BY CAST(c.county_number AS UNSIGNED), a.agency_identifier, a.agency_name"
    );
    while (!$result->EOF) {
        $agencyStatus = (int)$result->fields['status'];
        $countyStatus = (int)$result->fields['county_status'];
        $text = trim(
            $result->fields['county_number'] . ' '
            . $result->fields['agency_identifier'] . ' '
            . $result->fields['agency_name']
        );
        if ($agencyStatus !== 1) {
            $text .= ' (' . TEXT_INACTIVE_AGENCY . ')';
        }
        if ($countyStatus !== 1) {
            $text .= ' (' . TEXT_INACTIVE_COUNTY . ')';
        }
        $agencies[] = [
            'id' => (int)$result->fields['agency_ID'],
            'county_id' => (int)$result->fields['county_ID'],
            'text' => $text,
            'status' => $agencyStatus,
            'county_status' => $countyStatus,
        ];
        $result->MoveNext();
    }

    return $agencies;
}

/**
 * @return array<int, array{id: int, text: string, status: int}>
 */
function iems_units_get_counties(): array
{
    global $db;

    $counties = [];
    $result = $db->Execute(
        "SELECT county_ID, county_number, county_name, status
           FROM " . TABLE_IEMS_COUNTIES . "
          ORDER BY CAST(county_number AS UNSIGNED), county_name"
    );
    while (!$result->EOF) {
        $status = (int)$result->fields['status'];
        $text = trim($result->fields['county_number'] . ' ' . $result->fields['county_name']);
        if ($status !== 1) {
            $text .= ' (' . TEXT_INACTIVE_COUNTY . ')';
        }
        $counties[] = [
            'id' => (int)$result->fields['county_ID'],
            'text' => $text,
            'status' => $status,
        ];
        $result->MoveNext();
    }

    return $counties;
}

function iems_units_add_input_errors(array $errorCodes): void
{
    global $messageStack;

    $messages = [
        'agency_required' => ERROR_AGENCY_REQUIRED,
        'agency_invalid' => ERROR_AGENCY_INVALID,
        'identifier_required' => ERROR_IDENTIFIER_REQUIRED,
        'identifier_length' => ERROR_IDENTIFIER_LENGTH,
        'identifier_format' => ERROR_IDENTIFIER_FORMAT,
        'name_required' => ERROR_NAME_REQUIRED,
        'name_length' => ERROR_NAME_LENGTH,
        'name_format' => ERROR_NAME_FORMAT,
    ];
    foreach ($errorCodes as $errorCode) {
        if (isset($messages[$errorCode])) {
            $messageStack->add($messages[$errorCode], 'error');
        }
    }
}

function iems_units_redirect_with_message(string $message, int $unitId): never
{
    global $messageStack;

    $messageStack->add_session(sprintf($message, $unitId), 'success');
    zen_redirect(zen_href_link(FILENAME_IEMS_UNITS, 'unit_id=' . $unitId, 'SSL'));
}

$agencies = iems_units_get_agencies();
$counties = iems_units_get_counties();
$action = IemsUnitInput::scalar($_GET['action'] ?? '') ?? '__invalid__';
$allowedDisplayActions = ['', 'new', 'edit'];
$formMode = '';
$formValues = [
    'agency_id' => '',
    'unit_identifier' => '',
    'unit_name' => '',
];
$formUnitId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = IemsUnitInput::scalar($_POST['action'] ?? null);
    if (!in_array($postAction, ['save', 'deactivate', 'reactivate'], true)) {
        $messageStack->add(ERROR_INVALID_ACTION, 'error');
    } elseif ($postAction === 'save') {
        $unitIdRaw = IemsUnitInput::scalar($_POST['unit_id'] ?? null);
        $isCreate = $unitIdRaw === '';
        $unitId = $isCreate ? 0 : IemsUnitInput::positiveId($unitIdRaw);
        $input = IemsUnitInput::validate($_POST);
        $formValues = $input['values'];
        $formMode = $isCreate ? 'new' : 'edit';
        $formUnitId = $unitId ?? 0;

        if (!$isCreate && $unitId === null) {
            $input['errors'][] = 'unit_id_invalid';
            $messageStack->add(ERROR_INVALID_UNIT_ID, 'error');
        }

        $existingUnit = $unitId !== null && $unitId > 0 ? iems_units_get_unit($unitId) : null;
        if (!$isCreate && $unitId !== null && $existingUnit === null) {
            $input['errors'][] = 'unit_not_found';
            $messageStack->add(ERROR_UNIT_NOT_FOUND, 'error');
        }

        $agencyId = IemsUnitInput::positiveId($formValues['agency_id']);
        $agency = $agencyId === null ? null : iems_units_get_agency($agencyId);
        if ($agencyId !== null && $agency === null) {
            $input['errors'][] = 'agency_not_found';
            $messageStack->add(ERROR_AGENCY_INVALID, 'error');
        }

        if (
            $agencyId !== null
            && $agency !== null
            && $formValues['unit_identifier'] !== ''
            && preg_match('/^[A-Z0-9]{1,10}$/D', $formValues['unit_identifier']) === 1
            && iems_units_identifier_exists($agencyId, $formValues['unit_identifier'], $unitId ?? 0)
        ) {
            $input['errors'][] = 'identifier_duplicate';
            $messageStack->add(ERROR_DUPLICATE_IDENTIFIER, 'error');
        }

        iems_units_add_input_errors($input['errors']);

        if ($input['errors'] === []) {
            $countyId = (int)$agency['county_ID'];
            $identifier = $formValues['unit_identifier'];
            $name = $formValues['unit_name'];
            if ($isCreate) {
                $sql =
                    "INSERT INTO " . TABLE_IEMS_UNITS . "
                        (county_ID, agency_ID, unit_identifier, unit_name, status, date_added, last_modified)
                     VALUES
                        (:countyId, :agencyId, :identifier, :unitName, 1, NOW(), NULL)";
                $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
                $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
                $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
                $sql = $db->bindVars($sql, ':unitName', $name, 'string');
                $db->Execute($sql);
                $unitId = (int)$db->insert_ID();
                zen_record_admin_activity(
                    'IEMS unit created with ID ' . $unitId
                        . ', agency ID ' . $agencyId . ', county ID ' . $countyId,
                    'info'
                );
                iems_units_redirect_with_message(SUCCESS_UNIT_CREATED, $unitId);
            }

            $oldAgencyId = (int)$existingUnit['agency_ID'];
            $oldCountyId = (int)$existingUnit['county_ID'];
            $sql =
                "UPDATE " . TABLE_IEMS_UNITS . "
                    SET county_ID = :countyId,
                        agency_ID = :agencyId,
                        unit_identifier = :identifier,
                        unit_name = :unitName,
                        last_modified = NOW()
                  WHERE unit_ID = :unitId";
            $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
            $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
            $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
            $sql = $db->bindVars($sql, ':unitName', $name, 'string');
            $sql = $db->bindVars($sql, ':unitId', $unitId, 'integer');
            $db->Execute($sql);

            zen_record_admin_activity(
                'IEMS unit updated for ID ' . $unitId
                    . ', agency ID ' . $oldAgencyId . ' to ' . $agencyId
                    . ', county ID ' . $oldCountyId . ' to ' . $countyId,
                'info'
            );
            iems_units_redirect_with_message(SUCCESS_UNIT_UPDATED, $unitId);
        }
    } else {
        $unitId = IemsUnitInput::positiveId($_POST['unit_id'] ?? null);
        if ($unitId === null) {
            $messageStack->add(ERROR_INVALID_UNIT_ID, 'error');
        } else {
            $unit = iems_units_get_unit($unitId);
            if ($unit === null) {
                $messageStack->add(ERROR_UNIT_NOT_FOUND, 'error');
            } else {
                $newStatus = $postAction === 'reactivate' ? 1 : 0;
                if ((int)$unit['status'] === $newStatus) {
                    $messageStack->add(ERROR_STATUS_ALREADY_SET, 'error');
                } else {
                    $sql =
                        "UPDATE " . TABLE_IEMS_UNITS . "
                            SET status = :status,
                                last_modified = NOW()
                          WHERE unit_ID = :unitId";
                    $sql = $db->bindVars($sql, ':status', $newStatus, 'integer');
                    $sql = $db->bindVars($sql, ':unitId', $unitId, 'integer');
                    $db->Execute($sql);
                    $activity = $newStatus === 1 ? 'reactivated' : 'deactivated';
                    zen_record_admin_activity('IEMS unit ' . $activity . ' for ID ' . $unitId, 'notice');
                    iems_units_redirect_with_message(
                        $newStatus === 1 ? SUCCESS_UNIT_REACTIVATED : SUCCESS_UNIT_DEACTIVATED,
                        $unitId
                    );
                }
            }
        }
    }
}

if (!in_array($action, $allowedDisplayActions, true)) {
    $messageStack->add(ERROR_INVALID_ACTION, 'error');
    $action = '';
}

if ($formMode === '' && $action === 'new') {
    $formMode = 'new';
}

if ($formMode === '' && $action === 'edit') {
    $unitId = IemsUnitInput::positiveId($_GET['unit_id'] ?? null);
    if ($unitId === null) {
        $messageStack->add(ERROR_INVALID_UNIT_ID, 'error');
        $action = '';
    } else {
        $unit = iems_units_get_unit($unitId);
        if ($unit === null) {
            $messageStack->add(ERROR_UNIT_NOT_FOUND, 'error');
            $action = '';
        } else {
            $formMode = 'edit';
            $formUnitId = $unitId;
            $formValues = [
                'agency_id' => (string)$unit['agency_ID'],
                'unit_identifier' => (string)$unit['unit_identifier'],
                'unit_name' => (string)$unit['unit_name'],
            ];
        }
    }
}

$search = IemsUnitInput::scalar($_GET['search'] ?? '') ?? '';
if (mb_strlen($search) > 128) {
    $search = '';
    $messageStack->add(ERROR_SEARCH_LENGTH, 'error');
}
$statusFilter = IemsUnitInput::scalar($_GET['status'] ?? 'all') ?? 'all';
if (!in_array($statusFilter, ['all', 'active', 'inactive'], true)) {
    $statusFilter = 'all';
    $messageStack->add(ERROR_INVALID_ACTION, 'error');
}
$countyFilterRaw = IemsUnitInput::scalar($_GET['county_id'] ?? '') ?? '';
$countyFilter = $countyFilterRaw === '' ? 0 : IemsUnitInput::positiveId($countyFilterRaw);
if ($countyFilter === null) {
    $countyFilter = 0;
    $messageStack->add(ERROR_COUNTY_INVALID, 'error');
} elseif ($countyFilter > 0 && !iems_units_county_exists($countyFilter)) {
    $countyFilter = 0;
    $messageStack->add(ERROR_COUNTY_INVALID, 'error');
}
$agencyFilterRaw = IemsUnitInput::scalar($_GET['agency_id'] ?? '') ?? '';
$agencyFilter = $agencyFilterRaw === '' ? 0 : IemsUnitInput::positiveId($agencyFilterRaw);
if ($agencyFilter === null) {
    $agencyFilter = 0;
    $messageStack->add(ERROR_AGENCY_INVALID, 'error');
} elseif ($agencyFilter > 0 && iems_units_get_agency($agencyFilter) === null) {
    $agencyFilter = 0;
    $messageStack->add(ERROR_AGENCY_INVALID, 'error');
}

$where = ['1 = 1'];
if ($statusFilter !== 'all') {
    $where[] = 'u.status = ' . ($statusFilter === 'active' ? '1' : '0');
}
if ($countyFilter > 0) {
    $where[] = 'u.county_ID = ' . $countyFilter;
}
if ($agencyFilter > 0) {
    $where[] = 'u.agency_ID = ' . $agencyFilter;
}
if ($search !== '') {
    $searchSql = $db->bindVars(':search', ':search', '%' . $search . '%', 'string');
    $where[] =
        "(u.unit_identifier LIKE " . $searchSql
        . " OR u.unit_name LIKE " . $searchSql
        . " OR a.agency_identifier LIKE " . $searchSql
        . " OR a.agency_name LIKE " . $searchSql
        . " OR c.county_number LIKE " . $searchSql
        . " OR c.county_name LIKE " . $searchSql . ")";
}
$whereSql = implode(' AND ', $where);
$unitsQueryRaw =
    "SELECT u.unit_ID, u.county_ID, u.agency_ID, u.unit_identifier, u.unit_name, u.status,
            a.agency_identifier, a.agency_name, a.status AS agency_status,
            c.county_number, c.county_name, c.status AS county_status
       FROM " . TABLE_IEMS_UNITS . " u
       JOIN " . TABLE_IEMS_AGENCIES . " a ON a.agency_ID = u.agency_ID
       JOIN " . TABLE_IEMS_COUNTIES . " c
         ON c.county_ID = u.county_ID
        AND c.county_ID = a.county_ID
      WHERE " . $whereSql . "
      ORDER BY CAST(c.county_number AS UNSIGNED), a.agency_identifier, u.unit_identifier, u.unit_name";
$currentPage = IemsUnitInput::positiveId($_GET['page'] ?? null) ?? 1;
$unitsQueryNumRows = 0;
$unitsSplit = new splitPageResults(
    $currentPage,
    MAX_DISPLAY_SEARCH_RESULTS,
    $unitsQueryRaw,
    $unitsQueryNumRows
);
$units = $db->Execute($unitsQueryRaw);

$filterParameters = [];
if ($search !== '') {
    $filterParameters[] = 'search=' . rawurlencode($search);
}
if ($statusFilter !== 'all') {
    $filterParameters[] = 'status=' . $statusFilter;
}
if ($countyFilter > 0) {
    $filterParameters[] = 'county_id=' . $countyFilter;
}
if ($agencyFilter > 0) {
    $filterParameters[] = 'agency_id=' . $agencyFilter;
}
$paginationParameters = implode('&', $filterParameters);
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<div class="container-fluid">
    <h1><?php echo HEADING_TITLE; ?></h1>

    <div class="alert alert-info"><?php echo TEXT_READ_ONLY_COUNTIES; ?></div>
    <p class="help-block"><?php echo TEXT_PROFILE_ACCESS_HELP; ?></p>
    <div class="alert alert-warning"><?php echo TEXT_STATUS_CONSEQUENCE; ?></div>

    <?php if ($formMode !== '') { ?>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">
                    <?php echo $formMode === 'new' ? HEADING_NEW_UNIT : sprintf(HEADING_EDIT_UNIT, $formUnitId); ?>
                </h2>
            </div>
            <div class="panel-body">
                <?php echo zen_draw_form('iems_unit', FILENAME_IEMS_UNITS, '', 'post', 'class="form-horizontal"'); ?>
                <?php echo zen_draw_hidden_field('action', 'save'); ?>
                <?php echo zen_draw_hidden_field('unit_id', $formMode === 'new' ? '' : (string)$formUnitId); ?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="agency_id"><?php echo TEXT_AGENCY; ?></label>
                    <div class="col-sm-9">
                        <?php
                        $agencyOptions = [['id' => '', 'text' => TEXT_SELECT_AGENCY]];
                        foreach ($agencies as $agencyOption) {
                            $agencyOptions[] = ['id' => $agencyOption['id'], 'text' => $agencyOption['text']];
                        }
                        echo zen_draw_pull_down_menu(
                            'agency_id',
                            $agencyOptions,
                            $formValues['agency_id'],
                            'id="agency_id" class="form-control" required'
                        );
                        ?>
                        <p class="help-block"><?php echo TEXT_AGENCY_DERIVATION_HELP; ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="unit_identifier"><?php echo TEXT_UNIT_IDENTIFIER; ?></label>
                    <div class="col-sm-9">
                        <?php
                        echo zen_draw_input_field(
                            'unit_identifier',
                            $formValues['unit_identifier'],
                            'id="unit_identifier" class="form-control" maxlength="10" pattern="[A-Z0-9]+" required'
                        );
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="unit_name"><?php echo TEXT_UNIT_NAME; ?></label>
                    <div class="col-sm-9">
                        <?php
                        echo zen_draw_input_field(
                            'unit_name',
                            $formValues['unit_name'],
                            'id="unit_name" class="form-control" maxlength="128" required'
                        );
                        ?>
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><?php echo TEXT_SAVE; ?></button>
                    <a class="btn btn-default" href="<?php echo zen_href_link(FILENAME_IEMS_UNITS); ?>">
                        <?php echo TEXT_CANCEL; ?>
                    </a>
                </div>
                <?= '</form>' ?>
            </div>
        </div>
    <?php } ?>

    <div class="panel panel-default">
        <div class="panel-body">
            <?php echo zen_draw_form('iems_unit_filter', FILENAME_IEMS_UNITS, '', 'get', 'class="form-inline"'); ?>
            <div class="form-group">
                <label class="sr-only" for="search"><?php echo TEXT_SEARCH; ?></label>
                <?php
                echo zen_draw_input_field(
                    'search',
                    $search,
                    'id="search" class="form-control" maxlength="128" placeholder="' . TEXT_SEARCH_PLACEHOLDER . '"'
                );
                ?>
            </div>
            <div class="form-group">
                <label class="sr-only" for="status"><?php echo TEXT_STATUS; ?></label>
                <?php
                echo zen_draw_pull_down_menu(
                    'status',
                    [
                        ['id' => 'all', 'text' => TEXT_ALL_STATUSES],
                        ['id' => 'active', 'text' => TEXT_ACTIVE],
                        ['id' => 'inactive', 'text' => TEXT_INACTIVE],
                    ],
                    $statusFilter,
                    'id="status" class="form-control"'
                );
                ?>
            </div>
            <div class="form-group">
                <label class="sr-only" for="filter_county_id"><?php echo TEXT_COUNTY; ?></label>
                <?php
                $countyOptions = [['id' => '', 'text' => TEXT_ALL_COUNTIES]];
                foreach ($counties as $county) {
                    $countyOptions[] = ['id' => $county['id'], 'text' => $county['text']];
                }
                echo zen_draw_pull_down_menu(
                    'county_id',
                    $countyOptions,
                    $countyFilter,
                    'id="filter_county_id" class="form-control"'
                );
                ?>
            </div>
            <div class="form-group">
                <label class="sr-only" for="filter_agency_id"><?php echo TEXT_AGENCY; ?></label>
                <?php
                $filterAgencyOptions = [['id' => '', 'text' => TEXT_ALL_AGENCIES]];
                foreach ($agencies as $agencyOption) {
                    $filterAgencyOptions[] = ['id' => $agencyOption['id'], 'text' => $agencyOption['text']];
                }
                echo zen_draw_pull_down_menu(
                    'agency_id',
                    $filterAgencyOptions,
                    $agencyFilter,
                    'id="filter_agency_id" class="form-control"'
                );
                ?>
            </div>
            <button type="submit" class="btn btn-default"><?php echo TEXT_FILTER; ?></button>
            <a class="btn btn-link" href="<?php echo zen_href_link(FILENAME_IEMS_UNITS); ?>">
                <?php echo TEXT_CLEAR; ?>
            </a>
            <?= '</form>' ?>
            <a class="btn btn-primary pull-right" href="<?php echo zen_href_link(FILENAME_IEMS_UNITS, 'action=new'); ?>">
                <?php echo TEXT_CREATE_UNIT; ?>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th><?php echo TEXT_UNIT_ID; ?></th>
                <th><?php echo TEXT_COUNTY; ?></th>
                <th><?php echo TEXT_AGENCY; ?></th>
                <th><?php echo TEXT_UNIT_IDENTIFIER; ?></th>
                <th><?php echo TEXT_UNIT_NAME; ?></th>
                <th class="text-center"><?php echo TEXT_STATUS; ?></th>
                <th class="text-right"><?php echo TEXT_ACTIONS; ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($units->EOF) { ?>
                <tr>
                    <td colspan="7" class="text-center"><?php echo TEXT_NO_UNITS; ?></td>
                </tr>
            <?php } ?>
            <?php while (!$units->EOF) { ?>
                <?php $unitId = (int)$units->fields['unit_ID']; ?>
                <tr>
                    <td><?php echo $unitId; ?></td>
                    <td>
                        <?php
                        echo zen_output_string_protected(
                            trim($units->fields['county_number'] . ' ' . $units->fields['county_name'])
                        );
                        if ((int)$units->fields['county_status'] !== 1) {
                            echo ' <span class="label label-warning">' . TEXT_INACTIVE_COUNTY . '</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        echo zen_output_string_protected(
                            trim($units->fields['agency_identifier'] . ' ' . $units->fields['agency_name'])
                        );
                        if ((int)$units->fields['agency_status'] !== 1) {
                            echo ' <span class="label label-warning">' . TEXT_INACTIVE_AGENCY . '</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo zen_output_string_protected($units->fields['unit_identifier']); ?></td>
                    <td><?php echo zen_output_string_protected($units->fields['unit_name']); ?></td>
                    <td class="text-center">
                        <?php if ((int)$units->fields['status'] === 1) { ?>
                            <span class="label label-success"><?php echo TEXT_ACTIVE; ?></span>
                        <?php } else { ?>
                            <span class="label label-default"><?php echo TEXT_INACTIVE; ?></span>
                        <?php } ?>
                    </td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-primary" href="<?php echo zen_href_link(FILENAME_IEMS_UNITS, 'action=edit&unit_id=' . $unitId); ?>">
                            <?php echo TEXT_EDIT; ?>
                        </a>
                        <?php
                        $isActive = (int)$units->fields['status'] === 1;
                        $statusAction = $isActive ? 'deactivate' : 'reactivate';
                        $statusLabel = $isActive ? TEXT_DEACTIVATE : TEXT_REACTIVATE;
                        $confirmation = $isActive ? TEXT_CONFIRM_DEACTIVATE : TEXT_CONFIRM_REACTIVATE;
                        echo zen_draw_form(
                            'iems_unit_status_' . $unitId,
                            FILENAME_IEMS_UNITS,
                            '',
                            'post',
                            'class="form-inline" style="display:inline" onsubmit="return confirm(\''
                                . zen_output_string_protected($confirmation) . '\')"'
                        );
                        echo zen_draw_hidden_field('action', $statusAction);
                        echo zen_draw_hidden_field('unit_id', (string)$unitId);
                        ?>
                        <button type="submit" class="btn btn-xs <?php echo $isActive ? 'btn-warning' : 'btn-success'; ?>">
                            <?php echo $statusLabel; ?>
                        </button>
                        <?= '</form>' ?>
                    </td>
                </tr>
                <?php $units->MoveNext(); ?>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?php
            echo $unitsSplit->display_count(
                $unitsQueryNumRows,
                MAX_DISPLAY_SEARCH_RESULTS,
                $currentPage,
                TEXT_DISPLAY_NUMBER_OF_UNITS
            );
            ?>
        </div>
        <div class="col-sm-6 text-right">
            <?php
            echo $unitsSplit->display_links(
                $unitsQueryNumRows,
                MAX_DISPLAY_SEARCH_RESULTS,
                MAX_DISPLAY_PAGE_LINKS,
                $currentPage,
                $paginationParameters
            );
            ?>
        </div>
    </div>
</div>
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
