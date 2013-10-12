$(document).ready(function() {
	
	$('li.group a.group').click(function() {
		
		var group = $(this).parent();
		
		if (group.hasClass('closed')) {
			group.removeClass('closed');
		} else {
			group.addClass('closed');
		}
		
		return false;
	});

});