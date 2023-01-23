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
    	});  //end select initialize
	$(document).on('click', '#opc-bill-edit', function(){
		alert("PHP parsed.");
		<?php unit_lookup();?>
		var data = <?php echo json_encode($unit_array, JSON_UNESCAPED_SLASHES); ?>;
		console.log(data);
		var agency = "<?php echo $_SESSION['customer_agency'];?>";
		console.log(agency);
		alert("You have selected agency code " + agency);
		var agFilter = data.filter((data) => data.masterAgencyID === agency);
		var html = '<option value="">Please Make a Selection</option>';
        	for(var count = 0; count < agFilter.length; count++)
        	{
        		html += '<option value="'+agFilter[count].id+'">'+agFilter[count].masterUnitDescription+'</option>';
		}; //end for
		$('#unit-bill').empty();
		$('#unit-bill').append(html);
		$('#unit-bill').prop('readonly',false);
		$('#unit-bill').selectpicker('refresh');
		alert("Select an Ordering Unit.");
	});//end document on click
}); //end document ready

</script>
