txp.plugins.filedrop = {

	events 			: {},
	drops 			: [],
	uploads 		: [],
	errors 			: 0,
	max_file_size 	: 0,
	refresh 		: null,
	xhr 			: null,
	insert_mode 	: 'new',
	
	progress 		: {
		elem  : null,
		bar	  : null,
		html  : '',
		count : 0,
		prev  : 0,
		size  : 0
	},

// -----------------------------------------------------------------------------

	init : function() {
	
		console.log('init filedrop');
		
		var t = txp.plugins.filedrop;
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		t.max_file_size = $("form.upload input[name='MAX_FILE_SIZE']").val();
		t.progress.elem = $('#progress');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		$('form.upload').hide();
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// drop zones 
		
		t.events.add_image = ['article','list','file','image','link','category','discuss','custom','sites','admin'];
		
		t.drops.push(document.getElementById('content'));
		
		if (txp.event == 'image' && txp.step == 'edit') {
			
			t.drops.push({id:'thumbnail-image',style:$('#thumbnail-image')});
		
		} else if (txp.event == 'article') {
			
			t.drops.push({id:'article-image',style:$('#article-image').parent()});
			
		} else if (txp.step == 'list') {
			
			if (txp.view == 'tr') {
				$('td.thumb div.image img').each(function() {
					var id = $(this).attr('id');
					t.drops.push({id:id,style:$('#'+id).parents('td.thumb div.image')});
				});
			}
			
			if (txp.view == 'div') {
				$('div.data td span.border').each(function() {
					var id = $(this).attr('id');
					t.drops.push({id:id,style:$('#'+id).parents('td.grid div.data')});
				});
			}
		}
		
		// console.log(t.drops);
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		
		t.progress.html = t.progress.elem.html();
		t.progress.elem.html('');
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		$('form.upload input:file').change(function(){
			
			file = this.files[0];
			name = file.name;
			size = file.size;
			type = file.type;
		});
	
		$('form.upload').submit(function(){
			
			t.uploads = t.getUploads([file]);
			
			t.showProgress();
			
			uploadFile(t.uploads[0]);
			
			return false;
		});
		
		$('div#content').droppable();
		
		t.progress.elem.draggable();
		
		t.progress.elem.children('a.close').click(function() {
			
			t.uploads.length = 0;
			t.hideProgress();
		});
		
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
		if (t.drops[0]) {
			t.addEvent(t.drops[0], 'dragover', t.dragoverHandler);
			t.addEvent(t.drops[0], 'dragenter', t.cancel);
			t.addEvent(t.drops[0], 'dragleave', t.cancel);
			t.addEvent(t.drops[0], 'drop', t.dropHandler);
		}
	},

// -----------------------------------------------------------------------------
	
	dropHandler : function(e) {
	
		var t = txp.plugins.filedrop;
		
		if (e.preventDefault) e.preventDefault();
		
		var files  = e.dataTransfer.files;
		var text   = e.dataTransfer.getData('Text');
		
		t.errors = 0;
		
		// console.log(files,'target',e.target.id); return false;
		
		if (files.length) {
			
			t.uploads = t.getUploads(files); 
			
			t.showProgress(e.clientX,e.clientY);
			t.insertItem(t.uploads[0],e.target.id);
			
		} else if (text) {
			
			if (text.match(/^http\:\/\//)) {
				
				var url = text.split('?').shift();
				var ext = url.toLowerCase().split('.').pop();
				
				if (ext.match(/^(jpg|png|gif|jpeg)$/)) {
					
					// console.log('image:',text);
					
					t.uploads = t.getUploads([{url:url,ext:ext}]); 
					
					t.showProgress(e.clientX,e.clientY);
					t.insertItem(t.uploads[0],e.target.id);
				
				} else {
					
					// console.log('link:',text);
				}
				
			} else {
			
				// console.log('text:',text);
				
				// t.insertArticle(text);
			}	
			
		} else {
			
			var src_winid = e.dataTransfer.getData('win');	
			var src_type  = e.dataTransfer.getData('type');	
			var src_id    = e.dataTransfer.getData('id');	
			
			if (txp.winid != parseInt(src_winid)) {
				
				if (src_type == 'image') {
				
					if ($.inArray(t.events.add_image,txp.event)) {
						t.addImage(src_id);
					}
				}
			}
		}
	},

// -----------------------------------------------------------------------------

	cancel : function(e) {

		// required by FF + Safari
		if (e.preventDefault) e.preventDefault();
		
		// tells the browser what drop effect is allowed here
		//  e.dataTransfer.dropEffect = 'copy'; 
		
		return false; // required by IE
	},

// -----------------------------------------------------------------------------

	dragoverHandler : function(e) {
	
		var t = txp.plugins.filedrop;
		
		// required by FF + Safari
		if (e.preventDefault) e.preventDefault();
	
		var id = e.target.id;
		
		for (var i = 1; i < t.drops.length; i++) {
			
			t.drops[i].style.removeClass('dragover');
			
			if (t.drops[i].id == id) {
				t.drops[i].style.addClass('dragover');
			}
		}
		
		return false; // required by IE
	},

// -----------------------------------------------------------------------------

	getUploads : function(files){
		
		var t = txp.plugins.filedrop;
		
		var progress_size = 0;
		
		for (var i = 0; i < files.length; i++) {
			
			var id = Math.ceil(Math.random() * 1000000);
			var ext = '';
			var error = '';
			
			if (files[i].type) {
			
				ext = files[i].type.split('/').pop();
			
			} else if (files[i].ext) {
				
				ext = files[i].ext;
						
			} else if (files[i].name) {
				
				var name = files[i].name.split('.');
				
				if (name.length == 2) { 
					ext = name.pop();
				}
			}
			
			if (ext == 'jpg') ext = 'jpeg';
				
			if (ext && txp.ext.length && $.inArray(ext, txp.ext) == -1) {
				error = "Only JPEG, GIF or PNG files allowed.";
			} 
			
			if (files[i].size && files[i].size > t.max_file_size) {
				var size = t.max_file_size / 1000000;
				error = "File size exceeds maximum of " + size + "MB.";
			}
			
			files[i].id = id;
			files[i].ext = ext;
			files[i].progress = progress_size;
			files[i].error = error;
			files[i].is_image = ($.inArray(ext,['jpeg','png','gif']) >= 0);
			
			if (files[i].url) {
				
				files[i].name = files[i].url.split('/').pop();
				
			} else {
			
				files[i].url = '';
			}
			
			t.uploads.push(files[i]);
			
			progress_size = progress_size + files[i].size;
		}
		
		t.progress.size = progress_size;
		
		return t.uploads;
	},

// -----------------------------------------------------------------------------

	hideProgress : function() {
		
		var t = txp.plugins.filedrop;
		
		setTimeout(function() {
			t.progress.elem.fadeOut();
			t.progress.elem.html(t.progress.html);
		},1500);
	},

// -----------------------------------------------------------------------------

	showProgress : function(x,y) {
	
		var t = txp.plugins.filedrop;
		
		t.progress.elem.html(t.progress.html);
		t.progress.bar = t.progress.elem.find("#progress-bar span");
		
		var progress_width = t.progress.elem.outerWidth();
		var list = t.progress.elem.children("ul");
		
		list.html('');
		
		var top = 200;
		var left = Math.round((window.outerWidth / 2) - (progress_width / 2));
		
		if (x) {
		 
			top  = y - 20;
			left = x - Math.round(progress_width / 2);
		
			if ((left + progress_width) > window.outerWidth) {
				left = window.outerWidth - progress_width - 40;
			}
			
			if (left < (progress_width / 2)) {
				left = 20;
			}
		}
		
		t.progress.bar.css('width','0%');
		
		if (t.progress.elem.css('display') == 'none') {
			t.progress.elem.css('top',top).css('left',left).show();
		}
		
		for (var i = 0; i < t.uploads.length; i++) {
			
			var id   = t.uploads[i].id;
			var name = t.uploads[i].name;
			var ext  = '';
			
			if (t.uploads[i].ext) {
				name = name.split('.');
				ext  = '.' + name.pop();
				name = name.join('.');
			}
			
			if (name.length > 30) {
				name = name.substring(0,29) + '...';
			}
			
			var item = $('<li>').attr('id',id).append(name + ext);
			
			list.append(item);
		}
		
		$('#progress a.close').click(function() {
			
			t.xhr.abort();
			t.hideProgress();
			
			return false;
		});
	},

// -----------------------------------------------------------------------------

	insertItem : function(file,target) {
	
		var t = txp.plugins.filedrop;
		
		var formData = new FormData();
		var callback = t.refreshContent;
		var event 	 = txp.event;
		var step 	 = 'insert';
		
		if (file.error) {
		
			return t.uploadComplete(file.error);
		}
		
		if (file.ext) {
				
			// image or other kind of file 
			
			event = txp.event;
			
			if (txp.event != 'file' && txp.event != 'image') {
			
				event = ($.inArray(file.ext,['jpeg','png','gif']) >= 0)
					? 'image' : 'file';
			}
		}
		
		if (!file.url && file.ext) {
			
			// get file from the users computer 
			
			formData.append('thefile', file);
			formData.append('MAX_FILE_SIZE', t.max_file_size);
		}
		
		if (file.url) {
			
			// get file from a URL
			
			formData.append('url',file.url);
		}
		
		if (!file.url && !file.ext) {
		
			// no url and file without extension is a folder
			
			step = 'add_folder';
			
			formData.append('title', file.name);
				
		} else {
			
			// replacement images 
			
			if (txp.event == 'image' && txp.step == 'edit') {
				
				// replace image on image edit page 
				
				t.insert_mode = 'replace';
				
				// replace the existing image  
				
				formData.append('file_id',txp.docid);
				
				if (target == 'thumbnail-image') {
				
					// replace the existing thumbnail image only 
					
					step = 'replace_thumbnail';
					
					callback = txp.edit.image.updateThumbnailBox;
				}
			
			} else if (txp.event == 'article') {
			
				// replace image from article edit page 
				
				if (target == 'article-image') {
					
					t.insert_mode = 'replace';
					
					var id = $('input#article-image-id').val();
					
					formData.append('file_id',id);
				}
				
			} else if (txp.step == 'list') {
				
				// replace image from list page 
				
				if (target.match(/^article\-image\-\d+$/)) {
					
					t.insert_mode = 'replace';
					
					var id = target.split('-').pop();
					
					formData.append('file_id',id);
				}
			}
		}
		
		formData.append('from_event',txp.event);
		formData.append('from_step',txp.step);
		formData.append('from_id',txp.docid);
		formData.append('event',event);
		formData.append('step',step);
		formData.append('win', txp.winid);
		formData.append('app_mode', 'async');
		
		if (txp.checked.length) {
			formData.append('parent',txp.checked[0]);
		}
		
		t.postItem(file,formData,callback);
	},

// -----------------------------------------------------------------------------

	insertArticle : function(title) {
	
		$.post("index.php", { 
			event    : txp.event, 
			step     : 'post', 
			win      : txp.winid,
			app_mode : 'async',
			parent   : txp.docid,
			title	 : title},
			function(data){
				txp.plugins.filedrop.refreshContent();
			});
	},

// -----------------------------------------------------------------------------

	addImage : function(id) {
	
		var href = [];
		
		href.push("event="+txp.event);
		
		// - - - - - - - - - - - - - - - - - - - - - - -
		
		if (txp.step == 'edit') {
			
			href.push("step=add_image");
			href.push("ID=" + txp.docid);
			href.push("ImageID=" + id);
		
		} else {
			
			href.push("step=multi_edit");
			href.push("edit_method=add_image");
			href.push("scroll=" + txp.get_scroll_point());
			href.push("image=" + id);
			
			if (txp.checked.length) href.push("selected=" + txp.checked.join(','));
			if (txp.col) 			href.push("selcol=" + txp.col);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - -
		
		if (txp.event == 'image') {
			
			if (txp.checked.length == 0) {
				txp.checked.push(txp.docid);
			}
			
			href.push("edit_method=move");
			href.push("checked=" + id + ',' + txp.checked[0]);
		}
		
		// - - - - - - - - - - - - - - - - - - - - - - -
		
		if (txp.winid) href.push("win=" + txp.winid);
		
		if (txp.step == 'edit') {
		
			href.push("app_mode=async");
			
			$.get("index.php",href.join('&'),function(data){
				
				if ($.trim(data).match(/^OK\d+$/)) {
					
					txp.plugins.filedrop.refreshContent();
				};
				
			});
		
		} else {
			
			// console.log(href);
			document.location.href = "?" + href.join('&');
		}
	},

// -----------------------------------------------------------------------------

	showArticleImage : function() {
	
		$.get("index.php", { 
			event      : txp.event, 
			step	   : 'show_image',
			article_id : txp.docid,
			win        : txp.winid,
			app_mode   : 'async'},
			function(data){
				$('div.image div#image-view div.image').html(data);
				$('div.image').removeClass('add');
				$('div.image').addClass('view');
			});
	},

// -----------------------------------------------------------------------------

	postItem : function(file,data,callback) {
	
		var t = txp.plugins.filedrop;
		
		var timelimit = 6000;
		var stalled   = 0;
		var loaded    = false;
		
		var progloop = setInterval(function() {
			
			if (t.progress.count > t.progress.prev) {
			
				t.progress.prev = t.progress.count;
			
			} else if (!loaded && stalled >= 10) {
				
				t.progress.prev = 0;
				t.progress.count = 0;
				
				clearInterval(progloop);
				t.xhr.abort();
				
				return t.uploadComplete('Timed out. Try again!');
			
			} else { 
				
				stalled += 1;
			}
		
		},500);
		
		t.xhr = new XMLHttpRequest();
		
		t.xhr.open('POST', 'index.php', true);
		
		t.xhr.setRequestHeader("Cache-Control", "no-cache");
		t.xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
		
		if (file.name && file.size) {
		
			t.xhr.setRequestHeader("X-File-Name", file.name);
			t.xhr.setRequestHeader("X-File-Size", file.size);
		}
		
		t.xhr.onload = function(e) { 
			
			stalled = 0;
			loaded  = true;
			
			clearInterval(progloop);
			
			if (this.status == 200) {
				t.uploadComplete(this.responseText,callback);
			} else {
				t.errorHandler(e);
			}
			
			// console.log(this.responseText);
		};
		
		t.xhr.upload.addEventListener('loadstart',t.beforeSendHandler, false);
		t.xhr.upload.addEventListener('progress',t.progressHandler, false);
		t.xhr.upload.addEventListener('error',t.errorHandler, false);
		
		t.xhr.send(data); 	
	},

// -----------------------------------------------------------------------------

	beforeSendHandler : function(e) {
		
		// console.log('start',txp.plugins.filedrop.uploads[0].name);
	},
	
	completeHandler : function(e) {
		
		// console.log('complete');
	},

	errorHandler : function(e) {
	
		// console.log('error');
	},

// -----------------------------------------------------------------------------

	progressHandler : function(e) {
	
		var t = txp.plugins.filedrop;
		
		if (e.lengthComputable) {
			
			var loaded = t.uploads[0].progress + e.loaded;
			t.progress.count = Math.round((loaded / t.progress.size) * 100);
			
			if (t.progress.count > 100) t.progress.count = 100;
			
			t.progress.bar.css('width',t.progress.count+'%');
		}
	},

// -----------------------------------------------------------------------------

	uploadComplete : function(response,callback) {
	
		var t = txp.plugins.filedrop;
		
		var upload_id = t.uploads[0].id;
		var is_image  = t.uploads[0].is_image;
		
		var ok = '<span class="ok">&#10003;</span>';
		var error = '<span class="error">&#10007;</span>';
		
		t.uploads.shift();
		
		if (t.refresh) clearTimeout(t.refresh);
		
		// -------------------------------------------------------------
		// OK 
		
		if ($.trim(response).match(/^[\d\/]+$/)) {
			
			// response is ID of inserted item 
			
			var id = parseInt(response.split('/').pop());
			
			$("li#"+upload_id).append(ok);
			
			if (t.uploads.length) { 
				
				// more items remaining to be inserted 
				
				if (is_image && t.insert_mode == 'new') {
					if (txp.step == 'edit') t.addImage(id);
					if (txp.event == 'list') t.addImage(id);
				}
				
				t.refresh = setTimeout(function() {
					t.refreshContent();
				},5000);
				
				// insert the next item
				
				t.insertItem(t.uploads[0]);
				
			} else {
				
				// no more items to be inserted 
				
				if (is_image && t.insert_mode == 'new') {
					if (txp.step == 'edit') t.addImage(id);
					if (txp.event == 'list') t.addImage(id);
				}
					
				callback();
				
				if (t.errors == 0) {
				
					t.progress.bar.css('width','100%');
					
					t.hideProgress();
				}
			}
		
		// -------------------------------------------------------------
		// ERROR
		
		} else {
			
			// response is an error 
			
			$("li#"+upload_id).append(error);
			
			$("li#"+upload_id).append('<div class="message">' + response + '</div>');
			
			t.errors = 1;
			
			if (t.uploads.length) { 
				
				// insert the next item 
				
				insetItem(t.uploads[0]);
			
			} else {
				
				// t.refreshContent();
			}
		}
	},

// -----------------------------------------------------------------------------

	refreshContent : function() {
	
		if (txp.step == 'edit') {
			
			if (txp.event == 'image' || txp.event == 'file') {
				
				document.location.reload();
			
			} else {
				
				txp.plugins.filedrop.showArticleImage();
			}
			
		} else if (txp.step == 'list') {
			
			// refresh list table rows
			
			$.get("index.php", { 
				event    :txp.event, 
				win      :txp.winid,
				app_mode :'async',
				refresh_content:1 },
				function(data){
					$('table#list tr.data').remove();
					$('table#list tr.grid').remove();
					$('table#list tr.hr').remove();
					$(data).insertAfter('table#list tr.headers');
					txp.list.init();
				});
		}
	}
}

// -----------------------------------------------------------------------------

txp.plugins.filedrop.addEvent = (function () {
  
	if (document.addEventListener) {
    	
    	return function (el, type, fn) {
      		
      		if (el && el.nodeName || el === window) {
        	
        		el.addEventListener(type, fn, false);
      		
      		} else if (el && el.length) {
        		
        		for (var i = 0; i < el.length; i++) {
          			txp.addEvent(el[i], type, fn);
        		}
			}
		};
	
	} else {
		
		return function (el, type, fn) {
			
			if (el && el.nodeName || el === window) {
    			
    			el.attachEvent('on' + type, function () { return fn.call(el, window.event); });
    		
    		} else if (el && el.length) {
    			
    			for (var i = 0; i < el.length; i++) {
     				txp.addEvent(el[i], type, fn);
    			}
   			}
		};
	}
  
})();
