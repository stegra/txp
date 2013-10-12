// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

var myInterval = function(callback, factor, times, min, max) { 
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// initialise
	
	this.callback = {
		
		func : callback, 
		args : []
	};
	
	this.settings = {
		
		factor	 : factor || 1, 
		times	 : times  || 10, 
		min		 : min	  || 1, 
		max		 : max	  || 10
	};
	
	this.mystatus = { 
		
		times	: this.settings.times,
		min		: this.settings.min,
		stopped : true,
		started : false
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// private methods
	
	this.go = function() {
		
		var callback = this.callback;
		var sett 	 = this.settings;
		var status   = this.mystatus;
		
		status.stopped = false;
		status.started = true;
		
		var internalCallback = function( t, counter, status ) {
			
			return function() { 
				
				var time = ((status.min + counter * sett.factor) < sett.max) 
						? status.min + (++counter * sett.factor) 
						: sett.max;
						
				if ( --t >= 0 && !status.stopped) { 
				
					// console.log(time);
					
					window.setTimeout( internalCallback, time);
					
					callback.func.apply( this, callback.args );
				
				} else {
					
					status.times 	= t;
					status.stopped 	= true;
					status.min 		= time - sett.factor;
				}
			}
			
		}( status.times, 0 , status );
		
		window.setTimeout( internalCallback, status.min );
	
	};
	
	this.getArgs = function getArgs(arguments,offset) {
	
		var args = [];
		var offset = offset || 0;
				
		for (var i = offset, len = arguments.length; i < len; ++i) {
		
			args.push(arguments[i]);
		}
	
		return args;
	};
	
	this.callback.args = this.getArgs(arguments,5);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// public methods
	
	this.start = function(callback, factor, times, min, max) {
		
		if (this.mystatus.stopped) {
			
			if (!this.mystatus.started) {
			
				if (arguments.length) {
					
					this.callback.func 		= callback;
					this.callback.args 		= this.getArgs(arguments,5);
					
					this.settings.factor 	= factor;
					this.settings.times 	= times;
					this.settings.min 		= min;
					this.settings.max 		= max;
					
					this.mystatus.times 	= times;
				}
			
				this.go();
			
			} else {
				
				this.cont();
			}
		}
	};
	
	this.stop = function(delay) {
		
		var delay = delay || 0;
		
		window.setTimeout( function(status) {
			
			status.stopped = true;
			
		}, delay, this.mystatus);
	
	};
	
	this.cont = function() {
		
		if (this.mystatus.stopped) {
			
			this.go();
		}
	};
	
	this.restart = function() {
		
		this.mystatus.times = this.settings.times;
		this.mystatus.min   = this.settings.min;
		
		if (this.mystatus.stopped && this.mystatus.started) {
			
			this.go();
		}
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// start anonymously invoked object
	
	if (this.parent != undefined) {
	
		this.go();
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	return this;
	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -