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
		var agFilter = data.filter((data) => data.masterCountyID === county);
		var html = '';
        	for(var count = 0; count < agFilter.length; count++)
        	{
        		html += '<option value="'+agFilter[count].text+'">'+agFilter[count].text+'</option>';
		}; //end for
		$('#entry_agency').selectpicker('empty')
		$('#entry_agency').append(html);
		$('#entry_agency').selectpicker('refresh');
	});//end document on change
});
</script>
