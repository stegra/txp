$(document).ready(function() {
	
	var toolbar_status = $.cookie('txp_sitemode_toolbar') || 'off';
	
	if (toolbar_status == 'on') {
		
		$('iframe#toolbar').css('top',"0px");
		
		$.cookie('txp_sitemode_toolbar','off', { expires: 7, path: '/' });
	}
	
	$(document).mouseleave(function(e) {
		
		var mouse = (e.pageY - $(this).scrollTop());
		
		if (toolbar_status == 'off' && mouse < 0) {
		
			toolbar_status = 'on';
			$('iframe#toolbar').animate({top:"0px"},500);
		}
	});
	
	$("body").mousemove(function(e) {
	
		var mouse = (e.pageY - $(this).scrollTop());
    	
    	if (toolbar_status == 'on' && mouse >= 25) {
    	
    		toolbar_status = 'off';
    		$('iframe#toolbar').animate({top:"-25px"},500);
    	}		
    });
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	
});