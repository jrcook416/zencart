<?php
/**
 * Page Template
 * 
 * BOOTSTRAP v3.8.0
 *
 * Loaded automatically by index.php?main_page=account_edit.
 * View or change Customer Account Information
 *
 * @package templateSystem
 * @copyright Copyright 2003-2016 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: rbarbour zcadditions.com Fri Feb 26 00:03:33 2016 -0500 Modified in v1.5.5 $
 */
?>
<div id="accountEditDefault" class="centerColumn">
    <?= zen_draw_form('account_edit', zen_href_link(FILENAME_ACCOUNT_EDIT, '', 'SSL'), 'post', 'onsubmit="return check_form(account_edit);"') . zen_draw_hidden_field('action', 'process') ?>

<?php
if ($messageStack->size('account_edit') > 0){
    echo $messageStack->output('account_edit');
}
?>
<?php
$iemsCountyOptions          = is_array($GLOBALS['iems_county_options'] ?? null) ? $GLOBALS['iems_county_options'] : [];
$iemsAgencyOptionsByCounty  = is_array($GLOBALS['iems_agency_options_by_county'] ?? null) ? $GLOBALS['iems_agency_options_by_county'] : [];
$iemsSelectedCountyId       = (int)($GLOBALS['iems_selected_county_id'] ?? 0);
$iemsSelectedAgencyId       = (int)($GLOBALS['iems_selected_agency_id'] ?? 0);
$iemsAgencyOptions          = $iemsAgencyOptionsByCounty[$iemsSelectedCountyId] ?? [];
$iemsPlaceholder            = defined('PULL_DOWN_DEFAULT') ? PULL_DOWN_DEFAULT : 'Please Select';
$iemsCountyLabel            = defined('ENTRY_IEMS_COUNTY') ? ENTRY_IEMS_COUNTY : 'County';
$iemsAgencyLabel            = defined('ENTRY_IEMS_AGENCY') ? ENTRY_IEMS_AGENCY : 'Agency';
$iemsAffiliationHeading     = defined('HEADING_IEMS_AFFILIATION') ? HEADING_IEMS_AFFILIATION : 'Affiliation Details';
$iemsAgencyOptionsJson      = json_encode(
    $iemsAgencyOptionsByCounty,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);

// True when the admin lock toggle (IEMS_ACCOUNT_EDIT_LOCK_ENABLED) is on.
$iemsAccountEditLocked = !empty($GLOBALS['iems_account_edit_locked']);

// Pre-resolve display text for locked county/agency.
$iemsSelectedCountyText = '';
$iemsSelectedAgencyText = '';
if ($iemsAccountEditLocked) {
    foreach ($iemsCountyOptions as $c) {
        if ((int)$c['id'] === $iemsSelectedCountyId) {
            $iemsSelectedCountyText = htmlspecialchars((string)$c['text'], ENT_QUOTES, CHARSET);
            break;
        }
    }
    foreach ($iemsAgencyOptionsByCounty[$iemsSelectedCountyId] ?? [] as $a) {
        if ((int)$a['id'] === $iemsSelectedAgencyId) {
            $iemsSelectedAgencyText = htmlspecialchars((string)$a['text'], ENT_QUOTES, CHARSET);
            break;
        }
    }
}
?>
<!--bof iems affiliation card-->
    <div id="iemsAffiliation-card" class="card mb-3">
        <div id="iemsAffiliation-card-header" class="card-header"><h4><?= $iemsAffiliationHeading ?></h4></div>
        <div id="iemsAffiliation-card-body" class="card-body p-3">
            <label class="inputLabel"><?= $iemsCountyLabel ?></label>
<?php if ($iemsAccountEditLocked): ?>
            <p class="form-control-plaintext"><?= $iemsSelectedCountyText ?: '&mdash;' ?></p>
            <input type="hidden" name="iems_county_id" value="<?= $iemsSelectedCountyId ?>">

            <label class="inputLabel"><?= $iemsAgencyLabel ?></label>
            <p class="form-control-plaintext"><?= $iemsSelectedAgencyText ?: '&mdash;' ?></p>
            <input type="hidden" name="iems_agency_id" value="<?= $iemsSelectedAgencyId ?>">
<?php else: ?>
            <select name="iems_county_id" id="iems-county-id-edit" class="form-control" required>
                <option value=""><?= htmlspecialchars($iemsPlaceholder, ENT_QUOTES, CHARSET, true) ?></option>
<?php
    foreach ($iemsCountyOptions as $countyOption) {
        $countyId   = (int)($countyOption['id'] ?? 0);
        $countyText = htmlspecialchars((string)($countyOption['text'] ?? ''), ENT_QUOTES, CHARSET, true);
        $selected   = ($countyId === $iemsSelectedCountyId) ? ' selected' : '';
        echo '                <option value="' . $countyId . '"' . $selected . '>' . $countyText . '</option>' . "\n";
    }
