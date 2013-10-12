var MyArray = function() { 
	
	this.arr = [];
	this.length = this.arr.length
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.getArgs = function(arguments,offset) {
	
		var args = [];
		var offset = offset || 0;
				
		for (var i = offset, len = arguments.length; i < len; ++i) {
		
			args.push(arguments[i]);
		}
	
		return args;
	};
	
	this.isArray = function(obj) {
   
		if (obj.constructor.toString().indexOf("Array") == -1)
			return false;
		else
			return true;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// initialize array
	
	var args = this.getArgs(arguments);
	
	if (args.length == 1 && this.isArray(args[0])) {
		
		this.arr = args[0];
	
	} else {
		
		this.arr = this.getArgs(arguments);
	}
	
	this.length = this.arr.length;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.push = function(item) {
		
		this.arr.push(item);
		this.length = this.arr.length;
		
		return this;
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.join = function(string1,string2) {
		
		var string1 = string1 || ',';
		var string2 = string2 || ':';
		var temp = [];
		
		if (this.length) {
			
			for (i in this.arr) {
				
				if (typeof this.arr[i] != 'object') {
					
					temp.push(this.arr[i]);
				
				} else {
					
					var obj = this.arr[i];
					var arr = [];
					
					for (j in obj) {
						arr.push(obj[j]);
					}
					
					temp.push(arr.join(string2));
				}
			}
			
			return temp.join(string1);
		}
		
		return '';
		
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.shift = function() {
		
		if (this.length) {
			
			var item = this.arr.shift();
			this.length = this.arr.length;
			
			return item;
		}
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	this.has = function(value,name) {
	
		for (var i = 0, l = this.length; i < l; ++i) {
		
			var item = this.arr[i];
			
			if (typeof item == 'object') {
			
				if (name && item[name] == value) return i+1;
				
			} else {
				
				if (item == value) return i+1;
			}
		}
		
		return 0;
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	this.each = function(action) {
	
		for (var i = 0, l = this.length; i < l; ++i) {
		
			action(this.arr[i], i+1);
		}
	};

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.last = function(value) {
		
		if (this.length) {
			
			var last = this.arr[this.length-1];
			
			if (value != undefined)
				return (value == last) ? true : false;
			else	
				return last;
		}
		
		return '';
		
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	this.unset = function(value,isIndex) {
	
		var isIndex = isIndex || 0;
		
		if (value == undefined) {
			
			this.length = 0;
			this.arr.length = 0;
			
			return this;
		}
		
		for (var i = 0, j = 0, l = this.length; i < l; ++i) {
		
			if (!isIndex) {
				
				if (this.arr[i] != value) { 
				
					this.arr[j++] = this.arr[i];
				}
			
			} else {
				
				if (i != value) {
					
					this.arr[j++] = this.arr[i];
				}
			}
		}
		
		this.length = j;
		this.arr.length = j;
		
		return this;
	};

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.sort = function() {

		var dir = dir || 'asc';
		
		if (dir == 'asc') 
			this.arr.sort( function (a, b) { return (a > b) - (a < b); } );
		
		if (dir == 'desc') 
			this.arr.sort( function (a, b) { return (a < b) - (a > b); } );
		
		return this;
	};

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.sortby = function(name,dir) {

		var sort_name = [];
		var temp = []
		var sorted   = [];
		var dir = dir || 'asc';
		
		if (!name) return this;
		
		for (k in this.arr) { 
		
			if (typeof this.arr[k] != 'function') {
			
				if (typeof this.arr[k] == 'object') {
					
					sort_name.push(this.arr[k][name]);
					temp.push({k:k,val:this.arr[k]});
				}
			}
		}
		
		if (dir == 'asc') 
			sort_name.sort( function (a, b) { return (a > b) - (a < b); } );
		
		if (dir == 'desc') 
			sort_name.sort( function (a, b) { return (a < b) - (a > b); } );
			
		for (i in sort_name) { 
		
			var val = sort_name[i];
			
			if (typeof val != 'function') {
			
				for (j in temp) {
				
					var t = temp[j];
					
					if (typeof t != 'function') {
					
						if (t.val[name] == val) {
							this.arr[i] = t.val;
						}
					}
				}
			}
		}
		
		return this;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	return this;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/*
var myarray = new MyArray(1,2,3,4,6,3,4,2,1);

myarray.each( function(val) { console.log(val) } );

console.log(myarray.has(2));

myarray.unset(3).push(10);

console.log(myarray,myarray.length);

*/
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/*
var testing = new MyArray('CDC','KKK');

testing.push('XYZ');
testing.push('ABC');
testing.push('DEF');
testing.each( function(val) { console.log(val) } );
testing.sort();
console.log(testing.join());
*/
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
