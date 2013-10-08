$(document).ready(function() {
	
	var id = 0;
	var errors = 0;
	
	// ---------------------------------------------------------
	
	$("b").each(function() {
		
		var text = $(this).html().toLowerCase();
		
		if (text == 'warning' || text == 'notice') {
			
			errors++;
			
			$(this).html('Error');
		}
		
		if (text.match('error')) {
			errors++;
		}
		
	});
	
	// ---------------------------------------------------------
	
	$("h3,pre,p").each(function() {
	
		if ($(this).is('p')) {
			id++;
		}
		
		if ($(this).is('h3')) {
			
			id++;
			$(this).attr('id','group'+id);
		}
		
		if ($(this).is('pre')) {
			
			$(this).addClass('group'+id);
			
		}
	});
	
	// ---------------------------------------------------------
	
	$("h3").hover(
	
		function() {
			$(this).addClass('hover');
		},
		function() {
			$(this).removeClass('hover');
		}
	);
	
	// ---------------------------------------------------------
	
	$("h3").click(function() {
		
		var id = $(this).attr('id');
		
		if ($(this).hasClass('open')) {
		
			$(this).removeClass('open');
			$("pre."+id).hide();
		
		} else {
			
			$(this).addClass('open');
			$("pre."+id).show();	
		}
		
		// console_pane_api.reinitialise();
	});
	
	// ---------------------------------------------------------
	
	$("div.site h2 a").click(function() {
		
		var site = $(this).parent().parent();
		
		if (site.hasClass('open')) {
		
			site.removeClass('open');
			site.find('div.version').removeClass('open');
			site.find('h3').removeClass('open');
			site.find("pre").hide();
		
		} else {
			
			site.addClass('open');
		};
		
		return false;
	});
	
	// ---------------------------------------------------------
	
	$("div.version p a").click(function() {
		
		var version = $(this).parent().parent();
		
		if (version.hasClass('open')) {
		
			version.removeClass('open');
			version.addClass('closed');
			version.find('h3').removeClass('open');
			version.find("pre").hide();
		
		} else {
			
			version.removeClass('closed');
			version.addClass('open');
		};
		
		return false;
	});
	
	// ---------------------------------------------------------
	
	if (errors) {
		
		$("div#footer p").addClass('errors');
		
		$("div#footer p span#errors").html(errors).addClass('error');
		
		if (errors == 1) $("div#footer p span.errors").html('error');
	}
	
	// ---------------------------------------------------------
		
	$("body.single div.site").addClass('open');
	$("body.single div.version").addClass('open');
	
	// console_pane_api.scrollToBottom();
	
	// window.scrollTo(0,100000);
	
});

// -----------------------------------------------------------------------------
	
	function toDo(site_id,last)
	{
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// show progress
		
		var progress = document.getElementById('prog-site-'+site_id);
		var percent  = 0;
		
		var amount = { 
			current : parseInt(progress.innerHTML.split('/').shift()), 
			total   : parseInt(progress.innerHTML.split('/').pop()) 
		}
		
		if (last) { 
			
			amount.current = amount.total
		}
		
		if (amount.current) {
			
			percent = Math.round((amount.current * 100) / amount.total);
			
			if (percent > 100) percent = 100;
			
			progress.style.width = percent + '%';
		} 
		
		// console.log(amount.current,amount.total,percent); 
		
		if (!last) { 
		
			amount.current += 1;
		}
		
		progress.innerHTML = amount.current + '/' + amount.total;
	}
	