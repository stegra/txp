$(document).ready( function() {
	
	var grid = $('table#list td.grid .pad');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$(window).resize(function() {
  		
  		clearTimeout(resized);
  		
  		resized = setTimeout(function() {
  			mini_window_width();
  		},400);
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function mini_window_width() {
		
		var width = win.width();
		
		if (width <= 420) {
			
			$('body').addClass('window-narrow');
		
		} else {
			
			$('body').removeClass('window-narrow');
		}
		
		if (width <= 550) {
			
			$('body').addClass('window-narrow-2');
		
		} else {
			
			$('body').removeClass('window-narrow-2');
		}
	}
});