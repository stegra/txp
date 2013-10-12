// *****************************************************************************
// Objects

Object.prototype.join = function(str){
	
	var arr = [];
	var obj = this;
	
	for (i in obj) {
		if (i != 'join') arr.push(obj[i]);
	}
	
	return arr.join(str);
}

// *****************************************************************************
// Arrays

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

	var args = getArgs(1,arguments);
	var arr = this;
	
	 for (var i = 0, l = arr.length; i < l; ++i) {
    	
        action.apply(this,[arr[i],i].concat(args));
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

// *****************************************************************************

function getArgs(start,arguments) {

	var args = [];
			
	for (var i = start, len = arguments.length; i < len; ++i) {
	
		args.push(arguments[i]);
	}

	return args;
};
	
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// Test
/*
var myarray = [1,2,3,4,6,3,4,2,1];

myarray.each( function(val,i,add1,add2) { console.log(val,i,add1,add2) }, 'abc', 'xyz' );

console.log(myarray.has(2));

myarray.unset(3).push(10);

console.log(myarray,myarray.length);
*/