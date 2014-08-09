txp.list = {
	
	debug				: 1,
	mode				: 'view',	// 'view' when no items are being edited
									// 'edit' when at least one item is being edited
	sort				: {},
	
	columns				: [],		// list of column names
	rows				: [],		// list of table row objects
	row_count			: 0,		// total number of rows including empty rows
	checked 			: [],		// list of selected item ids
	lastchecked			: 0,		// id of last selected item
	
	clicks				: 0,
	dclick				: 0,
	dclickspeed			: 500,
	clickoff			: {},
	
	keypress 			: '',
	key					: [0],
	modkey				: [0,0],
	shift_key_down 		: false,
	command_key_down 	: false,
	control_key_down    : false,
	arrow_key_down 		: false, 
	
	editfocus 		    : false,
	resized				: null,
	select_all_chbox 	: null,
	scroll_to_after	    : ['open','close','refresh','multi_edit'],
	contextmenu			: { main:{}, trash:{} },
	actions				: [],
	create_new			: 0
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// reset after loading more list items 

txp.list.reset = function() {
	
	console.log('reset list');
	
	var t = txp.list;
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	
	t.rows.length = 0;
	t.rows = t.get_table_rows();
	var empty_rows = $("table#list tr.empty");
	t.row_count = t.rows.length + empty_rows.length;
	
	// - - - - - - - - - - - - - - - - - - - - - - -
	
	t.addEventRowHover();
	t.addEventRowClick();
	t.addEventLoadMore();
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.init = function() {
	
	console.log('init list');
	
	txp.plugins.pulldown.init();
	
	if (txp.list.grid) {
		
		txp.list.grid.init();
	}
	
	if (txp.list[txp.event]) {
	
		txp.list[txp.event].init();
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	var t = txp.list;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	t.clickoff.elem = $('#clickoff');
	t.clickoff.html = t.clickoff.elem.html();
	t.clickoff.elem.html('');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("input#search-click").bind("click",function() { 
	
		document.search.search_click.value = 1;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	for (i=0;i<=t.scroll_to_after.length;i++) {
		if (txp.step == t.scroll_to_after[i]) {
			window.scrollTo(0,parseInt(txp.scroll));
		};
	}
	
	// this does not always work!
	/* if ($.inArray(txp.step,t.scroll_to_after)) {
		window.scrollTo(0,parseInt(txp.scroll));
	} */
	
	// $(".notepad").draggable();
	
	$(".chbox input").hide();
	$(".chbox input").hide();
	
	if ($('tr.headers').hasClass('hidden')) {
		txp.headers = 'hide';
	}
	
	t.resize_grid_view();
 // t.resize_list_view();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (document.longform.checked.value != '') {
		 
		// txp.checked = document.longform.checked.value.split(',');
		// checked items come in through txp object
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// table header array
	
	t.columns.length = 0;
	
	t.columns = t.get_table_column_names();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// table row array
	
	t.rows.length = 0;
	
	t.rows = t.get_table_rows();
	
	var empty_rows = $("table#list tr.empty");
	
	t.row_count = t.rows.length + empty_rows.length;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("table#list tr.main-article td.col-0 div.pad")
		.first().prepend('<span class="border"></span>');
		
	$("table#list tr.row-0 td.col-0 div.pad")
		.first().prepend('<span class="border"></span>');
		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// image thumbnails
	
	var images = document.querySelectorAll('table#list div.image');
	
	for (var i = 0; i < images.length; i++) {
			
		image = images[i];
		
		image.addEventListener('dragstart', t.handleDragStart, false);
		image.addEventListener('dragend', t.handleDragEnd, false);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// grid view items
	
	var panels = document.querySelectorAll('table#list div.panel');
	
	for (var i = 0; i < panels.length; i++) {
			
		panel = panels[i];
		
		panel.addEventListener('dragstart', t.handleDragStart, false);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (txp.method == 'move_up') {
		
		$("select#action option#position-move-up").attr('selected','yes');
	}
	
	if (txp.method == 'move_down') {
		
		$("select#action option#position-move-down").attr('selected','yes');
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('form#longform input#go').bind("click",function() {
		
		if ($("select#action").val() == 'edit') {
			document.longform.editcol.value = '';
		}
	});
	
	$('form#longform').submit(function() {
		
		var action = $("select#action");
		
		if (action.val() == 'new') {
			
			if (!t.create_new) {
				t.show_create_new();
			} else {
				t.submit_create_new();
			}
		
		} else if (action.val() == 'group') {
			
			if (!t.create_new) {
				t.show_create_new('group');
			} else {
				t.submit_create_new();
			}
			
		} else if (action.val() == 'window') {
  			
  			t.open_checked_in_window();
  			action.val('none');
  		
  		} else if (action.val() != 'none') {
  			
  			t.showLoadingAnim();
  			
  			document.longform.submit();
  		}
  		
  		return false;
	});
	
	$("select#action option").each(function() {
		
		if ($(this).hasClass('show') == false) {
			// $(this).remove();
		}
	});
	
	t.sort.col = $('form#longform').attr('data-sort');
	t.sort.dir = $('form#longform').attr('data-sortdir');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// check rows if any in edit mode
	
	if ($("table#list tr.edit").length) {
	
		t.mode = 'edit';
		t.add_action('edit');
		
		// put the focus on the first title input field
	
		$("input.title").each(function(i) {
			if (i == 0) {
				$(this).trigger('focus');
				t.editfocus = true;
			}
		});
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// thumbnail image click:
	// - open image edit window if image item
	// - open note if note item
	
	$("body.list td.thumb a").bind("click",function(event) { 
		
		event.preventDefault();
		event.stopPropagation();
		
		// note
		
		if ($(this).hasClass('note')) {
			txp.plugins.note.open($(this).attr('href'));
			return false;
		}
		
		// image
		
		var href = [this.href];
		var name = 'window-'+$(this).parent('div').attr('id');
		
		href.push("win=new");
		href.push("mini=1");
		
		window.open(href.join('&'),name,'width=780,height=610,scrollbars,resizable');
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// go to edit page when article title link is clicked
	
	$("tr.view td.title a.title").bind("click",function(event){
		
		var id   = $(this).attr('rel');
		var href = $(this).attr('href');
		
		if (t.command_key_down && t.keypress == 'E') {
			
			// in a new window when command key and E is pressed down
			
			t.open_checked_in_window('edit',id);
			
		} else {
			
			// otherwise in the same window
			
			document.location.href = href + "&win=" + txp.winid ;
		}
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open trash in a mini window when trash icon is clicked
	
	$("tr.footer div.trash a").bind("click",function(event) { 
		
		if (t.control_key_down) {
		
			$(this).trigger('contextmenu');
			return false;
		}
		
		t.open_trash_window(event,this.href);
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// load more 
	
	t.addEventLoadMore();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// row hover
	
	t.addEventRowHover();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// column header click event
	
	$("table#list th").bind("click",function(event) {
	
		t.handleColumnHeaderClick(event,this);
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// row click event
	
	t.addEventRowClick();
		
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// keyboard events
	
	$(document).bind("keydown",function(event) { 
		
		t.handleKeyDown(event); 
	});
	
	$(document).bind("keyup",function(event) { 
		
		t.handleKeyUp(event); 
	});
	
	$(document).bind("keypress",function(event) { 
		
		t.handleKeyPress(event); 
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// context menus
	
	t.contextmenu.main.init();
	
	t.contextmenu.trash.init();
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// column change selection handler
	
	$('select#column').bind("change",function(event) {
	
		t.handlerColumnChange(event,this);
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// disable checkbox in edit mode
	
	$("tr.edit .chbox input.article").change(function(event) { 
	
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// edit mode when form input fields are focused
	
	$('input.text,select,textarea').bind("focus",function(event){
		
		t.editfocus = true;
	});
	
	$('input.text,select,textarea').bind("focusout",function(event){
		
		t.editfocus = false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// select all checkbox
	
	t.select_all_chbox = $("input#select-all");
	
	t.select_all_chbox.bind('click',function() {
	
		if ($(this).attr('checked')) {
    		txp.selectall();
    	} else { 
    		txp.deselectall();
    	}
  	});
  	
  	t.select_all_chbox.bind('uncheck',function() {
  		
  		$(this).attr('checked', false);
  	});
  	
  	t.select_all_chbox.bind('toggle',function() {
  		
  		if (!$(this).attr('checked')) {
    		txp.selectall();
    	} else { 
    		txp.deselectall();
    	}
  	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// multi-select init
	
	$('td.multi-select.edit').each(function() {
	
		var max_word_length = 0;
		var min_word_length = 99;
		var blankfirst = false;
		var first = null;
		var selected = {
			values   : [],
			titles   : []
		};
		
		$('select option',this).each(function(i) {
		
			var option = $(this);
			option.title = option.html();
			option.value = option.attr('value');
			
			if (option.hasClass('selected')) {
				option.html(option.title + ' &#10003;');
				option.title = option.title.replace(/&nbsp;/g,' ');
				option.pos   = parseInt(option.data('pos'))-1;
				selected.values[option.pos] = option.value;
				selected.titles[option.pos] = $.trim(option.title);
			}
			
			option.title = option.title.replace(/&nbsp;/g,' ');
			
			if (i == 0) {
				
				first = option;
				
				if (option.title.length == 0) blankfirst = true;
			} 
				
			if (max_word_length < option.title.length) {
				max_word_length = option.title.length;
			}
			
			if (min_word_length > option.title.length) {
				min_word_length = option.title.length;
			}
		});
		
		// console.log(blankfirst,min_word_length,max_word_length,selected);
		
		var line = '---';
		
		for (var i=1; i<=max_word_length; i++) line += '-';
		
		var value = selected.values.join(',');
		var title = selected.titles.join(',');
			
		if (blankfirst) {
			
			first.attr('value',value);
			first.attr('title',title);
			first.html(selected.titles.join(', '));
			first.after('<option class="line">'+line+'</option>');
		
		} else {
			
			var html  = '<option value="'+value+'" title="'+title+'">';
			html     += selected.titles.join(', ')
			html 	 += '</option>';
			html 	 += '<option class="line">'+line+'</option>';
			
			first.before(html);
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// multi-select change event handler
	
	$('td.categories select').bind("change",function() {
		
		var sel  = $(this);
		var main = {};
		var val  = sel.val();
		var opt1 = sel.find('option').first();
		var remove = false;
		var check  = '';
		
		main.value = opt1.val();
		main.title = opt1.attr('title');
		
		main.value = (main.value) ? main.value.split(',') : [];
		main.title = (main.title) ? main.title.split(',') : [];
		
		main.value.unset('NONE'); 
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (main.value.has(val)) {
			
			main.value.unset(val); 
			
		} else if (val.length) {
			
			main.value.push(val);
			check = ' &#10003;';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		// option title check marks
		
		sel.children('option').each(function(i) {
			
			var opt = $(this);
			
			if (i > 0 && opt.val() == val) {
				
				var level = parseInt(opt.attr('data-level'))-2;
				var title = opt.attr('title');
				var indent = '';
				
				for (var i = 0; i < level; i++) 
					indent += '&#160;&#160;&#160;';
				
				if (check)
					main.title.push(title);
				else
					main.title.unset(title);	
				
				opt.html(indent + title + check);
			}
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		
		if (main.value.length)
			opt1.val(main.value.join(','));
		else
			opt1.val('NONE');
		
		opt1.attr('title',main.title.join(','));
		opt1.html(main.title.join(', '));
		opt1.attr('selected',true);
		
		// - - - - - - - - - - - - - - - - - - - - - - - -
		// resize the line under the first option
		
		var length = main.title.join(', ').length + main.title.length;
		
		if (length < 20) length = 20;
		
		for (var i=1, line=''; i<=length; i++) line += '-';
		
		sel.children('option.line').first().html(line);
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// window resize
	
	$(window).resize(function() {
  		
  		clearTimeout(t.resized);
  		
  		t.resized = setTimeout(function() {
  			t.resize_grid_view();
  			t.resize_list_view();
  		},400);
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// resize title textarea for new article form 
	
	$('#create-new-article textarea').bind('input propertychange', function() {
		
		var height = parseInt($(this).css('min-height'));
		var rows = $(this).val().split("\n").length;
		rows = (rows > 20) ? 20 : rows;
		
		if (rows >= 2) {
			$(this).css('height',(rows*(height-1))+'px');
		} else {
			$(this).css('height',height+'px');
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// textarea focus
	
	$('tr.edit div.textarea textarea').focus(function(){
		
		$(this).parent().addClass('focus');
	});
	
	$('tr.edit div.textarea textarea').blur(function(){
		
		$(this).parent().removeClass('focus');
	});
	
}

// =============================================================================
// add events 

txp.list.addEventRowHover = function() {
	
	$("tr.view,div.data").off("mouseenter mouseleave");

	$("tr.view,div.data").hover(
		function() {
			$(this).addClass('hover');
		}, 
		function() {
			$(this).removeClass('hover');
		}
	);
}

txp.list.addEventRowClick = function() {
	
	$("tr.view td.col").unbind("click",txp.list.handleRowClick);
	$("tr.view td.col").bind("click",txp.list.handleRowClick);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.addEventLoadMore = function() {
	
	$("body.list td span.more a.load-more").bind("click",function(event) { 
		
		$.get("index.php", { 
			event    : txp.event, 
			win      : txp.winid,
			more	 : 50,
			app_mode : 'async',
			refresh_content: 1 },
			function(data){
				$('table#list td span.more').remove();
				$(data).insertBefore('table#list tr#hr3');
				txp.list.reset();
			});
		
		return false;
	});
}

// =============================================================================
// event handlers 

txp.list.handleKeyDown = function(event) {
	
	var t = txp.list;
	
	t.keypress = '';
	
	if (txp.keyboard == 'mac') {
	
		switch (event.keyCode) {
			
			case 17  : t.keypress = 'CONTROL';	t.control_key_down = true; break;
			case 91  : t.keypress = 'COMMAND';	t.command_key_down = true; break; // safari
			case 224 : t.keypress = 'COMMAND';	t.command_key_down = true; break; // mozilla
			case 187 : t.keypress = '+'; break;
			case 189 : t.keypress = '-'; break;
		}
	
	} else {
		
		switch (event.keyCode) {
		
			case 17  : t.keypress = 'COMMAND';	t.command_key_down = true; break;
			case 61  : t.keypress = '+'; break;
			case 173 : t.keypress = '-'; break;
		}
	}
	
	switch (event.keyCode) {
		case 16  : t.keypress = 'SHIFT';  	t.shift_key_down = true;   break;
		case 13  : t.keypress = 'ENTER';  	break;
		case 8   : t.keypress = 'DELETE'; 	break;
		case 27  : t.keypress = 'ESCAPE'; 	break;
		case 32	 : t.keypress = 'SPACE';	break;
		case 37  : t.keypress = 'LEFT';		t.arrow_key_down = 'LEFT';  break;
		case 38  : t.keypress = 'UP'; 		t.arrow_key_down = 'UP';    break;
		case 39  : t.keypress = 'RIGHT';	t.arrow_key_down = 'RIGHT'; break;
		case 40  : t.keypress = 'DOWN';		t.arrow_key_down = 'DOWN';  break;
		case 48  : t.keypress = '0'; break;
		case 65  : t.keypress = 'A'; break;
		case 67  : t.keypress = 'C'; break;
		case 68  : t.keypress = 'D'; break;
		case 69  : t.keypress = 'E'; break;
		case 71  : t.keypress = 'G'; break
		case 72  : t.keypress = 'H'; break;
		case 73  : t.keypress = 'I'; break;
		case 76  : t.keypress = 'L'; break;
		case 83  : t.keypress = 'S'; break;
		case 84  : t.keypress = 'T'; break;
		case 85  : t.keypress = 'U'; break;
		case 86  : t.keypress = 'V'; break;
		case 87  : t.keypress = 'W'; break;
		case 88  : t.keypress = 'X'; break;
	}
	
	if (t.debug) console.log('Key Down:',event.keyCode,t.keypress);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	if (t.command_key_down) {
	
		if (!t.editfocus && !t.getSelText()) {
		
			event.preventDefault();
			
			switch (t.keypress) {
			 // case 'DELETE' 	: t.with_selected_go(event,'trash'); 	break;
				case 'A' 		: t.select_all_chbox.trigger('toggle'); break;
				case 'X' 		: t.with_selected_go(event,'cut'); 	break;
				case 'C' 		: t.with_selected_go(event,'copy'); 	break;
				case 'V' 		: t.with_selected_go(event,'paste'); 	break;
				case 'W' 		: t.with_selected_go(event,'view_site'); break;
				case 'L' 		: t.with_selected_go(event,'alias'); 	break;
				case 'D' 		: t.with_selected_go(event,'duplicate'); break;
				case 'G' 		: t.with_selected_go(event,'group');  break;
				case 'U' 		: t.with_selected_go(event,'ungroup');  break;
				case 'H' 		: t.with_selected_go(event,'hoist');  break;
				case 'T' 		: t.with_selected_go(event,'touch');  break;
				case '-' 		: t.with_selected_go(event,'close'); 	break;
				case '+' 		: t.with_selected_go(event,'open'); 	break;
				case 'I' 		: t.with_selected_go(event,'folder_image');
			}
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	// used for double clicks
	
	t.key.shift(); 
	t.key.push(t.keypress); 
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
	
	if (t.shift_key_down) {
	
		if (!t.editfocus) {
			event.preventDefault();
		}
	}	
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'SHIFT' || t.keypress == 'COMMAND') {
		
		t.modkey.shift(); 
		t.modkey.push(t.keypress);
		
		return false;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'E') {
		
		if (t.command_key_down && !t.editfocus && !t.getSelText()) {
			
			event.preventDefault();
			
			if (txp.checked.length) {
				t.with_selected_go(event,'edit');
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'S') {
		
		if (t.command_key_down) {
		
			event.preventDefault();
			t.with_selected_go(event,'save');
		
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'SPACE') {
		
		if (!t.editfocus) {
			
			t.with_selected_go(event,'move');
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'ENTER') {
		
		console.log('command_key_down',t.command_key_down);
		
		if (t.command_key_down) {
			
			if (txp.list.create_new) {
				
				event.preventDefault();
				t.submit_create_new();
			
			} else { 
			 
				if (t.mode != 'edit') t.with_selected_go(event,'new');
				if (t.mode == 'edit') t.with_selected_go(event,'save');
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'DELETE') {
		
		if (!t.editfocus && t.command_key_down) {
			
			event.preventDefault();
			
			if (txp.col) { 
				
				t.remove_column(); 
			
			} else {
				
				t.with_selected_go(event,'trash');
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'ESCAPE') {
		
		console.log('ESCAPE');
		console.log(t.actions);
		
		event.preventDefault();
		
		switch (t.actions.shift()) {
			case 'check'   : t.unselect_all_rows(); break;
			case 'column'  : t.unselect_column(); break;
			case 'edit'    : t.with_selected_go(event,'edit_cancel'); break;
			case 'context' : hide_main_context_menu(); 
							 hide_trash_context_menu(); break;
			case 'new'	   : t.hide_create_new(); break;
			default 	   : t.with_selected_go(event,'clear_clip');
		}
		
		return false;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'UP' || t.keypress == 'DOWN') {
		
		if (t.command_key_down) {
			
			t.modkey.shift(); 
			t.modkey.push('COMMAND');
		}
		
		if (t.shift_key_down) {
			t.modkey.shift(); 
			t.modkey.push('SHIFT');
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'UP' && txp.view == 'tr') {
		
		if (!t.editfocus) {
		
			event.preventDefault();
			
			if ((t.key || txp.key == 'COMMAND') && t.sort.col.toUpperCase() === 'POSITION') {
			
				t.with_selected_go(event,'move_up');
			
			} else {
				
				t.unselect_column();
				// t.select_row(0,-1);
				t.select_row(0,'UP');
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'DOWN' && txp.view == 'tr') {
		
		if (!t.editfocus) {
		
			event.preventDefault();
			
			if ((t.command_key_down || txp.key == 'COMMAND') && t.sort.col.toUpperCase() === 'POSITION') {
			
				t.with_selected_go(event,'move_down');
			
			} else {
				
				t.unselect_column();
				// t.select_row(0,1);
				t.select_row(0,'DOWN');
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'LEFT' && txp.view == 'tr') {
	
		if (!t.editfocus) {
	
			if (txp.checked.length) {
				
				if (txp.open.length) {
				
					t.with_selected_go(event,'close');
				
				} else {
					
					t.unselect_all_rows();
				}
				
			} else {
				
				if (txp.col) {
				
					if (t.command_key_down) 
						t.move_column(txp.col,'left');
					else
						t.select_column(txp.col,-1);
				} else {
					t.select_column(0,-1);
				}
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	if (t.keypress == 'RIGHT' && txp.view == 'tr') {
		
		if (!t.editfocus) {
		
			if (txp.checked.length) {
			
				t.with_selected_go(event,'open');
			
			} else {
				
				if (txp.col) {
					
					if (t.command_key_down) {
						t.move_column(txp.col,'right'); 
					} else
						t.select_column(txp.col,1);
				} else {	
					t.select_column(0,0);
				}	
			}
			
			return false;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
	// return false;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.handleKeyUp = function(event) {

	var t = txp.list;
	
	if (txp.keyboard == 'mac') {
		
		switch (event.keyCode) {
			case 91  : t.command_key_down = false; txp.key = ''; break; // safari
			case 224 : t.command_key_down = false; txp.key = ''; break; // mozilla
			case 17  : t.control_key_down = false; break;
		}
	
	} else if (event.keyCode == 17) {
	
		t.command_key_down = false;
	}
	
	switch (event.keyCode) {
		case 16  : t.shift_key_down   = false; break;
		case 37  : t.arrow_key_down   = false; break;
		case 38  : t.arrow_key_down   = false; break;
		case 39  : t.arrow_key_down   = false; break;
		case 40  : t.arrow_key_down   = false; break;
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.handleKeyPress = function(event) {
	
	if (txp.keyboard == 'mac') {
	
		if (txp.list.debug) console.log('press',event.keyCode);
		
		if (event.keyCode == 91 || event.keyCode == 224) {
			
			event.preventDefault();
		}
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.handleColumnHeaderClick = function(event,elem) {

	event.stopPropagation();
	
	if ($(elem).hasClass('selected')) {
		
		txp.list.unselect_column();
	
	} else {
	
		txp.list.select_column($(elem).attr('id'));
	}
}
	
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
txp.list.handleRowClick = function(event) {
	
	var t = txp.list;
	
	var tr = $(this).parent('tr');
	
	if (tr.hasClass('noclick') == false) {
		
		t.clicks++;
		t.dclick++;
			
		var id = parseInt(tr.attr('id').split('-').pop());
		var name = $(this).attr('class').split(' ').shift();
		
		if (!txp.checked.has(id)) {
			t.clicks = 0;
		}
		
		setTimeout(function() {
			t.dclick = 0;
		},t.dclickspeed);
		
		if (t.command_key_down) {
			
			if (t.dclick == 2) {
				
				t.doCheck(id);
				t.doClickOff();
				
				t.with_selected_go(event,'window');
				
			} else if (t.keypress == 'E') {
			
				t.open_checked_in_window('edit',id);
				
			} else {
			
				t.toggleCheckbox(id);
			}
			
		} else if (t.shift_key_down) {
		
			event.preventDefault();
			
			t.doClickOff();
			
			if (t.debug) console.log('Shift Click');
			
			t.toggleCheckbox(id);
			
			txp.selectrange2();
			
		} else {
		
			t.doUncheck(txp.docid); // uncheck main article 
			t.doUncheck(-id); 
			t.doCheck(id);
			
			if (t.dclick == 2) {
				t.hoist_row(id);
			} else if (t.clicks == 2) {
				t.with_selected_go(event,'edit',name);
			}
		}
		
		if (t.clicks == 2 || t.dclick == 2) {
			t.clicks = -1;
		}
		
		$("tr.view").removeClass('noclick');
		
		t.contextmenu.main.hide();
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
txp.list.handlerColumnChange = function(event,elem) {
	
	var value = $(elem).val();
	
	if (value == 'headers') {
	
		var headers = $("tr.headers");
		
		if (txp.headers == 'show') {
			
			headers.fadeOut(400,function() {
				headers.addClass('hidden');
			});
			
			txp.headers = 'hide';
		
		} else {
			
			headers.fadeIn(400,function() {
				headers.removeClass('hidden');
			});
			
			txp.headers = 'show';
		}
		
		$(elem).val('none');
		
		txp.update_window_session('headers',function() {
			console.log('ok');
		});
		
		return false;
	}
	
	txp.list.toggle_column(value);
}

// =============================================================================
// context menus 

txp.list.contextmenu.main.init = function() {
	
	var t = txp.list.contextmenu.main;
	
	t.elem = $('#main-context-menu');
	t.html = t.elem.html();
	t.elem.html('');
	
	$("tr.view").bind("contextmenu",function(event){
		
		event.preventDefault();
		event.stopPropagation();
		
		var chbox = $(this).find(".chbox input");
		var chboxid = parseInt(chbox.attr('id'));
			
		if ($(this).hasClass('checked') == false) {
			txp.list.toggleCheckbox(chboxid);
		}
			
		$(this).addClass('noclick');
		
		t.show(event,chboxid);
		
		return false;
	});
	
	$("tr.view td.title a.title").bind("contextmenu",function(event){
		
		event.stopPropagation();
	});
	
	$(document).bind("click",function(event){
		
		t.hide();
		
		$("tr.view").removeClass('noclick');
		
		// t.unselect_column();
	});
	
	t.hide = function() {
		
		t.elem.hide();
		t.elem.html('');
	};
	
	t.show = function(event,rowid) {
		
		txp.list.add_action('context');
		
		t.elem.html(t.html);
		
		t.elem.css({
        	top: event.pageY+'px',
        	left: event.pageX+'px'
		}).show();
		
		if (txp.docid == rowid) {
			
			// hide certain actions that do not apply to the main item
			
			t.elem.find(
				"li#cut,li#duplicate,li#group,li#alias,li#hoist,li#trash"
			).hide();
		}
		
		t.elem.find("a").bind("click",function(event){
		
			var option = $(this).parent().attr('id').replace('-','_');
			
			t.elem.hide();
			
			$("tr.view").removeClass('noclick');
			
			if (option) txp.list.with_selected_go(event,option);
			
			return false;
		});
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// trash context menu

txp.list.contextmenu.trash.init = function() {
	
	var t = txp.list.contextmenu.trash;
	
	t.elem = $('#trash-context-menu');
	t.html = t.elem.html();
	t.elem.html('');
	
	$("tr.footer div.trash a").bind("contextmenu",function(event) {
		
		show_trash_context_menu(event);
		
		return false;
	});
	
	var hide_trash_context_menu = function(event) {
		
		t.elem.hide();
		t.elem.html('');
	}
	
	var show_trash_context_menu = function(event) {
		
		txp.list.add_action('context');
		
		t.elem.html(t.html);
		
		t.elem.css({
			top: event.pageY - t.elem.height() - 21 + 'px',
			left: event.pageX - 28 + 'px'
		}).show();
		
		t.elem.find("a").bind("click",function() {
			
			var option = this.id;
			
			t.elem.hide();
			
			if (option == 'open') {
				$("tr.footer div.trash a").trigger('click');
			} 
			
			if (option == 'empty') {
				txp.list.with_selected_go(event,'empty_trash');
			}
			
			return false;
		});
	};
}

// =============================================================================

txp.list.get_table_column_names = function() {  
	
	var columns = [];
	
	$("table#list th").each(function(i) {
		
		var name = $(this).attr('id');
		
		if (name) { 
			
			columns.push(name);
			
			if ($(this).hasClass('selected')) {
				txp.col = name;
			}
			
			if (name == txp.col) {
				txp.list.select_column(name);
			}
		}
	});
	
	return columns;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.get_table_rows = function() {  
	
	var rows = [];
	
	$("table#list .child").each(function() {
		
		var id = parseInt($(this).attr('id').split('-').pop());
		var open = false;
		var is_checked = false;
		var position = parseInt($(this).attr('data-pos'));
		var chbox = $(this).find(".chbox input");
		var is_open = $(this).hasClass('open');
		var is_grid = $(this).hasClass('grid');
		
		if (txp.checked.has(id)) {
		 // txp.list.doCheck(id);
			is_checked = true;
		}
		
		if (is_open && !is_grid) {
			txp.open.push(id);
			open = true;
		}
		
		rows.push({
			'id':id,
			'position':position,
			'open':open,
			'checked':is_checked
			}
		);
		
	});
	
	return rows;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
txp.list.open_trash_window = function(event,href) {
	
	event.preventDefault();
	event.stopPropagation();
	
	var href   = [href];
	var width  = 700;
	var height = 390;
	
	href.push("win=new");
	href.push("mini=1");
	href.push("view=" + txp.view);
	
	if (txp.view == 'div') width = 500;
	
	window.open(href.join('&'),'trash','width='+width+',height='+height+',scrollbars,resizable');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
txp.list.open_checked_in_window = function(mode,id) {
	
	txp.list.keypress = null;
	txp.list.command_key_down = false;
					
	var mode = mode || 'view';
	var id   = id || txp.list.lastchecked;
	
  	var name   = 'window'+id;
    var href   = [];
    var width  = 745;
    var height = 480;
    var event  = txp.event;
    var step   = 'list';
    
    txp.list.toggleCheckbox(id);
	
	if (mode == 'view') {
		width = 620;
		href.push("view=" + txp.view);
	};
	
	if (mode == 'edit') {
		event  = 'article';
		step   = 'edit';
		width  = 980;
		height = 540;
	};
	
	href.push("event=" + event);
	href.push("step=" + step);
	href.push("opener=" + txp.winid);
	href.push("win=new");
	href.push("mini=1");
	href.push("id=" + id);
	
	href = "index.php?" + href.join('&');
	
	setTimeout(function() {
    	window.open(href,name,'width='+width+',height='+height+',scrollbars,resizable');
    },500);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.with_selected_go = function(event,value,column) {
	
	value = value.toLowerCase();
	
	if (column == undefined) column = '';
	
	if (txp.list.debug) console.log('go:',event,value,column);
	
	event.preventDefault();
	
	if (value == 'new') {
	
		txp.list.show_create_new();
		
		return false;
	}
	
	if (value == 'group') {
	
		txp.list.show_create_new('group');
		
		return false;
	}
	
	if (value == 'window') {
	
		txp.list.open_checked_in_window('view');
		
		return false;
	}
	
	if (value == 'view_site') {
	
		txp.update_window_session('checked');
  				
  		view_site(txp.winid);
		
		return false;
	}
	
	if (value == 'edit_window') {
		
		txp.list.open_checked_in_window('edit');
		
		return false;
	}
	
	if (value == 'hoist') {
		
		txp.list.hoist_row();
		
		return false;
	}
	
	if (value == 'update_db') {
		
		window.open(
			'index.php?update=XXX',
			'update_database',
			"width=400,height=300,scrollbars=yes");
		
		return false;
	}
	
	txp.list.showLoadingAnim();
	
	var action = document.getElementById('action');
	var option = document.createElement("option");
	option.setAttribute("value", value);
	action.appendChild(option);
	
	$("select#action").val(value);
	
	if (txp.list.command_key_down) {
		txp.key = 'COMMAND';
	} else if (txp.list.command_key_down) {
		txp.key = 'SHIFT';
	}
	document.longform.editcol.value = column;
	document.longform.scroll.value  = txp.get_scroll_point();
	
	document.longform.submit();
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.remove_column = function(name) {
	
	var name = name || txp.col;
	
	if (name) {
		
		if (name == txp.col) txp.col = '';
		
		var href    = [];
		var window  = "win=" + txp.winid;
		var event   = "event=" + txp.event;
		var step    = "step=list";
		var column  = "column=" + name;
		
		href.push(event);
		href.push(step);
		href.push(column);
		if (window) href.push(window);
	
		txp.update_window_session('checked',function() {
  			document.location.href = "?" + href.join('&');
  		});
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.toggle_column = function(name) {
	
	var href    = [];
	var window  = "win=" + txp.winid;
	var event   = "event=" + txp.event;
	var step    = "step=toggle_column";
	var column  = "column=" + name;
	
	href.push(event);
	href.push(step);
	href.push(column);
	if (window) href.push(window);
	
	txp.update_window_session('checked',function() {
  		document.location.href = "?" + href.join('&');
  	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.move_column = function(name,dir) {
	
	var href = [];
	
	var event  = "event="+txp.event;
	var window = "win=" + txp.winid;
	var step   = "step=move_column";
	var column = "column=" + name;
	var move   = "move=" + dir;
	
	href.push(event);
	href.push(step);
	href.push(column);
	href.push(move);
	if (window) href.push(window);
	
	txp.update_window_session('checked',function() {
  		document.location.href = "?" + href.join('&');
  	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.select_column = function(name,dir) {
	
	var dir = dir || 0;
	var pos = 0;
	
	if (dir == 'left')  dir = -1;
	if (dir == 'right') dir = 1; 
	
	if (!name) {
		
		pos = pos + dir;
	
	} else {
		
		pos = parseInt($('th#'+name).attr('data-pos')) + dir;
	}
	
	if (pos < 0) pos = txp.list.columns.length - 1;
	if (pos > txp.list.columns.length - 1) pos = 0;
	
	txp.list.unselect_column(); 
	
	name = txp.list.columns[pos];
	
	$('th#'+name).addClass('selected');
	$('td.col-'+pos).addClass('selected');
	
	txp.col = name;
	
	txp.list.add_action('column');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.unselect_column = function() {
	
	$("table#list th.selected").removeClass('selected');
	$('td.selected').removeClass('selected');
	
	txp.col = '';
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.select_row = function(id,dir) {
	
	var dir = dir || '';
	var columns = parseInt(document.longform.columns.value);
	var maxpos  = txp.list.rows.length - 1;
	var newpos  = 0;
	
	if (id) {
		
		txp.list.doUncheck(-id); 
		txp.list.toggleCheckbox(id);
		
	} else if (txp.checked.length == 0) {
		
		pos = (dir == 'DOWN' || dir == 'RIGHT') ? 0 : -1;
		
		if (pos < 0) pos = maxpos;
		if (pos > maxpos) pos = 0;
	
		id = txp.list.rows[pos].id;
		
		txp.list.toggleCheckbox(id);
		
		txp.row = id;
		
	} else {
		
		var lastchecked_id  = txp.checked.pop();
		var lastchecked_pos = parseInt($('#article-'+lastchecked_id).attr('data-pos'));
		
		txp.checked.push(lastchecked_id);
		
		switch (dir) {
			case 'UP'    : newpos = lastchecked_pos - (1 * columns); break;
			case 'DOWN'  : newpos = lastchecked_pos + (1 * columns); break;
			case 'LEFT'  : newpos = lastchecked_pos - 1; break;
			case 'RIGHT' : newpos = lastchecked_pos + 1; break;
		}
		
		if (newpos < 0) newpos = maxpos;
		if (newpos > maxpos) newpos = 0;
		
		if (!txp.list.shift_key_down && !txp.list.command_key_down) {
			
			txp.list.unselect_all_rows();
		}
		
		if (txp.list.command_key_down && txp.list.modkey[0] == "COMMAND") {
			
			txp.list.doUncheck(lastchecked_id);
		}
		
		txp.list.doCheck(txp.list.rows[newpos].id);
		
		txp.row = txp.list.rows[newpos].id;
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.unselect_all_rows = function() {
	
	$("tr.view, div.data.view").each(function(event){
		
		var chbox = $(this).find(".chbox input");
		var chboxid = parseInt(chbox.attr('id'));
		
		// txp.list.doUncheck(-chboxid);
		txp.list.doUncheck(chboxid); 
	});
	
	txp.row = -1;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.show_create_new = function(method) {
	
	var create = $('#create-new-article');
	var method = method || 'new';
	
	create.show();
	create.find('textarea.title').focus().blur().focus();
	document.create_new.edit_method.value = method;
	
	if (method == 'group') {
		create.find('.extra').hide();
	} else {
		create.find('.extra').show();
	}
	
	$('#create-new-article a.cancel').click(function() {
		txp.list.hide_create_new();
		return false;
	});
	
	$('#create-new-article a.save').click(function() {
		txp.list.submit_create_new();
		return false;
	});
	
	txp.list.create_new = 1;
	txp.list.add_action('new');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.submit_create_new = function() {
	
	document.create_new.checked.value  = document.longform.checked.value;
	document.create_new.selected.value = document.longform.checked.value;
	document.create_new.scroll.value   = txp.get_scroll_point();
	
	var text = $('#create-new-article textarea.title').val().trim();
	
	text = text.trim().split("\n");
		
	for (i=0;i<text.length;i++) {
		
		// remove starting bullet point char
		if (text[i].trim().charCodeAt(0) == 8226) {
		
			text[i] = text[i].trim().substr(1).trim();
		}
	}
		
	text = text.join("\n");
	
	if (text.length) {
	
		$('#create-new-article textarea.title').val(text);
	
		$('#create-new-article form').submit();
	}
	
	txp.list.hide_create_new();
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.hide_create_new = function() {
	
	var height = $('#create-new-article textarea.title').css('min-height');
	
	$('#create-new-article').hide();
	$('#create-new-article textarea.title').val('');
	$('#create-new-article textarea.title').css('height',height);
	$('#create-new-article select').val('');
	$("select#action").val('none');
	txp.list.create_new = 0;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.hoist_row = function(id) {
	
	if (!id) {
		
		id = txp.checked.shift();
		txp.list.unselect_all_rows();
		
	} else {
		
		txp.list.doUncheck(id);
	}
	
	txp.update_window_session('checked',function() {
	
		var href = [];
	
		href.push("event=" + txp.event);
		href.push("step=hoist");
		href.push("win=" + txp.winid);
		href.push("id=" + id);
	
		document.location.href = "?" + href.join('&');
	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.toggleCheckbox = function(box) {
	
	box = parseInt(box);
	
	if (box) {
	
		if (txp.checked.has(box)) {
			
			txp.list.doUncheck(box);
			
		} else { 
			
			txp.list.doCheck(box);
			
			txp.list.lastchecked = box;
		}
	}
	
	return false;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.doCheck = function(box) {
	
	if (!txp.checked.has(box)) {
	
		txp.checked.push(box);
		
		txp.list.lastchecked = box;
	}
	
	$(".chbox input#"+box).attr('checked', true);
	$("#article-"+box).addClass('checked');
	
	txp.list.checked = [];
	
	for (var i=0;i<txp.list.rows.length;i++) {
	
		if (txp.checked.has(txp.list.rows[i].id)) {
		
			txp.list.rows[i].checked = true;
			txp.list.checked.push(txp.list.rows[i].id);
		}
	}
	
	txp.checked = txp.list.checked;
	
	document.longform.checked.value = txp.checked.join(','); 
	
	txp.list.add_action('check');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.doUncheck = function(box) {
	
	if (box < 0) {
	
		// uncheck everything except box
	
		box = Math.abs(box);
		
		txp.checked.each(function(val) { 
			
			if (val != box) { 
				$(".chbox input#"+val).attr('checked', false);
				$("#article-"+val).removeClass('checked');
			}
		});
		
		if (txp.checked.has(box)) {
			// txp.checked.unset().push(box);
			 txp.checked = [box];
		} else {
			 txp.checked = [];
		}
		
	} else {
		
		// uncheck box
		
		txp.checked.unset(box);
		
		$(".chbox input#"+box).attr('checked', false);
		$("#article-"+box).removeClass('checked');
	}
	
	for (var i=0;i<txp.list.rows.length;i++) {
		
		if (txp.checked.has(txp.list.rows[i].id)) {
		
			txp.list.rows[i].checked = true;
			txp.list.checked.push(txp.list.rows[i].id);
		}
	}
	
	document.longform.checked.value = txp.checked.join(',');
	
	txp.list.select_all_chbox.trigger('uncheck');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.doClickOff = function() {

	var scroll_point = txp.get_scroll_point();
	
	txp.list.clickoff.elem.html(txp.list.clickoff.html);
	txp.list.clickoff.elem.children("input").focus();
	
	window.scrollTo(0,scroll_point);
	
	txp.list.clickoff.elem.html('');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// This function will return selected text within a single html tag. 

txp.list.getSelText = function() {

	var txt = '';
	
	if (window.getSelection) {
		txt = window.getSelection();
	} else if (document.getSelection) {
		txt = document.getSelection();
	} else if (document.selection) {
		txt = document.selection.createRange().text;
	} else {
		return txt;
	}
	
	if (txt) { 
		if (txt.anchorNode) {
		
			var start  = txt.anchorOffset;
			var length = txt.extentOffset - txt.anchorOffset;
		
			if (length < 0) {
				start  = txt.anchorOffset + length;
				length = Math.abs(length);
			}
		
			if (txt.anchorNode.data) {
				return txt.anchorNode.data.substr(start,length);
			}
		}
	}
	
	return '';
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.handleDragStart = function(e) {
	
	var id = this.id.split('-').pop();
	
	e.dataTransfer.setData('win',txp.winid);
	e.dataTransfer.setData('type','image');
	e.dataTransfer.setData('id',id);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.handleDragEnd = function(e) {
	
	var href = [];
	
	href.push("event=" + txp.event);
	href.push("step=list");
	href.push("win=" + txp.winid);
	href.push("scroll=" + txp.get_scroll_point());
	
	document.location.href = "?" + href.join('&');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// resize grid view area

txp.list.resize_grid_view = function() {
	
	if (txp.view == 'tr') return;
	
	var grid = $('tr.grid div.pad');
	var grid_cell = $('td.grid div.pad div.data');
	
	var grid_width = grid.innerWidth();
	var grid_cell_width = grid_cell.outerWidth();
	
	document.longform.columns.value = Math.floor((grid_width-15)/grid_cell_width);
	
	if (txp.mini) {
	
		var win_height = $(window).height();
		var grid_height = grid.height();
		
		var content_height = 0;
		content_height += $('#masthead').height();
		content_height += $('#messagepane').height();
		content_height += $('#content').height();
		content_height -= $('tr.footer td').height();
		
		var space = win_height - content_height;
		
		if (grid.length) {	
			grid.height(grid_height + (win_height - content_height) - 60);
		}
	}
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// resize list view table

txp.list.resize_list_view = function() {
	
	if (txp.mini == 0 || txp.view == 'div') return;
	 
	var win_height = $(window).height();
	
	var content_height = 0;
	content_height += $('#masthead').height();
	content_height += $('#messagepane').height();
	content_height += $('#content').height();
	content_height -= $('tr.footer td').height();
	
	var space = win_height - content_height;
	var lines = 0;
	
	while (space > 30) {
		lines += 1; 
		space -= 30; 
	}
	
	for (var i=1, tr=''; i <= lines; i++) {
		
		txp.list.row_count += 1;
		
		for (var j=0, td=''; j < txp.list.columns.length; j++) {
			td += '<td class="col col-'+j+'">&nbsp;</td>';
		}
	
		td += '<td class="chbox col-'+j+'" style="padding:0px"></td>';
		
		var odd_even = (txp.list.row_count % 2 == 0) ? 'even' : 'odd';
		
		tr += '<tr class="data '+odd_even+' empty empty-'+txp.list.row_count+'">'+td+'</tr>';
	}
	
	$(tr).insertBefore('tr.footer');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// truncate text

txp.list.truncate = function(text,max) {	

	if (text.length > (max + 1)) {
		
		var tmp = '';
		
		text = text.split(' ');
		
		for (var i=0; i < text.length; i++) {
			
			if (tmp.length + text[i].length <= max) {
			
				tmp += (tmp) ? ' ' + text[i] : text[i]; 
			
			} else {
				
				return tmp;
			}
		}
		
		text = tmp;
	}
	
	return text;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.add_action = function(name) {

	if (txp.list.actions[0] != name) txp.list.actions.unshift(name);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.showLoadingAnim = function(delay) {
	
	var delay = delay || 1000;
	
	setTimeout(function() {
		$("div#processing").show();
	},delay);
}