var MyObject = function() {

	this.obj = {};
	this.length = 0;
	
	this.max_numeric_key = 0;
	
	this.keys = new MyArray();
	
	this.reserved = new MyArray(
		'obj',
		'length',
		'max_numeric_key',
		'keys',
		'reserved',
		'getArgs',
		'push',
		'pop',
		'each',
		'join',
		'has',
		'unset'
	);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.getArgs = function getArgs(arguments,offset) {
	
		var args = [];
		var offset = offset || 0;
				
		for (var i = offset, len = arguments.length; i < len; ++i) {
		
			args.push(arguments[i]);
		}
	
		return args;
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	var args = this.getArgs(arguments);
	
	for (i in args) {
		
		this.max_numeric_key++;
		
		this.obj[this.max_numeric_key] = args[i];
		
		this[this.max_numeric_key] = args[i];
		
		this.length++;
		this.keys.push(this.max_numeric_key);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.push = function(value,name) {
	
		var name = name || ++this.max_numeric_key;
		
		if (this.reserved.has(name)) 
			return this;
		
		this.obj[name] = value;
		this[name] = value;
		
		this.length++;
		this.keys.push(name);
		
		return this;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.pop = function() {
		
		if (this.length) {
			
			var top = this.keys.pop();
			var item = this.obj[top];
			
			delete this.obj[top];
			delete this[top];
			
			this.length--;
			
			return item;
		}
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.each = function(action) {
	
		var obj = this.obj;
		
		for (i in obj) {
		
			action(obj[i], i);
		}
		
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.unset = function(value,isKey) {
	
		var isKey = isKey || 0;
		
		if (value == undefined) {
			
			this.keys.each( function(key) {
			
				delete this[key];
			});
			
			this.length = 0;
			this.obj = {};
			this.keys = new MyArray();
			this.max_numeric_key = 0;
			
			return this;
		}
		
		if (isKey) {
			
			if (this.keys.has(value)) {
			
				delete this.obj[value];
				delete this[value];
			
				this.keys.unset(value);
				this.length--;
			}
		
		} else {
			
			for (i in this.obj) {
				
				if (this.obj[i] == value) {
					
					delete this.obj[i];
					delete this[i];
					
					this.keys.unset(i,1);
					this.length--;
				}
			}
		}
		
		return this;
	};
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	this.join = function(str) {
	
		var arr = [];
		var obj = this.obj;
		
		for (i in obj) {
		
			arr.push(obj[i]);
		}
		
		return arr.join(str);
	}

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	this.has = function(value,name) {
	
		var obj = this.obj;
		
		for (i in obj) {
			
			var item = obj[i];
			
			if (typeof item == 'object') {
			
				if (name && item[name] == value) return i;
				
			} else {
				
				if (item == value) return i;
			}	
		}
		
		return false;
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
/*
var testme = new MyObject('CDC','KKK');

testme.push('ABC');
testme.push('DEF','city');
testme.push('XYZ');

console.log(testme.join());
console.log(testme);
testme.unset('city',1);
console.log(testme.join());
*/
