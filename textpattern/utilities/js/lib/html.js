var html = {

	td : function(content,myclass,attr) {
	
		var myclass = myclass || '';
		var attr = attr || {};
		attr.myclass = myclass;
		
		return this.tag('td',content,attr);
	},

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	tr : function(content,myclass,attr) {
		
		if (content == '') return '';
		
		var myclass = myclass || '';
		var attr = attr || {};
		attr.myclass = myclass;
		
		return this.tag('tr',content,attr);
	},
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	a : function(href,content,myclass,attr) {
		
		if (href == '') return '';
		
		var content = content || href;
		var myclass = myclass || '';
		var attr = attr || {};
		
		attr.href = href;
		attr.myclass = myclass;
		
		return this.tag('a',content,attr);
	},
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	br : function() {
		
		return this.tag('br');
	},
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	tag : function(name,content,attr) {
	
		var attr = attr || {};
		var attributes = [];
		
		for (var i in attr) {
		
			if (attr[i]) attributes.push(' ' + i.replace(/^my/,'') + '="' + attr[i] + '"');
		}
		
		if (content != undefined) 
			return '<' + name + attributes.join('') + '>' + content + '</' + name +'>';
			
		return '<' + name + attributes.join('') + '/>';
		
	},
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	format_bytes : function(bytes) {
	
		if (bytes >= 1000000000)	return this.decimal_pad(Math.round((bytes / 1073741824) * 100) / 100) + ' <span>GB</span>';
		if (bytes >= 10000000) 		return (Math.round(bytes / 1048576)) + ' <span>MB</span>';
		if (bytes >= 1000000)		return this.decimal_pad(Math.round((bytes / 1048576) * 100) / 100) + ' <span>MB</span>';
		if (bytes >= 1000)			return (Math.round(bytes / 1024)) + ' <span>KB</span>';
		// if (bytes >= 1)			return this.decimal_pad(Math.round((bytes / 1024) * 100) / 100) + ' <span>KB</span>';
		
		return bytes + ' <span>B</span>';
	},
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	decimal_pad : function(num,size) {
		
		var size = size || 2;
		
		num = num.toString().split('.');
		
		if (num.length == 2) {
		
			for (var i = 1; i < size; i++) {
				num[1] += (i >= num[1].length) ? "0" : "";
			}
		
		} else {
			
			num.push('0');
			
			for (var i = 1; i < size; i++) {
				num[1] += '0';
			}	
		}
		
		return num.join('.');
	}

}

