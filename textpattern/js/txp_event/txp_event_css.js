txp.list.css = {};
txp.edit.css = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.css.init = function() {

	console.log('init list css');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.css.init = function() {
	
	console.log('init edit css');
	
	var t = txp.edit.css;

	if ($('body.edit').length) {
		
		t.editor = txp.plugins.codemirror.init("text/css");
		
		t.editor.toggle_line_numbers(txp.linenum);
	}
}

