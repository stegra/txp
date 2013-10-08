txp.plugins.note = {
	pos : { x:700, y:90, z:10 }
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.init = function() {
	
	console.log('init note');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	var t = txp.plugins.note;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("div.note").draggable({ handle:'.header' });
	$("div.note .body").resizable();
	
	$("div.note").each( function() {
		
		var note   = $(this);
		var id     = note.attr('id').split('-').pop();
		var status = note.attr('data-status');
		var minmax = note.attr('data-minmax');
		var left   = parseInt(note.css('left')) || t.pos.x;
		var top    = parseInt(note.css('top')) || t.pos.y;
		var z 	   = parseInt(note.css('z-index')) || t.pos.z;
		
		if (status == 'min') {
			
			note.trigger('minimize');
		}
		
		if (z > t.pos.z) { 
			
			t.pos.z = z;
			t.pos.x = left + 30;
			t.pos.y = top + 30;
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// editing
	
	$("div.note .body .content .read").dblclick( function () {
		
		var content = $(this).parent();
		
		content.addClass('edit');
		content.find('textarea').focus();
	});
	
	$("div.note .body .content textarea").bind("blur",function () {
		
		var content = $(this).parent();
		var text    = $(this).val();
		var note    = content.parents('.note');
		
		t.save_text(note,text);		
	});
	
	$("div.note .header").click( function () {
		
		var note = $(this).parent();
		
		if (note.find('.content').hasClass('edit')) {
		
			note.find('.content textarea').trigger('blur');
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// dragging
	
	$("div.note").bind("dragstop", function(event, ui) {
		
		t.save_status($(this));
	});

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// resizeing
	
	$("div.note .body").bind("resize", function(event, ui) {
				
		t.resize_title($(this).parent());
	});
	
	$("div.note .body").bind("resizestop", function(event, ui) {
		
		t.save_status($(this).parent());
	});

	$(".ui-resizable-s, .ui-resizable-e, .ui-resizable-se").hover(
				
		function () {
			$(this).addClass("hover");
		},
		function () {
			$(this).removeClass("hover");
		}
	);
		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// min/max/close
	
	$("div.note .buttons a.min").click( function() {
			
		$(this).parents('.note').trigger('minimize');
		return false;
	});
	
	$("div.note .buttons a.max").click( function() {
	
		$(this).parents('.note').trigger('maximize');
		return false;
	});
			
	$("div.note .buttons a.close").click( function() {
			
		$(this).parents('.note').trigger('close');
		return false;
	});
	
	// minimize
	
	$('div.note').bind('minimize', function() {
				
		var note = $(this);
		
		note.attr('data-minmax','min');
		note.removeClass('max');
		note.addClass('min');
		
		t.save_status(note);
	});
	
	// maximize
	
	$('div.note').bind('maximize', function() {
	
		var note = $(this);
		
		note.attr('data-minmax','max');
		note.removeClass('min');
		note.addClass('max');
		
		t.up_zindex(note);
		t.save_status(note);
	});
	
	// close
	
	$('div.note').bind('close', function() {
				
		$(this).removeClass('open');
		$(this).attr('data-status','closed');
		
		t.save_status($(this));
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// zindex
	
	$("div.note").click( function() {
		
		t.up_zindex($(this));
		t.save_status($(this));
		
	});
	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.up_zindex = function(note) {

	note.css('z-index', ++txp.plugins.note.pos.z ); 
}
		
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.resize_title = function(note) {

	var title         = note.find('.title');
	var body_width    = note.find('.body').outerWidth();
	var title_width   = title.outerWidth();
	var buttons_width = note.find(".header .buttons").outerWidth();
	
	title_width = body_width - buttons_width - 1;
	
	title.css('width',title_width+'px');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.open = function(id) {

	var t = txp.plugins.note;
	
	var note = $("#note-"+id);
	
	if (note.attr('data-status') == 'closed') {
		
		var top  = note.css('top');
		var left = note.css('left');
		
		if (top == 'auto' || top == '0px') {
			top  = t.pos.y + 'px';
			left = t.pos.x + 'px' ;
		}
		
		note.css({ top:top, left:left });
		note.addClass('open');
		note.attr('data-status','open');
		
		t.resize_title(note);
		t.up_zindex(note);
		t.save_status(note);
	
		t.pos.x = t.pos.x + 30;
		t.pos.y = t.pos.y + 30;
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.save_text = function(note,text) {

	var id = note.attr('id').split('-').pop().split('_').shift();
	
	$.post("/admin/index.php", { 
			event   : 'list', 
			step    : 'save_note_text', 
			win	  	: txp.winid, 
			app_mode: 'async',
			noteid	: id, 
			type	: note.attr('data-type'), 
			text 	: text 
		},
		function(data) {  
			txp.plugins.note.update_text(note,data); 
		}
	);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.update_text = function(note,data) {

	var data = jQuery.parseJSON(data);
	
	note.find('.content .read').html(data.html);
	note.find('.content textarea').val(data.text);
	note.find('.content').removeClass('edit');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.plugins.note.save_status = function(note) {

	var id   = note.attr('id').split('-').pop().split('_').shift();
	var body = note.find('.body');
	
	$.get("/admin/index.php", { 
		
		event 	: 'list', 
		step  	: 'save_note_status', 
		win	  	: txp.winid, 
		app_mode: 'async',
		noteid	: id, 
		type    : note.attr('data-type'),
		status  : note.attr('data-status'),
		minmax	: note.attr('data-minmax'),
		width	: parseInt(body.css('width')),
		height	: parseInt(body.css('height')),
		top		: parseInt(note.css('top')),
		left	: parseInt(note.css('left')),
		z		: parseInt(note.css('z-index'))
		
	},function(data) { 
		// console.log(data); 
	});
}

// -------------------------------------------------------------
// OLD FROM global.js
/*
var x = 700;
var y = 90;
var zindex = 0;

txp.note = function(action,id) 
{
	if (action == 'open') {
		
		$("#note-"+id).show().css({ top:y+'px', left:x+'px' });
		$("#note-"+id).css('zIndex', zindex++ ); 
		
		x = x - 30;
		y = y + 20;
		
	} else if (action == 'close') {
		
		$("#note-"+id).hide();
		
	} else if (action == 'min') {
	
		$("#note-"+id+"-min").show();
		$("#note-"+id+"-max").hide();
		
	} else if (action == 'max') {
	
		$("#note-"+id+"-min").hide();
		$("#note-"+id+"-max").show();
	}
	
	$.post("index.php", { 
		event: 	  "list", 
		step: 	  "note_status",
		app_mode: "async",
		id:		  id,
		status:	  action
	});
}
*/