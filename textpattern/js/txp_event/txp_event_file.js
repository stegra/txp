txp.list.file = {};
txp.edit.file = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.file.init = function() {
	
	console.log('init list file');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.file.init = function() {

	console.log('init list file');

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('audio').bind('play', function() {
   		
   		$(this).addClass('playing');
   		
   		/*
   		if ($(this).hasClass('unplayed')) {
   			
   			txp.sendAsyncEvent(
				{
					event: txp.event,
					step: 'increment_count',
					id: this.id
				}
			);
			
			$(this).removeClass('unplayed');
		} */
    });
    
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('video').bind('play', function() {
   		
   		$(this).addClass('playing');
   		
   });
  	
  	// click poster to play
  	
  	$("div.play-video").click(function() {
		
		$(this).find('.start').fadeOut('slow');
		
		if ($(this).find('video').hasClass('unplayed')) {
		
			$(this).find('video').addClass('playing');
			$(this).find('video').removeClass('unplayed');
			$(this).find('video').trigger('play');
		}
	
	});
	
}
