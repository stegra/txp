$(document).ready(function() {
	
	$('input').change(function(){
		
		var id    = $(this).attr('id');
		var value = $(this).val();
		
		if (value.match(/^\d+$/)) {
			
			$.post( "/admin/utilities/index.php?go=user_agent_log", 
			 	{ id:id, screen:value },
				function( data ) {
    				// console.log( "Data Loaded: " + data );
  				}
  			);
		}	
	});
});