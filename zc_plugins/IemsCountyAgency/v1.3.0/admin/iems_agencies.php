<?php

declare(strict_types=1);

use Zencart\Plugins\Admin\IemsCountyAgency\IemsAgencyInput;

require 'includes/application_top.php';

if (!zen_is_superuser() && !check_page(FILENAME_IEMS_AGENCIES, [])) {
    zen_record_admin_activity(
        'Attempted access to unauthorized page [iems_agencies]. Redirected to DENIED page instead.',
        'notice'
    );
    zen_redirect(zen_href_link(FILENAME_DENIED, '', 'SSL'));
}

/**
 * @return array<string, mixed>|null
 */
function iems_get_agency(int $agencyId): ?array
{
    global $db;

    $sql =
        "SELECT agency_ID, county_ID, agency_identifier, agency_name, status
           FROM " . TABLE_IEMS_AGENCIES . "
          WHERE agency_ID = :agencyId
          LIMIT 1";
    $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
    $result = $db->Execute($sql);

    return $result->EOF ? null : $result->fields;
}

function iems_county_exists(int $countyId): bool
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

function iems_agency_identifier_exists(int $countyId, string $identifier, int $excludeAgencyId = 0): bool
{
    global $db;

    $sql =
        "SELECT agency_ID
           FROM " . TABLE_IEMS_AGENCIES . "
          WHERE county_ID = :countyId
            AND agency_identifier = :identifier";
    $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
    $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
    if ($excludeAgencyId > 0) {
        $sql .= " AND agency_ID <> :agencyId";
        $sql = $db->bindVars($sql, ':agencyId', $excludeAgencyId, 'integer');
    }
    $sql .= " LIMIT 1";

    return !$db->Execute($sql)->EOF;
}

/**
 * @return array<int, array{id: int, text: string, status: int}>
 */
function iems_get_counties(): array
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

