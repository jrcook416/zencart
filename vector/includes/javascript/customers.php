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

<!-- (Optional) Latest compiled and minified JavaScript translation files -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/i18n/defaults-*.min.js"></script>
<script>
$(document).ready(function() {
  	$('select').selectpicker({
    	    liveSearch:true,
    	});  //end select initialize

$(document).on('click', '.add', function(){
	<?php unit_lookup();?>
    alert("button clicked.");
	var data = <?php echo json_encode($unit_array, JSON_UNESCAPED_SLASHES); ?>;
	alert("data variable loaded");
	var agency = $("#select-entry_company option:selected").text();
	alert(agency);
	var agFilter = data.filter((data) => data.agency_filter === agency);
	alert("agency data loaded");
	console.log(data);
		var html = '';
        for(var count = 0; count < agFilter.length; count++)
        {
        html += '<option value="'+agFilter[count].text+'">'+agFilter[count].text+' ('+agFilter[count].agency_filter+')</option>';
		}; //end for
		$('#entry_suburb').append(html);
		$('#entry_suburb').selectpicker('refresh');
  
}); //end on click

 $(document).on('click', '.remove', function(){
	$('#entry_suburb').empty();
	$('#entry_suburb').selectpicker('refresh');
 
 }); //end on click
}); //end document ready
</script>
