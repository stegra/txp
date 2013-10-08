Array.prototype.last = function(value) {
	
	if (this.length) {
		
		var last = this[this.length-1];
		
		if (value != undefined)
			return (value == last) ? true : false;
		else	
			return last;
	}
	
	return '';

};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

Array.prototype.each = function(action) {

    for (var i = 0, l = this.length; i < l; ++i) {
    
        action(this[i], i);
	}
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

Array.prototype.has = function(value) {

	for (var i = 0, l = this.length; i < l; ++i) {
	
		if (this[i] == value) return true;
	}
	
	return false;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

Array.prototype.unset = function(value,isIndex) {

	var isIndex = isIndex || 0;
	
	if (value == undefined) {
		
		this.length = 0;
		
		return this;
	}
	
	for (var i = 0, j = 0, l = this.length; i < l; ++i) {
	
		if (!isIndex) {
			
			if (this[i] != value) { 
			
				this[j++] = this[i];
			}
		
		} else {
			
			if (i != value) {
				
				this[j++] = this[i];
			}
		}
	}
	
	this.length = j;
	
	return this;
};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/*
var myarray = [1,2,3,4,6,3,4,2,1];

myarray.forEach( function(val) { console.log(val) } );

console.log(myarray.has(2));

myarray.unset(3).push(10);

console.log(myarray,myarray.length);

*/