?>
            </select>

            <div class="p-2"></div>
            <label class="inputLabel" for="iems-agency-id-edit"><?= $iemsAgencyLabel ?></label>
            <select name="iems_agency_id" id="iems-agency-id-edit" class="form-control" required>
                <option value=""><?= htmlspecialchars($iemsPlaceholder, ENT_QUOTES, CHARSET, true) ?></option>
<?php
    foreach ($iemsAgencyOptions as $agencyOption) {
        $agencyId   = (int)($agencyOption['id'] ?? 0);
        $agencyText = htmlspecialchars((string)($agencyOption['text'] ?? ''), ENT_QUOTES, CHARSET, true);
        $selected   = ($agencyId === $iemsSelectedAgencyId) ? ' selected' : '';
        echo '                <option value="' . $agencyId . '"' . $selected . '>' . $agencyText . '</option>' . "\n";
    }
?>
            </select>
<?php endif; ?>
        </div>
    </div>
<!--eof iems affiliation card-->
<?php if (!$iemsAccountEditLocked): ?>
<script>
(() => {
    const countySelect = document.getElementById('iems-county-id-edit');
    const agencySelect = document.getElementById('iems-agency-id-edit');
    if (!countySelect || !agencySelect) {
        return;
    }

    const agencyOptionsByCounty = <?= $iemsAgencyOptionsJson ?: '{}' ?>;
    const initialAgency         = '<?= (int)$iemsSelectedAgencyId ?>';
    const placeholder           = '<?= htmlspecialchars($iemsPlaceholder, ENT_QUOTES, CHARSET, true) ?>';

    const rebuildAgencies = (preserveSelected = false) => {
        const countyId       = countySelect.value;
        const agencies       = agencyOptionsByCounty[countyId] || [];
        const selectedAgency = preserveSelected ? agencySelect.value : initialAgency;

        agencySelect.innerHTML = '';
        agencySelect.append(new Option(placeholder, ''));
        agencies.forEach((agency) => {
            const option = new Option(agency.text, String(agency.id));
            if (String(agency.id) === String(selectedAgency)) {
                option.selected = true;
            }
            agencySelect.append(option);
        });
    };

    countySelect.addEventListener('change', () => rebuildAgencies(false));
    rebuildAgencies(true);
})();
</script>
<?php endif; ?>

<!--bof my account information card-->
    <div id="myAccountInfo-card" class="card mb-3">
        <div id="myAccountInfo-card-header" class="card-header"><?= '<h2>' . HEADING_TITLE . '</h2>' ?></div>
        <div id="myAccountInfo-card-body" class="card-body p-3">
            <div class="required-info text-right"><?= FORM_REQUIRED_INFORMATION ?></div>
<?php
if (zen_config('ACCOUNT_GENDER') === 'true') {
    if ($iemsAccountEditLocked) {
?>
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('gender', 'm', ($account->fields['customers_gender'] === 'm'), 'id="gender-male" disabled') . '<label class="custom-control-label radioButtonLabel" for="gender-male">' . MALE . '</label>' ?>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('gender', 'f', ($account->fields['customers_gender'] === 'f'), 'id="gender-female" disabled') . '<label class="custom-control-label radioButtonLabel" for="gender-female">' . FEMALE . '</label>' ?>
            </div>
            <div class="p-2"></div>
<?php
    } else {
?>
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('gender', 'm', '1', 'id="gender-male"') . '<label class="custom-control-label radioButtonLabel" for="gender-male">' . MALE . '</label>' ?>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('gender', 'f', '', 'id="gender-female"') . '<label class="custom-control-label radioButtonLabel" for="gender-female">' . FEMALE . '</label>' ?>
            </div>
            <div class="p-2"></div>
<?php
    }
}
?>
            <label class="inputLabel" for="firstname"><?= ENTRY_FIRST_NAME ?></label>
            <?= zen_draw_input_field('firstname', $account->fields['customers_firstname'], 'id="firstname" placeholder="' . ENTRY_FIRST_NAME_TEXT . '"' . ((int)zen_config('ENTRY_FIRST_NAME_MIN_LENGTH ')> 0 ? ' required' : '') . ($iemsAccountEditLocked ? ' readonly' : '')) ?>
            <div class="p-2"></div>

            <label class="inputLabel" for="lastname"><?= ENTRY_LAST_NAME ?></label>
            <?= zen_draw_input_field('lastname', $account->fields['customers_lastname'], 'id="lastname" placeholder="' . ENTRY_LAST_NAME_TEXT . '"' . ((int)zen_config('ENTRY_LAST_NAME_MIN_LENGTH') > 0 ? ' required' : '') . ($iemsAccountEditLocked ? ' readonly' : '')) ?>
            <div class="p-2"></div>
