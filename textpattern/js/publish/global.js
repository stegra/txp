$(document).ready(function(){	

	$('audio').bind('play', function() {
   		
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
	
		window.open($(this).attr('href'),'edit-article','width=950,height=625,scrollbars=yes,toolbar=yes');
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open edit page links in a new window
	
	$('a.admin-link').click(function() {
	
		window.open($(this).attr('href'),'admin-page','width=950,height=625,scrollbars=yes,toolbar=yes');
		
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
	// report screen size 
	
	setTimeout(function() {
		
		var width = getScreenWidth();
		var href = document.location.href.split('/');
		var action = (href[3].match(/^~/)) 
			? '/' + href[3] + '/index.html' 
			: '/index.html';
		var id = $('body').attr('data-logid'); 
		
		if (id && width) {
			
			$.post(action, { 
				id:	id,
				screensize: width
			}, function(data) {
				// console.log(data);
			});
		}
		
	},300); 
	
});

//-------------------------------------------------------------
// AJAX

function sendAsyncEvent(data, fn)
{
	data.app_mode = 'async';
	$.post('/admin/index.php', data, fn, 'xml');
}

//-------------------------------------------------------------

function getScreenWidth()
{
	if (window.screen != null) 
		return window.screen.availWidth;

	if (window.innerWidth != null)
		return window.innerWidth;

	if (document.body != null)
		return document.body.clientWidth;

	return 0;
}

