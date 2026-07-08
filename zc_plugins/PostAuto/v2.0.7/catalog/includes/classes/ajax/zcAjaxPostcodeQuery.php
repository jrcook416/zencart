<?php
/**
 * @copyright Copyright 2003-2025 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: pilou2 2025 April 17 Modified in v2.1.0 $
*/

class zcAjaxPostcodeQuery extends base
{
    public function postcodequery()
    {
        $zone = array();
        if (isset($_POST['postcode']) and isset($_POST['country'])) { // uses postcode to define zone, limited to country where postcode zones have been put in database
            $conn = new PDO("mysql:host=" . constant('DB_SERVER') . ";dbname=" . constant('DB_DATABASE') . "", DB_SERVER_USERNAME, DB_SERVER_PASSWORD);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $reqcountcode = $conn->prepare("SELECT countries_iso_code_3 FROM " . TABLE_COUNTRIES . " WHERE countries_id = ?;");
            $reqcountcode->execute([$_POST['country']]);
            if (!empty($reqcountcode)) {
                $codeiso = $reqcountcode->fetchAll()[0]['countries_iso_code_3'];
            } else {
                return;
            }
            $citydata = (!empty($_POST['city'])) ? ' AND zone_city_name = :city' : '';

            switch ($codeiso) {
                case 'JPN':
                    if ($_SESSION['language'] === 'japanese') {
                        $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name, zone_street_name FROM " . TABLE_ZONES_TO_POST_CODE_JP . " WHERE post_code = :postcode" . $citydata . ";");
                    } else {
                        $request = $conn->prepare("SELECT zone_id, zone_name_romaji AS zone_name, zone_city_name_romaji AS zone_city_name, zone_street_name_romaji AS zone_street_name FROM " . TABLE_ZONES_TO_POST_CODE_JP . " WHERE post_code = :postcode" . $citydata . ";");
                    }
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    if (!empty($citydata)) $request->bindParam(':city', $_POST['city'], PDO::PARAM_STR);
                    break;
                case 'FRA':
                case 'GUF':
                case 'PYF':
                case 'ATF':
                case 'GLP':
                case 'MTQ':
                case 'MYT':
                case 'MCO':
                case 'REU':
                case 'WLF':
                case 'SPM':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name, zone_street_name FROM " . TABLE_ZONES_TO_POST_CODE_FR . " WHERE post_code = :postcode AND zone_country_id = :country" . $citydata . ";");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    $request->bindParam(':country', $_POST['country'], PDO::PARAM_INT);
                    if (!empty($citydata)) $request->bindParam(':city', $_POST['city'], PDO::PARAM_STR);
                    break;
                case 'PLW':
                case 'USA':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_US . " WHERE post_code = :postcode AND zone_country_id = :country;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    $request->bindParam(':country', $_POST['country'], PDO::PARAM_INT);
                    break;
                case 'ESP':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_ES . " WHERE post_code = :postcode;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    break;
                case 'DEU':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_DE . " WHERE post_code = :postcode;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    break;
                case 'ITA':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_IT . " WHERE post_code = :postcode;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    break;
                case 'LIE':
                case 'CHE':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_CH . " WHERE post_code = :postcode AND zone_country_id = :country;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    $request->bindParam(':country', $_POST['country'], PDO::PARAM_INT);
                    break;
                case 'AUS':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_AU . " WHERE post_code = :postcode;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    break;
                case 'AUT':
                    $request = $conn->prepare("SELECT zone_id, zone_name, zone_city_name FROM " . TABLE_ZONES_TO_POST_CODE_AT . " WHERE post_code = :postcode;");
                    $request->bindParam(':postcode', $_POST['postcode'], PDO::PARAM_STR);
                    break;
                default:
                    $request = null;
                    break;
            }

            if (!empty($request)){
                try {
                    $request->execute();
                    foreach($request as $v) {
                        $state = (!empty($v['zone_id']) && ACCOUNT_STATE_DRAW_INITIAL_DROPDOWN === 'true') ? $v['zone_id'] : $v['zone_name'];
                        $suburb_street = (empty($v['zone_street_name'])) ? '' : $v['zone_street_name'];
                        $zone[] = array('zone_id' => $state, 'city' => $v['zone_city_name'], 'street_name' => $suburb_street);
                    }
                    return $zone;
                } catch(PDOException $e) {
                    return "Connection failed: " . $e->getMessage();
                }
            }
            $conn = null;
        }
    }
}
?>