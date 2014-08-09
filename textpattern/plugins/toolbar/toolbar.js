$(document).ready(function() {
		
	var editmode    = $.cookie('txp_sitemode_edit');
	var previewmode = $.cookie('txp_sitemode_preview');
	
	// console.log('edit mode',editmode);
	// console.log('preview mode',previewmode);
	
	if (editmode == 'on') {
		$('.edit a.on').addClass('selected');	
	} else {
		$('.edit a.off').addClass('selected');	
	}
	
	if (previewmode == 'on') {
		$('.preview a.on').addClass('selected');	
	} else {
		$('.preview a.off').addClass('selected');	
	}
	
	$('a').click(function() {
		
		$.cookie('txp_sitemode_toolbar','on', { expires: 7, path: '/' });
	});
	
	$('.edit a').click(function() {
		
		$.cookie('txp_sitemode_edit', $(this).attr('rel'), { expires: 7, path: '/' });
		
		parent.location.reload();
		
		return false;
	});
	
	$('.preview a').click(function() {
		
		$.cookie('txp_sitemode_preview', $(this).attr('rel'), { expires: 7, path: '/' });
		
		parent.location.reload();
		
		return false;
	});
	
});