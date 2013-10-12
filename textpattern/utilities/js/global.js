// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

var console_pane_api = null;

$(document).ready( function() {
	
	var settings = {
		'showArrows': false,
		'maintainPosition': false,
		'enableKeyboardNavigation': false,
		'clickOnTrack': false,
		'verticalDragMinHeight': 40,
		'autoReinitialise': false,
		'autoReinitialiseDelay': 2000,
		'scrollToBottom': false,
		'disableHorizontalScroll': true,
		'disableFocusHandler' : true
	};
	
	var left_width = $('table.main td.left').width();
	
	control_pane = $('#control').jScrollPane(settings);
	console_pane = $('#console').jScrollPane(settings);
	console_pane_api = console_pane.data('jsp');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	function resize_console_pane() {
		
		var	window_width = $(window).width();
		var console_width = window_width - (left_width + 60);
		
		if (console_width < 575) console_width = 575;
		
		$(".console").css('width',console_width + "px");
		// $(".console table#list").css('width',(console_width - 22) + "px");
		$(".console .scroll").css('width',console_width + "px");
		$(".console .scroll .pad div").css('width',(console_width - 22) + "px");
		console.log(window_width,left_width,console_width);
		pane.jScrollPane(settings);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	// resize_console_pane();
	
	$(window).resize(function() {
	
  		// resize_console_pane();
	});
	
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('li#update_database span.go a').click( function() {
		
		window.open(
			'../index.php?update=SELF',
			'update_database',
			"width=400,height=300,scrollbars=yes");
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('span.go a').click( function() {
		
		if ($(this).parents('li#update_database').length == 0) {
		
			$(".console .scroll .pad pre").html('');
		}
		
	});
	
});

