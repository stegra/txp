txp.plugins.codemirror = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.codemirror.init = function(mode) {
	
	console.log('init codemirror');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	var scrollpane = $("#scrollpane");
	var scrollpane_height = scrollpane.height();
	var throttleTimeout;
	var delayTimeout;
	
	var settings = {
		'showArrows': false,
		'maintainPosition': true,
		'enableKeyboardNavigation': false,
		'clickOnTrack': false,
		'verticalDragMinHeight': 50,
		'verticalGutter': 0,
		'autoReinitialise': false,
		'autoReinitialiseDelay': 2000,
		'scrollToBottom': true,
		'disableHorizontalScroll': true,
		'disableFocusHandler' : true
	};
	
	scrollpane.jScrollPane(settings);
	scrollpane.api = scrollpane.data('jsp');
	
	$(window).bind('resize',function() {
		scrollpane.api.reinitialise();
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
		mode: mode,
		lineNumbers: false,
		lineWrapping: false,
		indentUnit: 4,
		onCursorActivity: function() {
			editor.setLineClass(hlLine, null, null);
			hlLine = editor.setLineClass(editor.getCursor().line, null, "activeline");
		}
	});
	
	var textarea	= $("textarea#code");
	var editorpane  = $(".CodeMirror-scroll");
	var line_height = $(".CodeMirror-lines pre").height();
	var line_count  = 0;
	var firstline   = editor.getLineHandle(0);
	var hlLine      = editor.setLineClass(firstline,null, "activeline");
	
	editor.hash 	= txp.SimpleHash(textarea.val());
	editor.saved	= true;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.resize = function() {
		
		var editorpane_height = editorpane.height();
		var new_line_count = editor.lineCount();
		
		if (new_line_count != line_count) {
		
			var content_height = new_line_count * line_height;
	
			if (content_height > scrollpane_height) {
				editorpane.css('height',content_height + 'px');
			}
			
			editor.refresh();
			line_count = new_line_count;
		}
		
			scrollpane.api.reinitialise();
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.toggle_line_numbers = function(state) {
		
		if (state == 'off' || (!state && editor.getOption('lineNumbers')))
			editor.hide_line_numbers();
		else
			editor.show_line_numbers();
		
		$.get("/admin/index.php", { 
			event 	: txp.event, 
			step  	: 'line_numbers', 
			win	  	: txp.winid, 
			app_mode: 'async',
			state	: (editor.getOption('lineNumbers')) ? 'on' : 'off'
		});
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.hide_line_numbers = function() {
		
		editor.setOption('lineNumbers', false);
		$(".CodeMirror").css("background-position","-40px 0px");
		$(".CodeMirror-scroll").css("left","0px");
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.show_line_numbers = function() {
		
		editor.setOption('lineNumbers', true);
		$(".CodeMirror").css("background-position","0px 0px");
		$(".CodeMirror-scroll").css("left","0px");
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.not_saved = function() {
		
		editor.save();
		
		if (editor.hash != txp.SimpleHash(textarea.val())) {
		
			$('input#save').val('Save').removeClass('saved');
			editor.saved = false;
				
		} else {
			
			$('input#save').val('Saved').addClass('saved');
			editor.saved = true;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	editor.setOption('onChange', function() {
		
		clearTimeout(delayTimeout);
		
		delayTimeout = window.setTimeout(function() {
			
			editor.resize();
			editor.not_saved();
		
		}, 1000);
 
	});
	
	editor.resize();
	scrollpane.api.scrollToY(parseInt(txp.scroll));
	
	// -----------------------------------------------------
	
	$("a#toggle-line-numbers").click( function (event) {
		
		event.preventDefault();
		editor.toggle_line_numbers();
	});
	
	// -----------------------------------------------------
	
	$("input#save").bind('click', function (event) {
		
		editor.save();
		
		$("input#scroll").val(scrollpane.api.getContentPositionY());
	
	});
	
	// -----------------------------------------------------
	
	var keypress = '';
	var command_key_down = false;
	
	/* $(document).keydown(function(event) {
	
		switch (event.keyCode) {
		
			case 91  : keypress = 'COMMAND'; command_key_down = true; break; // safari
			case 224 : keypress = 'COMMAND'; command_key_down = true; break; // mozilla
			case 83  : keypress = 'S'; break;
		}
		
		if (keypress == 'S' && command_key_down) {
			
			event.preventDefault();
			
			$("input#save").trigger('click');
			
			return false;
		}
	});*/
	
	// -----------------------------------------------------
	
	return editor;
}