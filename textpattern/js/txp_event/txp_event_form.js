txp.list.form = {};
txp.edit.form = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.form.init = function() {

	console.log('init list form');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.form.init = function() {
	
	console.log('init edit form');
	
	var t = txp.edit.form;
	
	if (txp.mode == 'edit') {
	
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
}

