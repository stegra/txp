var edit_window = null;

$(document).ready(function(){	

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	if (txp.plugins['audioplayer'] != undefined) {
		
		txp.plugins.audioplayer.init();
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

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
			
			linkhost = linkhost.replace('www.','');
			sitehost = sitehost.replace('www.','');
			
			if (linkhost && (linkhost != sitehost)) {
			
				window.open(href,'external','width=1000,height=700,scrollbars=yes,toolbar=yes');
			
				return false;	
			}
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
		
		edit_window = window.open($(this).attr('href'),'edit-article','width=950,height=625,scrollbars=yes,toolbar=yes');
		
		// send extra requests to this window if any 
		
		var extra = $(this).attr('rel');
		
		if (extra) {
		
			extra = extra.split(';');
			
			var countdown = 20;
			var inter1 = null; // wait for the id from the opened window
			var inter2 = null; // delay between extra requests 
			
			inter1 = setInterval(function() {
			
				if (edit_window.id) {
					
					inter2 = setInterval(function() {
						
						if (extra.length) {
						
							edit_window.location.href = '/admin/index.php?'+extra.shift()+'&win='+edit_window.id;;
							
						} else {
						
							clearInterval(inter2);
						}
						
					},1000);
					
					countdown = 0;
				}
				
				if (countdown == 0) clearInterval(inter1);
				
				countdown -= 1;
				
			},100);
		}
				
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// open edit page links in a new window
	
	$('a.admin-link').click(function() {
	
		var admin_window = window.open($(this).attr('href'),'admin-page','width=950,height=625,scrollbars=yes,toolbar=yes');
		
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
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// inspect meta tags and canonical url
	/*
	var meta = '<table>'+"\n";
	
	$('meta').each(function() { 
		
		if ($(this).attr('name')) {
			
			var name = $(this).attr('name');
			var content = $(this).attr('content');
			var cls = $(this).attr('class');
			var edit = '';
			if (name == 'keywords') {
				content = content.replace(/,/g,', ');
			}
			
			if ($(this).hasClass('edit')) {
				var id   = $(this).attr('id');
				var link = ($(this).hasClass('local')) ? 'Edit' : 'Add';
				var href = '/admin/index.php?event=article&step=edit&id='+id+'#advanced';
				edit += "\n"+'<td><a target="new" href="'+href+'">'+link+'</a></td>';
			}
			
			cls = (cls) ? name + ' ' + cls : name;
			
			meta += '<tr class="'+cls+'">';
			meta += '<td class="name">'+ name.replace(/\-/g,' ') + '</td>';
			meta += '<td class="content">'+ content + '</td>' + edit;
			meta += '</tr>'+"\n";		
		}
	});
	
	$('link').each(function() { 
	
		if ($(this).attr('rel') == 'canonical') {
			
			var href = $(this).attr('href');
			var link = '<a target="new" href="'+href+'">'+href+'</a>';
			
			meta += '<tr class="canonical">';
			meta += '<td class="name">canonical url</td>';
			meta += '<td class="content">'+ link + '</td>';
			meta += '</tr>'+"\n";	
		}
	});
	
	meta += '</table>';	
	
	$('body').append('<div id="inspect-meta">'+meta+'</div>');
	*/
});

//-------------------------------------------------------------
// AJAX

function getWindowID(id)
{
	edit_window.id = id;
}

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


