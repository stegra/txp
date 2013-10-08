
$(document).ready(function() {
	
	var sites 	= [0];
	var current = 1;
	var last 	= 1;
	var hash	= false;
	
	// -----------------------------------------------------------------
	// populate site array 
	
	$("#sites select option").each(function() {
		
		var href = $(this).val();
		
		sites.push(href);
		
		last = sites.length - 1;
	});
	
	// -----------------------------------------------------------------
	// get incoming site if any
	
	if (document.location.hash) {
		
		hash = true;
		
		current = parseInt(document.location.hash.substr(1));
		
		$("#display").html('');
		
		display_site();
	}
	
	// -----------------------------------------------------------------
	
	$("#sites select").change(function() {
		
		current = parseInt($("#sites select option:selected").attr('id'));
		
		display_site();
	});
	
	// -----------------------------------------------------------------
	
	$("#sites a.prev").click(function() {
		
		current = (current == 1) ? last : current - 1;
		
		display_site();
		
		return false;
	});
	
	// -----------------------------------------------------------------
	
	$("#sites a.next").click(function() {
		
		current = (current == last) ? 1 : current + 1;
		
		display_site();
		
		return false;
	});
	
	// -----------------------------------------------------------------
	
	function display_site() {
		
		var href = sites[current];
		
		$("#sites select").val(href);
		
		if ($("iframe#frame"+current).length) {
			
			$("iframe").hide();
			$("iframe#frame"+current).show();
		
		} else {
			
			$("iframe").hide();
			
			href += 'index.html';
			href += (hash) ? '?' + Math.floor(Math.random() * 1000000) : '';
			
			iframe = $('<iframe/>').appendTo('#display');
			iframe.attr('id','frame'+current);
			iframe.attr('src',href);
		}
		
		document.location.hash = "#"+current; 
	}
	
});
