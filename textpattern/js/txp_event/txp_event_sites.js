txp.list.sites = {};
txp.edit.sites = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.sites.init = function() {

	console.log('init list sites');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // close download archive popup
    
    $('#download-archive a.close').click(function(event) {
    	
    	$('#download-archive').hide();
    	
    	return false;
    });
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open new window for update database action
	
	$('form#longform input#go').click(function() {
	
		var action = $('form#longform select#action').val();
		
		if (action == 'update_db') {
			
			window.open(
				'index.php?update='+txp.checked.join(','),
				'update_database',
				"width=400,height=300,scrollbars=yes");
			
			return false;
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('form#longform').submit(function() {
		
		var action = $("select#action");
		
		if (action.val() == 'view_site') { 
  			
  			txp.update_window_session('checked');
  				
  			txp.list.sites.view_site(txp.winid);
  			
  			action.val('none');
  		}
  		
	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.sites.init = function() {
	
	console.log('init edit sites');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('input.publish.create-site').click( function() {
    	
    	document.forms.article.create_site.value = 1;
   
	});
   
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // change "path to site" and "site url" fields when site name changes
    
    var site_name_input = $('body.edit input[name=Name]');
    var site_dir_input  = $('body.edit input[name=SiteDir]');
	var site_url_input  = $('body.edit input[name=URL]');
	var site_name 		= site_name_input.val();
	
	site_name_input.change(function() {
		
		var site_dir = site_dir_input.val();
		site_dir_input.val(site_dir.replace(new RegExp(site_name+'$'),$(this).val()));
		
		var site_url = site_url_input.val();
		site_url_input.val(site_url.replace(new RegExp(site_name+'$'),$(this).val()));
		
		site_name = $(this).val();
	});
	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.sites.view_site = function(id) {
	
	var href = "index.php?event=sites&step=view&app_mode=async&win="+id;
	
	setTimeout( function() {
		txp.popup(href,'view_site',900,700);
    },500);
}
