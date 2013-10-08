var $ = jQuery;

$(document).ready(function(){	

	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("#inspector .block .h2").click( function(){
		
		var block = $(this).parent();
		var id = block.attr('id').split('-').pop();
		
		if (block.hasClass('closed')) {
			
			block.removeClass('closed');
			
			// console.log('open',id);
			
			inspector.push(id);
		
		} else {
		
			block.addClass('closed');
			
			// console.log('close',id);
			
			inspector = unset(inspector,id);
		}
		
		$.cookie("inspector", inspector.join(','));
		
		// console.log(inspector);
		
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	// $.cookie("inspector", null);
	
	var inspector = $.cookie("inspector");
	
	if (inspector == null) {
		
		inspector = "0";
		
		$.cookie("inspector", inspector);
	}
	
	inspector = inspector.split(',');
	
	for (var i in inspector) {
		
		var id = inspector[i]; 
			
		$("#inspector #block-"+id).removeClass('closed');
	}

});


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function in_array(array,value) {

	for (var i in array) {
	
		if (array[i] == value) return true;
	}
	
	return false;
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function unset(array, valueToUnset, valueOrIndex, isHash) {

	var output = new Array(0);
	
	for (var i in array) {
		
		if (!valueOrIndex) { //search value
			
			if (array[i]==valueToUnset) {continue};
			
			if (!isHash) {
				output[++output.length-1]=array[i];
			} else {
				output[i]=array[i];
			}
	  	
	  	} else { //search index (or key)
		
			if (i==valueToUnset) {continue};
			
			if (!isHash) {
				output[++output.length-1]=array[i];
			} else {
				output[i]=array[i];
			}
	  	}
	}
	
	return output;
}
