txp.list.image = {};
txp.edit.image = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.list.image.init = function() {

	console.log('init list image');
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("td.grid .image a").bind("click",function(event){
		
		var chbox = $(this).parent().find("input");
		
		$("td.grid .image a").removeClass('checked');
		
		if (chbox.attr('checked')) {
			chbox.attr('checked',false);
			$(this).removeClass('checked');
		} else {
			chbox.attr('checked',true);
			$(this).addClass('checked');
		}
		
		return false;
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$('form#search').submit(function() {
 		// console.log('submit');
	});

	$('form#search input.clearsearch').click( function() {
		
		$('input#clearsearch').val('1');
		$('form#search').trigger('submit');
	
	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.image.init = function() {

	console.log('init edit image');
	
	var t = txp.edit.image;
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	txp.ext = ['jpeg','gif','png',''];
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// effect filters 
	
	$('.effect-filters a.filter').click( function(event) {
		
		var filter = $(this).attr('href').substring(1);
		
		if (!$(this).parents('ul').hasClass(filter)) {
		 
			t.applyFilter(filter);
		}
		
		return false;
	});
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.image.applyFilter = function(name) {

	$.post("index.php", { 
		event: 		"image", 
		step: 		"effect",
		effect:		name,
		app_mode: 	"async",
		id: 		txp.docid,
		},function(data) {
  		
  		if (data.trim().substr(0,2) == 'OK') {
  			
  			txp.edit.image.updateRegularBox();
  			txp.edit.image.updateThumbnailBox();
  			txp.edit.image.updateCurrentEffect(name);
  		}
	});
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.calcResize = function(type,side,width,height) {

	var new_width  = eval('document.resize_' + type + '.new_width');
	var new_height = eval('document.resize_' + type + '.new_height');
	var resize_by  = eval('document.resize_' + type + '.resizeby');
	
	if (type == 't') {
	
		// maximum thumbnail size is the regualr image size
		var crop   = parseInt(document.resize_t.crop.value);
		var width  = parseInt(document.resize_r.new_width.value);
		var height = parseInt(document.resize_r.new_height.value);
		
		// square thumbnail
		if (crop != 4) {
			if (width <= height)
				height = width;
			else 
				width = height;
		}
	}
	
	if (side == 'width') { 
		
		if (parseInt(new_width.value) > width) { new_width.value = width; }
		if (parseInt(new_width.value) < 10)    { new_width.value = 10; }
		
		new_height.value = Math.round(new_width.value / (width / height));
	
	} else { 
		
		if (parseInt(new_height.value) > height) { new_height.value = height; }
		if (parseInt(new_height.value) < 10)     { new_height.value = 10; }
		
		new_width.value = Math.round(new_height.value * (width / height));
	}
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.selCrop = function(newcrop,width,height) {

	var current    = document.resize_t.crop.value;
	var bywidth    = document.resize_t.bywidth.value;
	var byheight   = document.resize_t.byheight.value;
	var side       = '';
	
	document.resize_t.crop.value = newcrop;
	
	txp.edit.image.border('crop' + current,0);
	txp.edit.image.border('crop' + newcrop,1,'#7F7F7F');
	
	if (!bywidth && !byheight) {
		if (width > height) side = 'width';
		if (width < height) side = 'height';
	}
	
	if (bywidth)  side = 'width';
	if (byheight) side = 'height';
	
	txp.edit.image.calcResize('t',side,0,0);
	
	txp.edit.image.resizeImage('t');
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.border = function(targetID,state,color) {
	
	if (color == null) color = '#FC3';
	
	var current = document.resize_t.crop.value;

	if (document.getElementById) 
	{
		var box = document.getElementById(targetID);
	
		if (state == 1) {
			
			if (targetID != ("crop" + current)) { 
				box.style.border = "1px solid " + color;
			}
		} else {
			if (targetID != ("crop" + current)) { 
				if (box.className == 'crop new')
					box.style.border = "1px dotted #999999";
				else
					box.style.border = "0"; 
			} else {
				box.style.border = "1px solid #7F7F7F";
			}
		}
	}
}

/* --------------------------------------------------------------------------------- */
/*
txp.edit.image.changeCategory = function(id,category) {
	
	var data = {
		event: 	  "image",
		step:	  "category",
		app_mode: "async",
		id: 	  id,
		category: category,
		refresh_content : 1
	};
	
	data.type = 'r';
	$.post("index.php",data,txp.edit.image.updateRegularResizeBox);
	
	data.type = 't';
	$.post("index.php",data,txp.edit.image.updateThumbnailResizeBox);
}

txp.edit.image.updateRegularResizeBox = function(data) {

	$('#regular-resize-box').html(data);
}

txp.edit.image.updateThumbnailResizeBox = function(data) {

	$('#thumbnail-resize-box').html(data);
}
*/
/* --------------------------------------------------------------------------------- */

txp.edit.image.resizeImage = function(type) {

	$.post("index.php", { 
		event: 		"image", 
		step: 		"resize_" + type,
		app_mode: 	"async",
		refresh_content : 1,
		id: 		txp.docid,
		new_width:  document.forms['resize_'+type].new_width.value,
		new_height: document.forms['resize_'+type].new_height.value,
		bywidth:	document.forms['resize_'+type].bywidth.value,
		byheight:	document.forms['resize_'+type].byheight.value,
		crop:		(type == 't') ? document.resize_t.crop.value : ''
		},function(data) {
			
			console.log(data);
			
			if (type == 'r') {
			
				txp.edit.image.updateRegularBox()
			
			} else {
				
				txp.edit.image.updateThumbnailBox();
			}
		});
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.updateRegularBox = function() {

	$.get("index.php", { 
		event: 		"image", 
		step: 		"edit_r",
		app_mode: 	"async",
		id: 		txp.docid,
		},function(data) {
  			
  			$('#regular-image-box').html(data);
	});
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.updateThumbnailBox = function() {

	$.get("index.php", { 
		event: 		"image", 
		step: 		"edit_t",
		app_mode: 	"async",
		id: 		txp.docid,
		},function(data) {
  		
  			$('#thumbnail-image-box').html(data);
	});
}

/* --------------------------------------------------------------------------------- */

txp.edit.image.updateCurrentEffect = function(name) {
	
	$('.effect-filters ul').attr('class','');
	$('.effect-filters ul').addClass(name);
	
	$('.effect-filters ul li').removeClass('current');
 	$('.effect-filters ul li.'+name).addClass('current');
}
