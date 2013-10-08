txp.list.prefs = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.prefs.init = function() {

	console.log('init list prefs');
	
	var options_field = new Array('select','checkbox','radio');
	var default_field = new Array('textfield','textarea','select','checkbox','radio');
	
	$('tr#input select').change(function() {
		
		if (this.value == 'none') {
		
			$('tr#options').addClass('hidden');
			$('tr#default').addClass('hidden');
		
		} else {
			
			if (in_array(options_field,this.value)) {
				$('tr#options').removeClass('hidden');
			} else {
				$('tr#options').addClass('hidden');
			}
			
			if (in_array(default_field,this.value)) {
				$('tr#default').removeClass('hidden');
			} else {
				$('tr#default').addClass('hidden');
			}
		}
	});
	
	$('tr.field-header td.title a').click(function() {
		txp.toggleDisplay($(this).attr('rel'));
	});
}