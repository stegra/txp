// -----------------------------------------------------------------------------

txp.plugins.audioplayer = {

	type	: 'flash', 
	length	: 0, 
	current	: 0, 
	playall	: false,
	loop	: false, 
	tracks  : [],
	
// -----------------------------------------------------------------------------

	init : function() {
	
		console.log('init audioplayer');
		
		var t = txp.plugins.audioplayer;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// html5 player type setting
		
		if ($.browser.webkit || $.browser.safari) {
			t.type = 'html5';
		}
		
		if ($.browser.msie && $.browser.version >= 9.0) {
			t.type = 'html5';
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// play all setting
		
		if ($('.playlist').hasClass('playall')) {
			t.playall = true;
			if ($('.playlist .playall').attr('checked','checked'));
		}
		
		if ($('.playlist .playall').attr('checked')) {
			t.playall = true;
		}

		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// setup players
		
		$('.audio-player').each( function() {
			
			var swf       = "/admin/plugins/jwplayer/player.swf";
			
			var container = $(this);
			var download  = container.find('.download');
			var time      = container.find('.time');
			var ext       = container.find('.type').attr('id');
			var track     = $(this).attr('id');
			var src       = download.attr('rel');
			var type	  = t.type;
			var playnext  = container.hasClass('playnext');
			var duration  = '00:00';
			
			var control = document.createElement('a');
			control.setAttribute('class','control');
			container.prepend(control);
			control = container.find('.control');
			
			control.attr('data-track',track);
			control.attr('id','track-'+track);
			control.attr('title','Play');
			control.html('Play');
			
			var player = document.createElement('div');
			player.setAttribute('class','player');
			container.append(player);
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// firefox: use html5 player if ogg file is available
			// otherwise use flash player with mp3 file
			
			if ($.browser.mozilla) {
			
				container.find('.type').each( function() {
					
					if ($(this).attr('id') == 'ogg') { 
						
						type = 'html5';
						ext  = 'ogg';
						src  = $.trim($(this).attr('data-src'));
					}
				});
			
			};
			
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Load HTML5 Player
			
			if (type == 'html5') { 
			
				// console.log('html5',src);
				
				var audio = document.createElement('audio');
				audio.setAttribute('src',src);
				player.appendChild(audio);
				
				audio.addEventListener("loadedmetadata", function() { 
				
					duration = t.formatTime(audio.duration);
					time.html(duration);
				
				}, true);
				
				audio.addEventListener("ended", function() { 
				
					t.stop();
					
					if (t.playall || t.tracks[track].playnext) 
						t.next();
						
				}, true);
			}
	
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			// Load FLASH Player
			
			if (type == 'flash') { 
				
				// console.log('flash',src);
				
				var trackid = 'track'+track+'player';
				
				player.setAttribute('id',trackid);
				
				var audio = jwplayer(trackid);
				
				audio.setup({
					flashplayer:swf, file:src, height:0, width:0
				});
				
				audio.onReady( function () {
					// console.log(audio.getDuration());
				});
				
				audio.onComplete( function () {
				
					t.stop();
					
					if (t.playall || t.tracks[track].playnext) 
						t.next();
				});
				
				$('#'+trackid+'_wrapper').css('height','0px');
				$('#'+trackid+'_wrapper').css('clear','both');
			}
	
			// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			
			t.tracks[track] = { 
				type	  : type,
				src       : src,
				ext 	  : ext,
				container : container,
				control   : control,
				player    : audio, 
				playing   : false, 
				interval  : null, 
				time	  : time, 
				duration  : duration,
				playnext  : playnext,
				unplayed  : true
			};
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.audio-player .control').click( function () {
			
			var track = $(this).attr('data-track');
			
			if (track != t.current) {
				
				t.stop(t.current);
				
				t.play(track);
				
			} else {
				
				if (t.tracks[track].playing) { 
				
					t.pause(track);
				
				} else {
				
					t.play(track);
				}
			}
			
			return false;
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.playlist .rewind a').click( function () {
			
			t.rewind(); return false;
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.playlist .rewind').hover( 
		
			function () {
				$(this).addClass('hover');
			}, 
			function () {
				$(this).removeClass('hover');
			}
		);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.playlist .playnext').click( function () {
			
			t.next(); return false;
		});
	
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.playlist .playprev').click( function () {
			
			t.prev(); return false;
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('.playall').click( function () {
			
			t.all();
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		$('.playlist audio').bind('play', function() {
			
			$(this).addClass('playing');
			
			var player = $(this).parent().parent();
			var id = player.attr('id');
			
			if (player.hasClass('unplayed')) {
				
				player.removeClass('unplayed');
			}
		});
	},

// -----------------------------------------------------------------------------
	
	play : function(track) {

		if (!track) return;
		
		var t = txp.plugins.audioplayer;
		var audio = t.tracks[track];
		
		if (!audio.playing) { 
		
			t.pause(t.current);
			
			audio.player.play();
			
			audio.interval = setInterval(function() {
				
				var currentTime = (audio.type == 'html5') 
					? audio.player.currentTime 
					: audio.player.getPosition();
				
				audio.time.html(t.formatTime(currentTime));
				
			},1000);
			
			if (audio.unplayed) {
				t.incDownloadCount(track)
			}
			
			t.current = track;
			audio.playing = true;
			audio.unplayed = false;
			audio.container.addClass('playing');
			audio.container.removeClass('paused');
			audio.control.html('Pause');
			
			$('.audio-player').removeClass('paused');
		}
	},

// -----------------------------------------------------------------------------
	
	pause : function(track) {

		if (!track) return;
		
		var t = txp.plugins.audioplayer;
		var audio = t.tracks[track];
		
		if (audio.playing) { 
		
			audio.player.pause();
			
			audio.playing  = false;
			audio.container.removeClass('playing');
			audio.container.addClass('paused');
			audio.control.html('Play');
				
			clearInterval(audio.interval);
		}
	},

// -----------------------------------------------------------------------------
	
	stop : function(track) {
		
		var t = txp.plugins.audioplayer;
		var track = track || t.current;
		
		if (!track) return false;
		
		var audio = t.tracks[track];
		
		audio.player.pause();
		t.rewind(track);
		audio.time.html(t.formatTime(audio.player.duration));
				
		audio.playing = false;
		audio.container.removeClass('playing');
		audio.container.removeClass('paused');
		audio.control.html('Play');
		
		clearInterval(audio.interval);
	},

// -----------------------------------------------------------------------------
	
	rewind : function(track) {
		
		var t = txp.plugins.audioplayer;
		if (!track) track = t.current;
		
		var audio = t.tracks[track];
		
		audio.player.currentTime = 0;
		audio.time.html('0:00');
	},
	
// -----------------------------------------------------------------------------
	
	next : function() {
		
		var t = txp.plugins.audioplayer;
		
		if (parseInt(t.current) < t.length) {
		
			t.play(parseInt(t.current) + 1);
		}
	},

// -----------------------------------------------------------------------------
	
	prev : function() {
		
		var t = txp.plugins.audioplayer;
		
		if (parseInt(t.current) > 1) {
		
			t.play(parseInt(t.current) - 1);
		}
	},

// -----------------------------------------------------------------------------
	
	all : function() {
		
		var t = txp.plugins.audioplayer;
		
		if (t.playall) {
		
			t.playall = false;
		
		} else {
		
			t.playall = true;
			
			if (t.current == 0) { 
				t.play(1);
			}
		}
	},
	
// -----------------------------------------------------------------------------

	formatTime : function(time) {
	
		var sec = Math.round(time);
		var min = Math.floor(sec / 60);
		sec = sec - (min * 60);
		sec = (sec < 10) ? '0'+sec : sec;
		
		return min+':'+sec;
	},

// -----------------------------------------------------------------------------
// Increment Download Count

	incDownloadCount : function(id) {
		
		$.get('/admin/index.php', {
				event : 'file',
				step  : 'increment_count',
				id    :  id,
				ext   :  txp.plugins.audioplayer.tracks[id].ext,
				app_mode : 'async'
			},
			function(data) { /* console.log(data); */ }
		);
	}
};
