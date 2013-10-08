txp.plugins.pulldown = {
	items : {}
};

// -----------------------------------------------------------------------------

txp.plugins.pulldown.init = function() {
	
	console.log('init pulldown');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// build alternative select list
	
	$('div.alt-select').each( function () {
	
		var alt_select = $(this);
		var select = alt_select.find('select');
		var select_id = select.attr('id');
		var select_list_items = [];
		
		var cover = ($.browser.mozilla) ? '<div data-id="'+select_id+'" class="cover"><'+'/div>' : '';
		alt_select.prepend('<ul class="select-'+select_id+'"><'+'/ul>'+cover);
		
		var select_list = alt_select.find('ul');
		var select_cover = alt_select.find('.cover');
		
		select_list.css('display','none');
		
		select.find('option').each( function () {
			
			var option   = $(this);
			var label    = option.html();
			var value    = option.attr('value');
			var key      = option.attr('data-key');
			var type  	 = option.attr('class');
			var sel      = (option.attr('selected')) ? ' selected' : '';
			var item     = '';
			
			label = label.replace(/\s(\W+)$/,'<span>&#10003;</span>');
			
			key = (key) ? '<span>'+key+'<'+'/span>' : '';
		
			item = (value) 
				? '<li class="'+type+' '+sel+'" data-value="'+value+'"><a href="#">'+label+key+'<'+'/a><'+'/li>'
				: '<li class="line"><hr noshade size="1"/><'+'/li>';
			
			select_list_items.push(item);
		});
		
		txp.plugins.pulldown.items[select_id] = select_list_items.join("\n");
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// real select list
		
		select_cover.click( function (event) {
			
			event.stopPropagation();
			
			txp.plugins.pulldown.open_list(event,$(this).attr('data-id'));
		});
		
		select.mousedown( function (event) {
			
			event.preventDefault();
			event.stopPropagation();
			
			txp.plugins.pulldown.open_list(event,$(this).attr('id'));
			
			return false;
		});
	});
	
}

// -----------------------------------------------------------------------------
// close alternative select list

txp.plugins.pulldown.close_list = function(list) {

	list.hide();
	list.html('');
}

// -----------------------------------------------------------------------------
// open alternative select list

txp.plugins.pulldown.open_list = function(event,id) {
	
	var select_list = $("ul.select-"+id);
	
	select_list.html(txp.plugins.pulldown.items[id]);
	
	var window_height = txp.getClientHeight();
	var top_space = event.pageY - $(window).scrollTop();
	var bottom_space = window_height - top_space;
	var list_height = select_list.outerHeight();
	var move_up = (list_height > bottom_space + 15) ? bottom_space - list_height : 0;
	
	select_list.css('top',move_up+'px');
	select_list.show();
	
	var list_item = select_list.find('li');

	list_item.hover( 
		function () { $(this).addClass('hover'); },
		function () { $(this).removeClass('hover'); } 
	);
	
	list_item.click( function (event) {
		
		event.preventDefault();
		event.stopPropagation();
		
		var option = $(this);
		var value = option.attr('data-value');
		
		$('select#'+id).val(value); 
		$('select#'+id).trigger('change');
		
		list_item.removeClass('selected');
		option.addClass('selected');
		
		txp.plugins.pulldown.close_list(select_list);
		
		$('body').unbind('click');
	});

	$('body').bind('click', function (event) {
		
		event.preventDefault();
		event.stopPropagation();
		
		txp.plugins.pulldown.close_list(select_list);
		
		$('body').unbind('click');
		
		return false;
	});
}