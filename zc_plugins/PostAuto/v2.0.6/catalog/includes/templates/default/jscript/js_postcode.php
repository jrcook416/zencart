<?="<script>
$.when(
    $.getScript( \"" . $this->zcPluginCatalogPath . "includes/templates/default/jscript/jquery.powertip.min.js\" ),
    $.Deferred(function( deferred ){
        $( deferred.resolve );
    })
).done(function(){

    $('#postcode').on('keyup change', function() {
        var cleancode = $('#postcode').val().replace(/[-|―|ー]/g,'');
        if (cleancode.length >= 4) {
            var donnees = 'country=' + $('#country').val() + '&postcode=' + cleancode;
            zcJS.ajax({
                url: \"ajax.php?act=AjaxPostcodeQuery&method=postcodequery\",
                data: donnees
            }).done(function( response ) {
                let optionsv = '';
                let optionss = '';
                let preced_cit = 'none';
                let preced_str = 'none';
                let countcities = 0;
                let countstreet = 0;
                let reg = /[1-9]/;

                if (response.length === 0) {
                    return;
                }
                if (reg.test(response[0].zone_id)) {
                    $('#stateZone').val(response[0].zone_id);
                } else {
                    $('#state').val(response[0].zone_id);
                }
                $('#city').val(response[0].city);
                if (response[0].street_name.length > 0) {
                    $('#street-address').val(response[0].street_name);
                }
                if (response.length > 1) {
                    for (let i = 0; i < response.length; ++i) {
                        if (response[i].city != preced_cit) {
                            if (response[i].city.length > 0) {
                                cityfield = document.getElementById('city');
                                optionsv += '<p onclick=\"cityfield.value = \'' + response[i].city.replace(/'/g, '\\\\\'') + '\';chevent = new Event(\'change\', {bubbles: true});cityfield.dispatchEvent(chevent);\">' + response[i].city + '</p>';
                            }
                            preced_cit = response[i].city;
                            countcities++;
                        }
                        if (response[i].street_name != preced_str) {
                            if (response[i].street_name.length > 0) {
                                optionss += '<p onclick=\"document.getElementById(\'street-address\').value = \'' + response[i].street_name.replace(/'/g, '\\\\\'') + '\'\">' + response[i].street_name + '</p>';
                            }
                            preced_str = response[i].street_name;
                            countstreet++;
                        }
                    }
                    optionsv = (countcities > 1) ? optionsv : [] ;
                    optionss = (countstreet > 0) ? optionss : [] ;
                }
                $(function() {
                    var mouseOndiv = $('#city');
                    var tipContent = $(optionsv);
                    mouseOndiv.data('powertipjq', tipContent);
                    mouseOndiv.powerTip({
                        placement: 'e',
                        mouseOnToPopup: true
                    });
                });
                $(function() {
                    var mouseOndiv = $('#street-address');
                    var tipContent = $(optionss);
                    mouseOndiv.data('powertipjq', tipContent);
                    mouseOndiv.powerTip({
                        placement: 'e',
                        mouseOnToPopup: true
                    });
                });
            });
        }
    });

    $('#city').on('keyup change', function() {
        if ($('#city').val().length > 1) {
            var cleancode = $('#postcode').val().replace(/[-|―|ー]/g,'');
            var citydata = 'country=' + $('#country').val() + '&postcode=' + cleancode + '&city=' + $('#city').val();
            zcJS.ajax({
                url: \"ajax.php?act=AjaxPostcodeQuery&method=postcodequery\",
                data: citydata
            }).done(function( resp ) {
                let optionss = '';
                let preced_str = 'none';
                let countstreet = 0;
                let reg = /[1-9]/;

                if (resp.length === 0) {
                    return;
                }
                if (reg.test(resp[0].zone_id)) {
                    $('#stateZone').val(resp[0].zone_id);
                } else {
                    $('#state').val(resp[0].zone_id);
                }
                $('#street-address').val(resp[0].street_name);
                if (resp.length > 1) {
                    for (let i = 0; i < resp.length; ++i) {
                        if (resp[i].street_name != preced_str) {
                            if (resp[i].street_name.length > 0 && $('#city').val() === resp[i].city) {
                                optionss += '<p onclick=\"document.getElementById(\'street-address\').value = \'' + resp[i].street_name.replace(/'/g, '\\\\\'') + '\'\">' + resp[i].street_name + '</p>';
                            }
                            preced_str = resp[i].street_name;
                            countstreet++;
                        }
                    }
                    optionss = (countstreet > 0) ? optionss : [] ;
                }
                $(function() {
                    var mouseOndiv = $('#street-address');
                    var tipContent = $(optionss);
                    mouseOndiv.data('powertipjq', tipContent);
                    mouseOndiv.powerTip({
                        placement: 'e',
                        mouseOnToPopup: true
                    });
                });
            });
        }
    });

});
</script>
";
