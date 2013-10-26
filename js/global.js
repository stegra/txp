$(document).ready(function(){	
	
	if (window['txp'] != undefined && txp['plugins'] != undefined) {
	
		for (plugin in txp.plugins) txp.plugins[plugin].init();
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('video').bind('play', function() {
   		
   		$(this).addClass('playing');
   		
   		if ($(this).hasClass('unplayed')) {
   			
   			sendAsyncEvent(
				{
					event: 'file',
					step: 'increment_download_count',
					id: this.id
				}
			);
			
			$(this).removeClass('unplayed');
		}
    });
  	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
  	// click poster to play
  	
  	$("div.play-video").click(function() {
		
		$(this).find('.start').fadeOut('slow');
		
		if ($(this).find('video').hasClass('unplayed')) {
			$(this).find('video').addClass('playing');
			$(this).find('video').trigger('play');
		}
	
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open external links in a new window
	
	$('a').click(function() {
		
		var href = $(this).attr('href');
		var linkhost = $(this).context.hostname;
		var sitehost = document.location.hostname;
		
		if (linkhost && (linkhost != sitehost)) {
			
			window.open(href,'external','width=1000,height=700,scrollbars=yes,toolbar=yes');
			
			return false;	
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open instruction links in a new window
	
	$('body.instructions #mainContent a').click(function() {
	
		window.open($(this).attr('href'),'instructions','width=920,height=550,toolbar=yes');
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open edit page links in a new window
	
	$('a.edit').click(function() {
	
		window.open($(this).attr('href'),'edit-article','width=950,height=625');
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open original size image in a new window
	
	$('a.magnify-image').click( function() {
		
		var href = $(this).attr('href');
		var id   = $(this).attr('id');
		
		var win = {};
		var img = new Image();
		img.src = href;
		
		setTimeout(function() {
		
			win.height = (img.height > screen.availHeight-50) ? screen.availHeight-50 : img.height;
			win.width  = (win.height < img.height) ? (win.height / img.height) * img.width : img.width;
		
			window.open(href,id,"width="+win.width+",height="+win.height+",scrollable=yes");
		
		},500);
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// current section in main nav
	
	$('ul li').each(function(){
		
		var id = $(this).attr('id');
		
		if ($('body').hasClass(id)) {
			
			$(this).addClass('selected');
		}	
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// sub navigation
	
	$('ul > li').hover(
		
		function() {
			if (!$(this).hasClass('selected')) {
				$(this).children('ul').show();
			};		
		},
		function() {
			if (!$(this).hasClass('selected')) {
				$(this).children('ul').hide();	
			};	
		}
	);
	
});

//-------------------------------------------------------------
// AJAX

function sendAsyncEvent(data, fn)
{
	data.app_mode = 'async';
	$.post('/admin/index.php', data, fn, 'xml');
}

