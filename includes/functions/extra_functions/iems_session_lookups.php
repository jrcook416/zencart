<?php
/*  Zen Cart&reg; v1.5.8 for Indianapolis EMS
 *  Custom Function Definition file - iems_session_lookups.php
 *  ********************
 *  THIS FILE ADDS THE FOLLOWING TO THE SESSION VARIABLE ON THE CUSTOMER SIDE OF THE SITE -
 *  DO NOT MOVE THIS FILE OR COPY THIS FILE INTO ADMINISTRATION PAGES!
 *
 *  These functions are called in the customer class on the catalog side of the site. Do not reuse them
 *  unless you're absolutely certain that you want $_SESSION['IEMS'] values rewritten in the catalog interface.
 *
 *  These variables are designed for future use and will *not* be implemented in v1.5.8 as of 2023-01-26.
 */
    function county_code_lookup($countyCode){
        global $db;
        $county_values = $db->Execute("select countyCode, countyName from iems_counties where countyID = '" . $countyCode . "' LIMIT 1");
            while (!$county_values->EOF){
                $_SESSION['IEMS']['countyCode'] = $county_values->fields['countyCode'];
                $_SESSION['IEMS']['countyName'] = $county_values->fields['countyName'];
                $county_values->MoveNext();
            }; //end while
    } //end county_code_lookup

    function agency_code_lookup($agencyCode){
        global $db;
        $agency_values = $db->Execute("select masterCountyID, masterAgency, masterAgencyDescription from iems_agencies where masterAgencyID = '" . $agencyCode . "' LIMIT 1");
            while (!$agency_values->EOF){
                $_SESSION['IEMS']['agencyCountyID'] = $agency_values->fields['masterCountyID'];
                $_SESSION['IEMS']['agencyCode'] = $agency_values->fields['masterAgency'];
                $_SESSION['IEMS']['agencyName'] = $agency_values->fields['masterAgencyDescription'];
                $agency_values->MoveNext();
            };
    } //end agency_code_lookup

    function unit_code_lookup($unitCode){
        global $db;
        $unit_values = $db->Execute("select masterCountyID, masterUnitID, masterAgency, masterUnitDescription from iems_units where masterUnitID = '" . $unitCode . "' LIMIT 1");
        while (!$unit_values->EOF){
            $_SESSION['IEMS']['unitCounty'] = $unit_values->fields['masterCountyID'];
            $_SESSION['IEMS']['unitAgency'] = $unit_values->fields['masterAgency'];
            $_SESSION['IEMS']['unitCode'] = $unit_values->fields['masterUnitID'];
            $_SESSION['IEMS']['unitName'] = $unit_values->fields['masterUnitDescription'];
            $unit_values->MoveNext();
        };
    } //end unit_code_lookup
