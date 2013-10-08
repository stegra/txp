txp.list.grid = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.grid.init = function() {
	
	console.log('init list grid');
	
	var t = txp.list;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("table#list div.panel a").bind("click",function(event){	
		
		var panel = $(this).parents('div.panel');
		var id = parseInt(panel.attr('id').split('-').pop());
		
		// - - - - - - - - - - - - - - - - -
		
		t.clicks++;
		t.dclick++;
		
		if (!txp.checked.has(id)) {
			t.clicks = 0;
		}
			
		setTimeout( function() {
			t.dclick = 0;
		},t.dclickspeed);
		
		// - - - - - - - - - - - - - - - - -
		
		if (t.command_key_down) {
		
			if (t.dclick == 2) {
				
				t.doCheck(id);
				t.doClickOff();
				
				t.with_selected_go(event,'window');
				
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
				
			t.doUncheck(-id); 
			t.doCheck(id);
			
			if (t.dclick == 2) {
				
				var href = [];
				
				href.push("event=" + txp.event);
				href.push("win=" + txp.winid);
				href.push("id=" + id);
				
				document.location.href = "?" + href.join('&');
			}
		}
		
		// - - - - - - - - - - - - - - - - -
		
		if (t.clicks == 2 || t.dclick == 2) {
			t.clicks = -1;
		}
				
		return false;
	});

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// grid panel title vertical centering
	
	$('table#list div.panel span.title').each(function() {
		
		var title  = $(this);
		var panel  = $(this).parents('div.panel');
		var height = title.height();
		var maxchar = 0;
		
		if (panel.hasClass('size-xx')) {
			var margin_top = Math.round((24 - height) / 2) + 4;
			title.css('margin-top',margin_top + 'px');
			maxchar = (txp.event == 'sites') ? 25 : 20;
		}
		
		if (panel.hasClass('size-x')) {
			maxchar = (txp.event == 'sites') ? 20 : 18;
		}
		
		if (panel.hasClass('size-y')) {
			maxchar = (txp.event == 'sites') ? 20 : 11;
		}
		
		if (maxchar) {
			title.html(t.truncate(title.html(),maxchar));
		}
		
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// key down
	
	$(document).keydown(function(event) {
	
		if (t.arrow_key_down && !t.editfocus) {
			
			event.preventDefault();
			
			if ((t.command_key_down || txp.key == 'COMMAND') && sort.col.toUpperCase() === 'POSITION') {
				
				t.with_selected_go(event,'move_'+t.arrow_key_down);
				
			} else {
				
				t.unselect_column();
				t.select_row(0,t.arrow_key_down);
			}
			
			return false;
		}
	
	});
	
}