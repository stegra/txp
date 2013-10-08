txp.list.page = {};
txp.edit.page = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.page.init = function() {

	console.log('init list page');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.page.init = function() {
	
	console.log('init edit page');
	
	var t = txp.edit.page;
	
	if ($('body.edit').length) {
		
		t.editor = txp.plugins.codemirror.init("application/xml");
		
		t.editor.toggle_line_numbers(txp.linenum);
	}
	
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
	
	$('div#edit div.column div.list').jScrollPane(settings);
	
	$("#tag-builder select").change( function() {
		
		var tag  = $(this).val();
		var href = "?event=tag&tag_name=" + tag;
		
		txp.popWin(href);
	});
	
}