<?php
if (zen_config('ACCOUNT_DOB') === 'true') {
?>
            <label class="inputLabel" for="dob"><?= ENTRY_DATE_OF_BIRTH ?></label>
            <?= zen_draw_input_field('dob', zen_date_short($account->fields['customers_dob']), 'id="dob" placeholder="' . ENTRY_DATE_OF_BIRTH_TEXT . '"' . (ACCOUNT_DOB == 'true' && (int)zen_config('ENTRY_DOB_MIN_LENGTH') != 0 ? ' required' : '') . ($iemsAccountEditLocked ? ' readonly' : '')) ?>
            <div class="p-2"></div>
<?php
}
?>
            <label class="inputLabel" for="email-address"><?= ENTRY_EMAIL_ADDRESS ?></label>
            <?= zen_draw_input_field('email_address', $account->fields['customers_email_address'], 'id="email-address" placeholder="' . ENTRY_EMAIL_ADDRESS_TEXT . '"'. ((int)zen_config('ENTRY_EMAIL_ADDRESS_MIN_LENGTH') > 0 ? ' required' : '') . ($iemsAccountEditLocked ? ' readonly' : ''), 'email') ?>
            <div class="p-2"></div>

            <label class="inputLabel" for="telephone"><?= ENTRY_TELEPHONE_NUMBER ?></label>
            <?= zen_draw_input_field('telephone', $account->fields['customers_telephone'], 'id="telephone" placeholder="' . ENTRY_TELEPHONE_NUMBER_TEXT . '"' . ((int)zen_config('ENTRY_TELEPHONE_MIN_LENGTH') > 0 ? ' required' : ''), 'tel') ?>
            <div class="p-2"></div>
<?php
if (zen_config('ACCOUNT_FAX_NUMBER') === 'true' ) {
?>
            <label class="inputLabel" for="fax"><?= ENTRY_FAX_NUMBER ?></label>
            <?= zen_draw_input_field('fax', $account->fields['customers_fax'], 'id="fax" placeholder="' . ENTRY_FAX_NUMBER_TEXT . '"' . ($iemsAccountEditLocked ? ' readonly' : ''), 'tel') ?>
            <div class="p-2"></div>
<?php 
}

if (zen_config('CUSTOMERS_REFERRAL_STATUS') === '2') {
    // Always display referral as read-only text when lock is active; otherwise use the default editable/display logic.
    if ($iemsAccountEditLocked) {
?>
            <label for="customers-referral-readonly"><?= ENTRY_CUSTOMERS_REFERRAL ?></label>
            <?= htmlspecialchars((string)$customers_referral, ENT_QUOTES, CHARSET) . zen_draw_hidden_field('customers_referral', $customers_referral, 'id="customers-referral-readonly"') ?>
            <div class="p-2"></div>
<?php
    } elseif ($customers_referral == '') {
?>
            <label class="inputLabel" for="customers-referral"><?= ENTRY_CUSTOMERS_REFERRAL ?></label>
            <?= zen_draw_input_field('customers_referral', '', zen_set_field_length(TABLE_CUSTOMERS, 'customers_referral', 15) . 'id="customers-referral"') ?>
            <div class="p-2"></div>
<?php
    } else {
?>
            <label for="customers-referral-readonly"><?= ENTRY_CUSTOMERS_REFERRAL ?></label>
            <?= $customers_referral . zen_draw_hidden_field('customers_referral', $customers_referral,'id="customers-referral-readonly"') ?>
            <div class="p-2"></div>
<?php
    }
}
?>
        </div>
    </div>
<!--eof my account information card-->

<!--bof newsletter and email details card-->
    <div id="details-card" class="card mb-3">
        <h4 id="details-card-header" class="card-header"><?= ENTRY_EMAIL_PREFERENCE ?></h4>
        <div id="details-card-body" class="card-body p-3">
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('email_format', 'HTML', $email_pref_html, 'id="email-format-html"' . ($iemsAccountEditLocked ? ' disabled' : '')) . '<label class="custom-control-label" for="email-format-html">' . ENTRY_EMAIL_HTML_DISPLAY . '</label>' ?>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <?= zen_draw_radio_field('email_format', 'TEXT', $email_pref_text, 'id="email-format-text"' . ($iemsAccountEditLocked ? ' disabled' : '')) . '<label class="custom-control-label" for="email-format-text">' . ENTRY_EMAIL_TEXT_DISPLAY . '</label>' ?>
            </div>
        </div>
    </div>
<!--eof newsletter and email details card-->

    <div id="accountEditDefault-btn-toolbar" class="btn-toolbar justify-content-between" role="toolbar">
        <?= zca_button_link(zen_href_link(FILENAME_ACCOUNT, '', 'SSL'), BUTTON_BACK_ALT, 'button_back') ?>
        <?= zen_image_submit(BUTTON_IMAGE_UPDATE , BUTTON_UPDATE_ALT) ?>
    </div>
    <?= '</form>' ?>
</div>
