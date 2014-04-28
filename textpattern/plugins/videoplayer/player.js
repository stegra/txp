$(document).ready(function(){	

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// HTML5 Audio Player
	
	$('audio').bind('play', function() {
   		
   		$(this).addClass('playing');
   		
   		console.log('playing');
   		
   		if ($(this).hasClass('unplayed')) {
   			
   			incrementDownloadCount(this.id);
   			
   			$(this).removeClass('unplayed');
		}
    });
    
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// HTML5 Video Player
	
	$('video').bind('play', function() {
   		
   		$(this).addClass('playing');
   		
   		if ($(this).hasClass('unplayed')) {
   			
   			incrementDownloadCount(this.id);
			
			$(this).removeClass('unplayed');
		}
    });
  	
  	// click poster to play
  	
  	$("div.play-video").click(function() {
		
		$(this).find('.start').hide();
		// $(this).find('.start').fadeOut('slow');
		
		if ($(this).find('video').hasClass('unplayed')) {
			$(this).find('video').addClass('playing');
			$(this).find('video').trigger('play');
			var video = document.getElementById("v11");
			video.play();
		}
	
	});

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// Flash Player
	
	$('object.player').each(function() {
		
		var player   = $(this);
		var playerID = player.attr('id');
		
		jwplayer(playerID).onPlay(function() { 
			
			if (player.hasClass('unplayed')) {
			
				incrementDownloadCount(playerID.substr(6));
			
				player.removeClass('unplayed');
			}
		});
	});

});

//-------------------------------------------------------------
// Download Count

function incrementDownloadCount(id) {

	sendAsyncEvent(
		{
			event: 'file',
			step: 'increment_count',
			id: id
		},
		function(data) {
			console.log(data);
		}
	);
}

//-------------------------------------------------------------
// AJAX

function sendAsyncEvent(data, fn) {

	// data.app_mode = 'async';
	data.nohead = '1';
	
	$.post('/admin/index.php', data, fn);
}

// -------------------------------------------------------------

function getQueryVariable(variable) {

	var query = window.location.search.substring(1);
	var vars = query.split("&");

	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		if (pair[0] == variable) {
			return pair[1];
		}
	}
}

