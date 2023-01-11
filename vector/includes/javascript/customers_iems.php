 <?php
 /**
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Jeremiah Cook 2022-03-30, modified for ZC v1.5.7d
 * //IEMS Custom Code - 2022-03-30//
 */   
	?>
 <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script>
$(document).ready(function() {
	$('select').selectpicker({
    	    liveSearch:true,
		}); //end select initialize 
	$(document).on('change', '#entry_county', function(){
		<?php agency_lookup();?>
		var data = <?php echo json_encode($agency_array, JSON_UNESCAPED_SLASHES); ?>;
		var county = $("#entry_county option:selected").val();
		alert("You have selected county code " + county);
		var agFilter = data.filter((data) => data.masterCountyID === county);
		var html = '<option value="">Please Make a Selection</option>';
        	for(var count = 0; count < agFilter.length; count++)
        	{
        		html += '<option value="'+agFilter[count].id+'">'+agFilter[count].text+'</option>';
		}; //end for
		$('#entry_agency').empty();
		$('#entry_agency').append(html);
		$('#entry_agency').prop('disabled',false);
		$('#entry_agency').selectpicker('refresh');
		alert("Please select an agency.");
	});//end document on change
	$(document).on('change', '#entry_agency', function(){
		<?php unit_lookup();?>
		var data = <?php echo json_encode($unit_array, JSON_UNESCAPED_SLASHES); ?>;
		var agency = $("#entry_agency option:selected").val();
		alert("You have selected agency code " + agency);
		var agFilter = data.filter((data) => data.masterAgencyID === agency);
		var html = '<option value="">Please Make a Selection</option>';
        	for(var count = 0; count < agFilter.length; count++)
        	{
        		html += '<option value="'+agFilter[count].id+'">'+agFilter[count].text+'</option>';
		}; //end for
		$('#entry_unit').empty();
		$('#entry_unit').append(html);
		$('#entry_unit').prop('disabled',false);
		$('#entry_unit').selectpicker('refresh');
		alert("Select an Ordering Unit.");
	});//end document on change
	$(document).on('click', '#updateAgency', function(){
		<?php agency_lookup();?>
		var agency = $("#entry_agency option:selected").val();
		var data = <?php echo json_encode($agency_array, JSON_UNESCAPED_SLASHES); ?>;
		var ag2Filter = data.filter((data) => data.id == agency);
			for(var count = 0; count < ag2Filter.length; count++)
			{
        	var text = ag2Filter[count].masterCountyID + " " + ag2Filter[count].masterAgency;
			}; //end for
		$('#entry_company').empty();
		$('#entry_company').val(text);
	});//end document on change
	$(document).on('click', '#resetAffiliations', function(){
		alert("Clearing the customer's affiliations from the database...");
		$('#entry_county').val('default');
		$('#entry_county').selectpicker('refresh');
		$('#entry_agency').val('default');
		$('#entry_agency').selectpicker('refresh');
		$('#entry_unit').val('default');
		$('#entry_unit').selectpicker('refresh');
		$('#entry_company').empty();
		alert("Select a County...");
	});//end document on change
});
</script>
