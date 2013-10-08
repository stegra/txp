(function($){
	
	var initLayout = function() {
		
		/* $('input.color').ColorPicker({
			onSubmit: function(hsb, hex, rgb, el) {
				console.log('submit',hex);
				$(el).val(hex);
				$(el).ColorPickerHide();
			},
			onBeforeShow: function () {
				console.log('input',this.value);
				$(this).ColorPickerSetColor(this.value);
			}
		}); */
		
		$('#colorSelector').ColorPicker({
			color: $('#colorSelector input').val(),
			onShow: function (colpkr) {
				$(colpkr).fadeIn(500);
				return false;
			},
			onHide: function (colpkr) {
				$(colpkr).fadeOut(500);
				return false;
			},
			onChange: function (hsb, hex, rgb) {
				$('#colorSelector input').val(hex);
				$('#colorSelector div').css('backgroundColor', '#' + hex);
			}
		});
		
	};
	
	EYE.register(initLayout, 'init');
	
})(jQuery)