function iems_add_input_errors(array $errorCodes): void
{
    global $messageStack;

    $messages = [
        'county_required' => ERROR_COUNTY_REQUIRED,
        'county_invalid' => ERROR_COUNTY_INVALID,
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

function iems_redirect_with_message(string $message, int $agencyId): never
{
    global $messageStack;

    $messageStack->add_session(sprintf($message, $agencyId), 'success');
    zen_redirect(zen_href_link(FILENAME_IEMS_AGENCIES, 'agency_id=' . $agencyId, 'SSL'));
}

$counties = iems_get_counties();
$action = IemsAgencyInput::scalar($_GET['action'] ?? '') ?? '__invalid__';
$allowedDisplayActions = ['', 'new', 'edit'];
$formMode = '';
$formValues = [
    'county_id' => '',
    'agency_identifier' => '',
    'agency_name' => '',
];
$formAgencyId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = IemsAgencyInput::scalar($_POST['action'] ?? null);
    if (!in_array($postAction, ['save', 'deactivate', 'reactivate'], true)) {
        $messageStack->add(ERROR_INVALID_ACTION, 'error');
    } elseif ($postAction === 'save') {
        $agencyIdRaw = IemsAgencyInput::scalar($_POST['agency_id'] ?? null);
        $isCreate = $agencyIdRaw === '';
        $agencyId = $isCreate ? 0 : IemsAgencyInput::positiveId($agencyIdRaw);
        $input = IemsAgencyInput::validate($_POST);
        $formValues = $input['values'];
        $formMode = $isCreate ? 'new' : 'edit';
        $formAgencyId = $agencyId ?? 0;

        if (!$isCreate && $agencyId === null) {
            $input['errors'][] = 'agency_id_invalid';
            $messageStack->add(ERROR_INVALID_AGENCY_ID, 'error');
        }

        $existingAgency = $agencyId !== null && $agencyId > 0 ? iems_get_agency($agencyId) : null;
        if (!$isCreate && $agencyId !== null && $existingAgency === null) {
            $input['errors'][] = 'agency_not_found';
            $messageStack->add(ERROR_AGENCY_NOT_FOUND, 'error');
        }

        $countyId = IemsAgencyInput::positiveId($formValues['county_id']);
        if ($countyId !== null && !iems_county_exists($countyId)) {
            $input['errors'][] = 'county_not_found';
            $messageStack->add(ERROR_COUNTY_INVALID, 'error');
        }

        if (
            $countyId !== null
            && $formValues['agency_identifier'] !== ''
            && preg_match('/^[A-Z0-9]{1,10}$/D', $formValues['agency_identifier']) === 1
            && iems_agency_identifier_exists($countyId, $formValues['agency_identifier'], $agencyId ?? 0)
        ) {
            $input['errors'][] = 'identifier_duplicate';
            $messageStack->add(ERROR_DUPLICATE_IDENTIFIER, 'error');
        }

        iems_add_input_errors($input['errors']);

        if ($input['errors'] === []) {
            $identifier = $formValues['agency_identifier'];
            $name = $formValues['agency_name'];
            if ($isCreate) {
                $sql =
                    "INSERT INTO " . TABLE_IEMS_AGENCIES . "
                        (county_ID, agency_identifier, agency_name, status, date_added, last_modified)
                     VALUES
                        (:countyId, :identifier, :agencyName, 1, NOW(), NULL)";
                $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
                $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
                $sql = $db->bindVars($sql, ':agencyName', $name, 'string');
                $db->Execute($sql);
                $agencyId = (int)$db->insert_ID();
                zen_record_admin_activity(
                    'IEMS agency created with ID ' . $agencyId . ', county ID ' . $countyId,
                    'info'
                );
                iems_redirect_with_message(SUCCESS_AGENCY_CREATED, $agencyId);
            }

            $oldCountyId = (int)$existingAgency['county_ID'];
            $db->Execute('START TRANSACTION');
            $sql =
                "UPDATE " . TABLE_IEMS_AGENCIES . "
                    SET county_ID = :countyId,
                        agency_identifier = :identifier,
                        agency_name = :agencyName,
                        last_modified = NOW()
                  WHERE agency_ID = :agencyId";
            $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
            $sql = $db->bindVars($sql, ':identifier', $identifier, 'string');
            $sql = $db->bindVars($sql, ':agencyName', $name, 'string');
            $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
            $db->Execute($sql);

            if ($oldCountyId !== $countyId) {
                $sql =
                    "UPDATE " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . "
                        SET county_ID = :countyId,
                            last_modified = NOW()
                      WHERE agency_ID = :agencyId";
                $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
                $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
                $db->Execute($sql);

                $sql =
                    "UPDATE " . TABLE_IEMS_UNITS . "
                        SET county_ID = :countyId,
                            last_modified = NOW()
                      WHERE agency_ID = :agencyId";
                $sql = $db->bindVars($sql, ':countyId', $countyId, 'integer');
                $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
                $db->Execute($sql);
            }
            $db->Execute('COMMIT');

            zen_record_admin_activity(
                'IEMS agency updated for ID ' . $agencyId
                    . ', county ID ' . $oldCountyId . ' to ' . $countyId,
                'info'
            );
            iems_redirect_with_message(SUCCESS_AGENCY_UPDATED, $agencyId);
        }
    } else {
        $agencyId = IemsAgencyInput::positiveId($_POST['agency_id'] ?? null);
        if ($agencyId === null) {
            $messageStack->add(ERROR_INVALID_AGENCY_ID, 'error');
        } else {
            $agency = iems_get_agency($agencyId);
            if ($agency === null) {
                $messageStack->add(ERROR_AGENCY_NOT_FOUND, 'error');
            } else {
                $newStatus = $postAction === 'reactivate' ? 1 : 0;
                if ((int)$agency['status'] === $newStatus) {
                    $messageStack->add(ERROR_STATUS_ALREADY_SET, 'error');
                } else {
                    $sql =
                        "UPDATE " . TABLE_IEMS_AGENCIES . "
                            SET status = :status,
                                last_modified = NOW()
                          WHERE agency_ID = :agencyId";
                    $sql = $db->bindVars($sql, ':status', $newStatus, 'integer');
                    $sql = $db->bindVars($sql, ':agencyId', $agencyId, 'integer');
                    $db->Execute($sql);
                    $activity = $newStatus === 1 ? 'reactivated' : 'deactivated';
                    zen_record_admin_activity('IEMS agency ' . $activity . ' for ID ' . $agencyId, 'notice');
                    iems_redirect_with_message(
                        $newStatus === 1 ? SUCCESS_AGENCY_REACTIVATED : SUCCESS_AGENCY_DEACTIVATED,
                        $agencyId
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
    $agencyId = IemsAgencyInput::positiveId($_GET['agency_id'] ?? null);
    if ($agencyId === null) {
        $messageStack->add(ERROR_INVALID_AGENCY_ID, 'error');
        $action = '';
    } else {
        $agency = iems_get_agency($agencyId);
        if ($agency === null) {
            $messageStack->add(ERROR_AGENCY_NOT_FOUND, 'error');
            $action = '';
        } else {
            $formMode = 'edit';
            $formAgencyId = $agencyId;
            $formValues = [
                'county_id' => (string)$agency['county_ID'],
                'agency_identifier' => (string)$agency['agency_identifier'],
                'agency_name' => (string)$agency['agency_name'],
            ];
        }
    }
}

$search = IemsAgencyInput::scalar($_GET['search'] ?? '') ?? '';
if (mb_strlen($search) > 128) {
    $search = '';
    $messageStack->add(ERROR_SEARCH_LENGTH, 'error');
}
$statusFilter = IemsAgencyInput::scalar($_GET['status'] ?? 'all') ?? 'all';
if (!in_array($statusFilter, ['all', 'active', 'inactive'], true)) {
    $statusFilter = 'all';
    $messageStack->add(ERROR_INVALID_ACTION, 'error');
}
$countyFilterRaw = IemsAgencyInput::scalar($_GET['county_id'] ?? '') ?? '';
$countyFilter = $countyFilterRaw === '' ? 0 : IemsAgencyInput::positiveId($countyFilterRaw);
if ($countyFilter === null) {
    $countyFilter = 0;
    $messageStack->add(ERROR_COUNTY_INVALID, 'error');
} elseif ($countyFilter > 0 && !iems_county_exists($countyFilter)) {
    $countyFilter = 0;
    $messageStack->add(ERROR_COUNTY_INVALID, 'error');
}

$where = ['1 = 1'];
if ($statusFilter !== 'all') {
    $where[] = 'a.status = ' . ($statusFilter === 'active' ? '1' : '0');
}
if ($countyFilter > 0) {
    $where[] = 'a.county_ID = ' . $countyFilter;
}
if ($search !== '') {
    $searchSql = $db->bindVars(':search', ':search', '%' . $search . '%', 'string');
    $where[] =
        "(a.agency_identifier LIKE " . $searchSql
        . " OR a.agency_name LIKE " . $searchSql
        . " OR c.county_number LIKE " . $searchSql
        . " OR c.county_name LIKE " . $searchSql . ")";
}
$whereSql = implode(' AND ', $where);
$agenciesQueryRaw =
    "SELECT a.agency_ID, a.county_ID, a.agency_identifier, a.agency_name, a.status,
            c.county_number, c.county_name, c.status AS county_status,
            COALESCE(af.affiliation_count, 0) AS affiliation_count
       FROM " . TABLE_IEMS_AGENCIES . " a
       JOIN " . TABLE_IEMS_COUNTIES . " c ON c.county_ID = a.county_ID
       LEFT JOIN (
            SELECT agency_ID, COUNT(*) AS affiliation_count
              FROM " . TABLE_IEMS_CUSTOMER_AFFILIATIONS . "
             GROUP BY agency_ID
       ) af ON af.agency_ID = a.agency_ID
      WHERE " . $whereSql . "
      ORDER BY CAST(c.county_number AS UNSIGNED), a.agency_identifier, a.agency_name";
$currentPage = IemsAgencyInput::positiveId($_GET['page'] ?? null) ?? 1;
$agenciesQueryNumRows = 0;
$agenciesSplit = new splitPageResults(
    $currentPage,
    MAX_DISPLAY_SEARCH_RESULTS,
    $agenciesQueryRaw,
    $agenciesQueryNumRows
);
$agencies = $db->Execute($agenciesQueryRaw);

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
                    <?php echo $formMode === 'new' ? HEADING_NEW_AGENCY : sprintf(HEADING_EDIT_AGENCY, $formAgencyId); ?>
                </h2>
            </div>
            <div class="panel-body">
                <?php echo zen_draw_form('iems_agency', FILENAME_IEMS_AGENCIES, '', 'post', 'class="form-horizontal"'); ?>
                <?php echo zen_draw_hidden_field('action', 'save'); ?>
                <?php echo zen_draw_hidden_field('agency_id', $formMode === 'new' ? '' : (string)$formAgencyId); ?>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="county_id"><?php echo TEXT_COUNTY; ?></label>
                    <div class="col-sm-9">
                        <?php
                        $countyOptions = [['id' => '', 'text' => TEXT_ALL_COUNTIES]];
                        foreach ($counties as $county) {
                            $countyOptions[] = ['id' => $county['id'], 'text' => $county['text']];
                        }
                        echo zen_draw_pull_down_menu(
                            'county_id',
                            $countyOptions,
                            $formValues['county_id'],
                            'id="county_id" class="form-control" required'
                        );
                        ?>
                        <p class="help-block"><?php echo TEXT_REASSIGNMENT_CONSEQUENCE; ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="agency_identifier"><?php echo TEXT_AGENCY_IDENTIFIER; ?></label>
                    <div class="col-sm-9">
                        <?php
                        echo zen_draw_input_field(
                            'agency_identifier',
                            $formValues['agency_identifier'],
                            'id="agency_identifier" class="form-control" maxlength="10" pattern="[A-Z0-9]+" required'
                        );
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3" for="agency_name"><?php echo TEXT_AGENCY_NAME; ?></label>
                    <div class="col-sm-9">
                        <?php
                        echo zen_draw_input_field(
                            'agency_name',
                            $formValues['agency_name'],
                            'id="agency_name" class="form-control" maxlength="128" required'
                        );
                        ?>
                    </div>
                </div>
                <div class="text-right">
                    <button type="submit" class="btn btn-primary"><?php echo TEXT_SAVE; ?></button>
                    <a class="btn btn-default" href="<?php echo zen_href_link(FILENAME_IEMS_AGENCIES); ?>">
                        <?php echo TEXT_CANCEL; ?>
                    </a>
                </div>
                <?= '</form>' ?>
            </div>
        </div>
    <?php } ?>

    <div class="panel panel-default">
        <div class="panel-body">
            <?php echo zen_draw_form('iems_agency_filter', FILENAME_IEMS_AGENCIES, '', 'get', 'class="form-inline"'); ?>
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
                $filterCountyOptions = [['id' => '', 'text' => TEXT_ALL_COUNTIES]];
                foreach ($counties as $county) {
                    $filterCountyOptions[] = ['id' => $county['id'], 'text' => $county['text']];
                }
                echo zen_draw_pull_down_menu(
                    'county_id',
                    $filterCountyOptions,
                    $countyFilter,
                    'id="filter_county_id" class="form-control"'
                );
                ?>
            </div>
            <button type="submit" class="btn btn-default"><?php echo TEXT_FILTER; ?></button>
            <a class="btn btn-link" href="<?php echo zen_href_link(FILENAME_IEMS_AGENCIES); ?>">
                <?php echo TEXT_CLEAR; ?>
            </a>
            <?= '</form>' ?>
            <a class="btn btn-primary pull-right" href="<?php echo zen_href_link(FILENAME_IEMS_AGENCIES, 'action=new'); ?>">
                <?php echo TEXT_CREATE_AGENCY; ?>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <th><?php echo TEXT_AGENCY_ID; ?></th>
                <th><?php echo TEXT_COUNTY; ?></th>
                <th><?php echo TEXT_AGENCY_IDENTIFIER; ?></th>
                <th><?php echo TEXT_AGENCY_NAME; ?></th>
                <th class="text-center"><?php echo TEXT_AFFILIATED_CUSTOMERS; ?></th>
                <th class="text-center"><?php echo TEXT_STATUS; ?></th>
                <th class="text-right"><?php echo TEXT_ACTIONS; ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($agencies->EOF) { ?>
                <tr>
                    <td colspan="7" class="text-center"><?php echo TEXT_NO_AGENCIES; ?></td>
                </tr>
            <?php } ?>
            <?php while (!$agencies->EOF) { ?>
                <?php $agencyId = (int)$agencies->fields['agency_ID']; ?>
                <tr>
                    <td><?php echo $agencyId; ?></td>
                    <td>
                        <?php
                        echo zen_output_string_protected(
                            trim($agencies->fields['county_number'] . ' ' . $agencies->fields['county_name'])
                        );
                        if ((int)$agencies->fields['county_status'] !== 1) {
                            echo ' <span class="label label-warning">' . TEXT_INACTIVE_COUNTY . '</span>';
                        }
                        ?>
                    </td>
                    <td><?php echo zen_output_string_protected($agencies->fields['agency_identifier']); ?></td>
                    <td><?php echo zen_output_string_protected($agencies->fields['agency_name']); ?></td>
                    <td class="text-center"><?php echo (int)$agencies->fields['affiliation_count']; ?></td>
                    <td class="text-center">
                        <?php if ((int)$agencies->fields['status'] === 1) { ?>
                            <span class="label label-success"><?php echo TEXT_ACTIVE; ?></span>
                        <?php } else { ?>
                            <span class="label label-default"><?php echo TEXT_INACTIVE; ?></span>
                        <?php } ?>
                    </td>
                    <td class="text-right">
                        <a class="btn btn-xs btn-primary" href="<?php echo zen_href_link(FILENAME_IEMS_AGENCIES, 'action=edit&agency_id=' . $agencyId); ?>">
                            <?php echo TEXT_EDIT; ?>
                        </a>
                        <?php
                        $isActive = (int)$agencies->fields['status'] === 1;
                        $statusAction = $isActive ? 'deactivate' : 'reactivate';
                        $statusLabel = $isActive ? TEXT_DEACTIVATE : TEXT_REACTIVATE;
                        $confirmation = $isActive ? TEXT_CONFIRM_DEACTIVATE : TEXT_CONFIRM_REACTIVATE;
                        echo zen_draw_form(
                            'iems_agency_status_' . $agencyId,
                            FILENAME_IEMS_AGENCIES,
                            '',
                            'post',
                            'class="form-inline" style="display:inline" onsubmit="return confirm(\''
                                . zen_output_string_protected($confirmation) . '\')"'
                        );
                        echo zen_draw_hidden_field('action', $statusAction);
                        echo zen_draw_hidden_field('agency_id', (string)$agencyId);
                        ?>
                        <button type="submit" class="btn btn-xs <?php echo $isActive ? 'btn-warning' : 'btn-success'; ?>">
                            <?php echo $statusLabel; ?>
                        </button>
                        <?= '</form>' ?>
                    </td>
                </tr>
                <?php $agencies->MoveNext(); ?>
            <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <?php
            echo $agenciesSplit->display_count(
                $agenciesQueryNumRows,
                MAX_DISPLAY_SEARCH_RESULTS,
                $currentPage,
                TEXT_DISPLAY_NUMBER_OF_AGENCIES
            );
            ?>
        </div>
        <div class="col-sm-6 text-right">
            <?php
            echo $agenciesSplit->display_links(
                $agenciesQueryNumRows,
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
