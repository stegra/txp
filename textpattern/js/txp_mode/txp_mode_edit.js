txp.edit = {};

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

txp.edit.init = function() {
	
	console.log('init edit');
	
	if (txp.edit[txp.event]) {
	
		txp.edit[txp.event].init();
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// article image
     
    $("#image-add .header a.add").click( function () {
			
		var width = 550;
		var height = 450;
	
		window.open(this.href,'add-image','width='+width+',height='+height+',scrollbars,resizable');
		
		return false;
    });
    
    // open image in edit window
	
	$("#image-view a.remove").click(function(event) { 
    	
    	event.preventDefault();
		event.stopPropagation();
		
		remove_image_from_article();
		
		return false;
    });
    
   	// open image in edit window
	
	$("#image-view a.edit").click(function(event) { 
    	
    	event.preventDefault();
		event.stopPropagation();
		
		var name = 'window'+Math.round(Math.random()*10000);
		
		window.open(this.href,name,'width=780,height=550,scrollbars,resizable');
		
		return false;
    });
        
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // custom fields
	
	$(".custom-fields input.checkbox").click( function() {
		
		var name = $(this).attr('name');
		var values = [];
		
		$(".custom-fields input.checkbox."+name).each( function() {
			
			if ($(this).attr('checked')) {
				values.push($(this).attr('value'))
			}
		});
		
		values = (values.length) ? values.join(',') : 'NONE';
		
		$(".custom-fields input#"+name).val('[' + values + ']');
	});
	
    $(".custom-fields div.level-1").hover(
    	function () {
    		$(this).addClass('hover');
    	},
    	function () {
    		$(this).removeClass('hover');
    	}
    );
   	
   	$(".custom-fields a.remove").click( function () {
    	
    	var field = $(this).attr('data-id');
    	var article_id = $(this).parents('form').attr('data-article-id');
    	
    	// console.log(article_id,field);
    	
    	$.post("index.php", { 
				event:'article', step:'remove_field', app_mode:'async', win:txp.winid,
				ID:article_id, field:field 
			},
			function(data) {
				
				// console.log(data);
				// id = parseInt(data);
				id = $.trim(data);
				$('.custom-fields #field-'+id).trigger('remove');
			}
		);
		
		return false;
    });
    
  	$(".custom-fields div.level-1").bind('remove',function () {
     	
     	var field = $(this);
		var id = field.attr('id');
		
		field.fadeTo(700,0.0);
		
		setTimeout(function () { 
		
			var height = field.innerHeight();
			field.css('padding-top','0px');
			field.css('padding-bottom','0px');
			field.css('height',height+'px');
			
			var interval = setInterval(function () {
				
				height = height - 1;
				
				if (height < 0) clearInterval(interval);
				
				field.css('height',height+'px');
				
			},5);
			
		},700);
	
	});
 	
 	/* $(".custom-fields p.apply input").click( function () {
    	
    	var checkbox = $(this);
    	var id = checkbox.attr('id');
    	
    	if (id == 'custom-apply-id') {
    		
    		if (checkbox.attr('checked')) {
    			$(".custom-fields p.apply input#custom-apply-class").attr('checked',false);
    			$(".custom-fields p.apply input#custom-apply-category1").attr('checked',false);
    			$(".custom-fields p.apply input#custom-apply-category2").attr('checked',false);
    		}
    	} else {
    		
    		if (checkbox.attr('checked')) {
    			$(".custom-fields p.apply input#custom-apply-id").attr('checked',false);
    		}
    	}
    	
    }); */
   	
   	$(".custom-fields .date").each(function() {
   		
   		$(this).children("input").hide();
   		
   		var input = $(this).children("input").first();
   		var date = input.val();
   		
   		var tpl = $(this).children("script").html();
   		
   		$(this).append(tpl);
   		
   		if (date) {
   			date = date.split('/');
   			$(this).children('select.month').first().val(date[1]);
   		}
   		
   		$(this).children("select").change(function() {
   		
			var date  = $(this).parent();
			var input = date.children("input").first();
			
			var month = date.children('select.month').first().val();
			var day   = date.children('select.day').first().val();
			var year  = date.children('select.year').first().val();
			
			if (year && month && day) {
			
				input.val(year+'/'+month+'/'+day);
			
			} else {
				
				input.val('');
			}
		});
   	
   	});
   	
   	$(".custom-fields .time").each(function() {
   		
   		$(this).children("input").hide();
   		
   		var input = $(this).children("input").first();
   		var time = input.val();
   		
   		var tpl = $(this).children("script").html();
   		
   		$(this).append(tpl);
   		
   		var hour = '';
		var min  = '';
   		var pm   = 'pm';
   		
   		if (time) {
			
			time = time.split(':');
			
			hour  = parseInt(time[0]);
			min   = time[1];
			pm	  = (hour >= 12) ? 'pm' : 'am';
			
			hour  = (hour > 12) ? hour - 12 : hour; 
			hour  = (hour == 0) ? 12 : hour;
			
			$(this).children('select.hour').first().val(hour);
			$(this).children('select.min').first().val(min);
			$(this).children('select.ampm').first().val(pm);
		}
		
		$(this).children("select").change(function() {
   		
			var time  = $(this).parent();
			var input = time.children("input").first();
			
			var hour = time.children('select.hour').first().val();
			var min  = time.children('select.min').first().val();
			var ampm = time.children('select.ampm').first().val();
			
			if (hour && min) {
				
				hour = parseInt(hour);
				min  = parseInt(min);
				
				if (ampm == 'pm' && hour != 12) {
				
					hour = hour + 12;
				
				} else if (ampm == 'am' && hour == 12) {
					
					hour = 0;
				}
				
				hour = (hour < 10 ) ? '0' + hour : hour;
				min  = (min < 10 )  ? '0' + min : min;
				
				input.val(hour + ':' + min);
				
			} else {
				
				input.val('');
			}
		});
   		
   	});
   	
   	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // categories
    
    $('fieldset.categorize select option').each( function () {
    	
    	var option = $(this);
    	
    	if (option.attr('selected')) {
    		
    		option.html(option.html().replace(/&nbsp;/g,''));
    	}
    });
    
    $('fieldset.categorize select').mousedown( function () {
    	
    	$(this).find('option').each( function () {
    	
    		var option = $(this);
    		
    		if (option.attr('selected')) {
    		
    			var level = option.attr('data-level');
    			
    			for (var i = 0; i < level-2; i++) {
    				option.prepend('&nbsp;&nbsp;&nbsp;');
    			}
    		}
    	});
    });
    
    $('fieldset.categorize select').change( function () {
    	
    	$(this).find('option').each( function () {
    	
    		var option = $(this);
    	
    		if (option.attr('selected')) {
    		
    			option.html(option.html().replace(/&nbsp;/g,''));
    		}
    	});
    	
    });

   	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    // save
	
	$('input.publish').click( function () {
		
		setTimeout(function() {
			$("div#processing").show();
		},1000);
		
	});
	
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// article image 

function save_image_setting(item) {

	var image = document.article.image.value;
	
	$.post("index.php", { 
		event: 	  "article", 
		step: 	  "save_image",
		app_mode: "async",
		win:	  txp.winid,
		article:  article,
		image:    image,
		name:	  item.name,
		value:	  item.value
	}, error);
}

function error(data) {

	console.log(data);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// article image 

function remove_image_from_article() {
	
	$.post("index.php", { 
		event: 	  txp.event, 
		step: 	  "remove_image",
		app_mode: "async",
		win:	  txp.winid,
		article:  txp.docid
	}, hide_article_image);
}

function show_article_image(data) {
	
	var response = data.split('###'); 
	var image    = response[0];
	var mini     = response[1];
	var image_id = response[2];
	
	document.article.image.value = image_id;
	
	$(".image #image-view .image").html(image);
	$(".image #image-view .header .mini").html(mini);
	$(".image.add").removeClass('add').addClass('view');
}

function hide_article_image() {
	
	$(".image.view").removeClass('view').addClass('add');
	
	var id = $("input#article-image-id").val();
	
	$("input#article-image-id").val(-id);
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
// article file 

function add_file_to_article(file) {
	
	$.post("index.php", { 
		event: 	  "article", 
		step: 	  "add_file",
		app_mode: "async",
		win:	  txp.winid,
		article:  article,
		file:     file
	}, show_article_file);
}

function remove_file_from_article() {
	
	$.post("index.php", { 
		event: 	  txp.event, 
		step: 	  "remove_file",
		app_mode: "async",
		win:	  txp.winid,
		article:  article
	}, hide_article_file);
}

function show_article_file(data) {
	
	var response  = data.split('###');
	var filename  = response[0];
	var extension = response[1];
	var insert	  = response[2];
	
	document.article.file.value = insert;
	
	$(".file #file-view .filename").html(filename);
	$(".file #file-view .ext").html(extension);
	$(".file.add").removeClass('add').addClass('view');
}

function hide_article_file() {
	
	$(".file.view").removeClass('view').addClass('add');
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function dateSelect(name) {

	year  = name + '-year';
	month = name + '-month';
	day   = name + '-day';
	
	date  = document.article[name];
	year  = document.article[year].value;
	month = document.article[month].value;
	day   = document.article[day].value;
	
	if (year && month && day) 
		date.value = year + '/' + month + '/' + day;
	else
		date.value = '';
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

function timeSelect(name) {

	hour = name + '-hour';
	min  = name + '-min';
	pm   = name + '-pm';
	
	time = document.article[name];
	hour = document.article[hour].value;
	min  = document.article[min].value;
	pm   = document.article[pm].value;
	
	if (hour != '-') {
		
		hour = parseInt(hour);
		pm   = parseInt(pm);
	
		if (hour == 12) { 
			if (pm != 1) hour = '00';
		} else {
			if (pm == 1) hour = hour + 12;
			else { 
				if (hour < 10) hour = '0' + hour;
			}
		}	
	
		if (min != '-') {
		 	if (parseInt(min) == 0) min = '00';
		} else {
			min = '00';
		}
	
		time.value = hour + ':' + min;
	
	} else {
	
		time.value = '';
	}
}

