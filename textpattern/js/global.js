txp.init = function() {
	
	console.log('init global');
	
	txp.ext     = [];		// list of file extensions
	txp.key     = '';		// 'COMMAND','SHIFT' or none
	
	// TODO: these should be in txp.list 
	
	txp.open 	= [];		// ids of open folders
	txp.col     = '';		// name of currently selected column
	txp.row     = -1;		// id of currently selected row
	txp.headers = 'show';	// show/hide table headers
	
	txp.plugins.note.init();
	txp.plugins.filedrop.init();
	txp.plugins.colorpicker.init();
	
	if (txp[txp.mode].init) {
		
		txp[txp.mode].init();
	}
	
	if (txp.event == 'prefs') {
		
		txp.list.prefs.init();
	} 
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// disable spellchecking on all elements of type "code" in capable browsers
	
	if (jQuery.browser.mozilla) {
		
		$(".code").attr("spellcheck", false);
	}
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// attach toggle behaviour
	
	$('.lever a[class!=pophelp]').click(txp.toggleDisplayHref);
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	$("select,input,textarea").change(function () {
    
    	$('input.publish').removeClass('saved');
    	
    });
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    
    if (jQuery.browser.mozilla) $('body').addClass('mozilla');
    if (jQuery.browser.webkit) $('body').addClass('webkit');
    if (jQuery.browser.msie) $('body').addClass('msie');
    if (jQuery.browser.opera) $('body').addClass('opera');
    
    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// submit window state before each internal link
	
	$('a').click(function(event) {
		
		var href   = $(this).attr('href');
		var target = $(this).attr('target');
		var rel    = $(this).attr('rel');
		
		if (href == '#') return false;
		if (rel == 'mini') return false;
		
		if ($(this).hasClass('title') && $(this).parents('td.title').length) return; 
		if ($(this).hasClass('ssh')) return;
		if ($(this).hasClass('ftp')) return;
		if ($(this).hasClass('filter')) return;
		if ($(this).hasClass('pophelp')) return;
		if ($(this).hasClass('oggfile')) return;
		
		if ($(this).parents('.alt-select').length) return;
		if ($(this).parents('.audio-player').length) return;
		if ($(this).parents('.panel').length) return;
		if ($(this).parent('.trash').length) return false; 
		if ($(this).parents('.context-menu').length) return false;
		if ($(this).parents('#image-add').length) return false;
	    
					
		if (href.match('mini=')) {
			return;
		} 
		
		event.stopPropagation();
		
		var win_id = (txp.winid && !href.match('win=')) 
			? "&win=" + txp.winid 
			: '';
		
		if (href.substring(0,4) != 'http') {
			
			if (txp.mode == 'list' && !target) {
				
				txp.update_window_session('checked,selcol,scroll',function() {
					
  					document.location.href = href + win_id;
  				});
  			
  			} else if (target == 'mini') {
					
				window.open(href+'&win=new&mini=1','mini','width=750,height=500,scrollbars=yes');
				
			} else {
				
				document.location.href = href + win_id;
			}
			
			return false;
		}
	});
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// "Add Image" link on edit pages 
	
	$("#image-add .header a.add").click( function () {
			
		var width = 550;
		var height = 450;
		
		window.open(this.href,'add-image','width='+width+',height='+height+',scrollbars,resizable');
    		
		return false;
    });
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	// popup help
	
	$("a.pophelp").click( function () {
			    	
		txp.popup($(this).attr('href'),'pophelp',400,400);
		
		return false;
    });
	
	// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	/* 
	$('td.tabs td a').hover( 
		function() {
			var z = parseInt($(this).css('z-index'));
			$(this).css('z-index',z + 100);txp.winid
		},
		function() {
			var z = parseInt($(this).css('z-index'));
			$(this).css('z-index',z - 100);
		}
	);
	*/
}

// -------------------------------------------------------------

txp.update_window_session = function(keys,callback) 
{	
	var post = { 
		event    : txp.event, 
		step     : 'list', 
		app_mode : 'async',
		win		 : txp.winid
	};
	
	keys = keys.split(',');
	
	for (var i = 0; i < keys.length; i++) {
	
		var key = $.trim(keys[i]);
		
		if (key == 'scroll') {
			
			post.scroll = txp.get_scroll_point();
		}
		
		else if (key == 'checked') {
		
			var checked = (document.longform) 
				? document.longform.checked.value 
				: '';
			
			post.checked = checked;
		} 
		
		else if (key == 'selcol') {
			
			post.selcol = txp.col;
		}
		
		else {
			
			post[key] = win[key];
		}
	}
	
	$.post("index.php", post, callback);
	
}

// -------------------------------------------------------------
// admin-side "cookies required" warning

txp.checkCookies = function()
{
	var date = new Date();

	date.setTime(date.getTime() + (60 * 1000));

	document.cookie = 'testcookie=enabled; expired='+date.toGMTString()+'; path=/';

	date.setTime(date.getTime() - (60 * 1000));

	document.cookie = 'testcookie=; expires='+date.toGMTString()+'; path=/';

	return (document.cookie.length > 2) ? true : false;
}

// -------------------------------------------------------------
// auto-centering popup windows

txp.popWin = function(url, width, height, options)
{
	var w = (width) ? width : 400;
	var h = (height) ? height : 400;

	var t = (screen.height) ? (screen.height - h) / 2 : 0;
	var l =	 (screen.width) ? (screen.width - w) / 2 : 0;

	var opt = (options) ? options : 'toolbar = no, location = no, directories = no, '+
		'status = yes, menubar = no, scrollbars = yes, copyhistory = no, resizable = yes';

	var popped = window.open(url, 'popupwindow',
		'top = '+t+', left = '+l+', width = '+w+', height = '+h+',' + opt);

	popped.focus();
}

// -------------------------------------------------------------
// basic confirmation for potentially powerful choice 
// (like deletion, for example)

txp.verify = function(msg) 
{ 	
	if (document.longform.edit_method.value == "empty_trash") { 
		
		msg = "The Trash contains "+msg+" articles.\nAre you sure you to permanently delete them?"; 
		
		return confirm(msg);
	}
	
	if (document.longform.edit_method.value == "delete") { 
	
		return confirm(msg);
	}
}

// -------------------------------------------------------------
// multi-edit checkbox utils

txp.selectall = function() {
	
	var cnt = 0;
	var elem = window.document.longform.elements;
	cnt = elem.length; 
	
	for (var i=0; i < cnt; i++) {
		
		if (parseInt(elem[i].id) != txp.docid) {
		
			if (elem[i].name.match('selected')) {
				if (elem[i].checked == false) toggleCheckbox(elem[i].id);
			}
			
			elem[i].checked = true;
		} 
	} 
}

txp.deselectall = function() {

	var cnt = 0;
	var elem = window.document.longform.elements;
	cnt = elem.length;
	
	for (var i=0; i < cnt; i++) {
		
		if (elem[i].name.match('selected')) {
			if (elem[i].checked == true) toggleCheckbox(elem[i].id);
		}
		elem[i].checked = false;
	}
}

txp.selectrange = function() {
	var inrange = false;
	var cnt = 0;
	var elem = window.document.longform.elements;
	cnt = elem.length;
	for (var i=0; i < cnt; i++) {
		if (elem[i].type == 'checkbox') {
			if (elem[i].checked == true) {
				if (!inrange) 
					inrange = true;
				else 
					inrange = false;
			}
			if (inrange) {
				if (document.longform.checked) {
					if (elem[i].checked == false) toggleCheckbox(elem[i].id);
				}
				elem[i].checked = true;
			}
		}
	}
}

txp.selectrange2 = function() {
	
	var start = {'id':0,'pos':0};
	var stop  = {'id':0,'pos':0};
	
	start.id = txp.checked.pop();
	stop.id  = txp.checked.pop();
	
	txp.checked.push(stop.id);
	txp.checked.push(start.id);
	
	for (var i=0; i<txp.list.rows.length; i++) {
		
		if (txp.list.rows[i].id == start.id) start.pos = txp.list.rows[i].position;
		if (txp.list.rows[i].id == stop.id) stop.pos = txp.list.rows[i].position;
	}
	
	dir = (start.pos < stop.pos) ? 1 : -1;
	
	for (var i=start.pos; i != (stop.pos + dir); i = i + dir) {
		
		txp.list.doCheck(txp.list.rows[i].id);
	}
}

// -------------------------------------------------------------
// ?

txp.cleanSelects = function()
{
	var withsel = document.getElementById('withselected');

	if (withsel && withsel.options[withsel.selectedIndex].value != '')
	{
		return (withsel.selectedIndex = 0);
	}
}

// -------------------------------------------------------------
// event handling
// By S.Andrew -- http://www.scottandrew.com/

txp.addEvent = function(elm, evType, fn, useCapture)
{
	if (elm.addEventListener)
	{
		elm.addEventListener(evType, fn, useCapture);
		return true;
	}

	else if (elm.attachEvent)
	{
		var r = elm.attachEvent('on' + evType, function () { return fn.call(elm, window.event); });
		return r;
	}

	else
	{
		elm['on' + evType] = fn;
	}
}

// -------------------------------------------------------------
// cookie handling

txp.setCookie = function(name, value, days)
{
	if (days)
	{
		var date = new Date();

		date.setTime(date.getTime() + (days*24*60*60*1000));

		var expires = '; expires=' + date.toGMTString();
	}

	else
	{
		var expires = '';
	}

	document.cookie = name + '=' + value + expires + '; path=/';
}

txp.getCookie = function(name)
{
	var nameEQ = name + '=';

	var ca = document.cookie.split(';');

	for (var i = 0; i < ca.length; i++)
	{
		var c = ca[i];

		while (c.charAt(0)==' ')
		{
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0)
		{
			return c.substring(nameEQ.length, c.length);
		}
	}

	return null;
}

txp.deleteCookie = function(name)
{
	txp.setCookie(name, '', -1);
}

// -------------------------------------------------------------
// @see http://www.snook.ca/archives/javascript/your_favourite_1/
txp.getElementsByClass = function(classname, node)
{
	var a = [];
	var re = new RegExp('(^|\\s)' + classname + '(\\s|$)');
	if(node == null) node = document;
	var els = node.getElementsByTagName("*");
	for(var i=0,j=els.length; i<j; i++)
		if(re.test(els[i].className)) a.push(els[i]);
	return a;
}

// -------------------------------------------------------------
// direct show/hide

txp.toggleDisplay = function(obj_id) {
	if (document.getElementById){
		var obj = document.getElementById(obj_id);
		if (obj.style.display == '' || obj.style.display == 'none'){
			var state = 'block';
		} else {
			var state = 'none';
		}
		obj.style.display = state;
	}
}

txp.showDisplay = function(obj_id) {
	if (document.getElementById){
		var obj = document.getElementById(obj_id);
		obj.style.display = 'block';
	}
}

txp.hideDisplay = function(obj_id) {
	if (document.getElementById){
		var obj = document.getElementById(obj_id);
		obj.style.display = 'none';
	}
}

// -------------------------------------------------------------
// direct show/hide referred #segment; decorate parent lever

txp.toggleDisplayHref = function()
{
	var href = $(this).attr('href');
	var lever = $(this).parent('.lever');
	if (href) txp.toggleDisplay(href.substr(1));
	if (lever) {
		if ($(href+':visible').length) {
			lever.addClass('expanded');
		} else {
			lever.removeClass('expanded');
		}
	}
	return false;
}

// -------------------------------------------------------------
// show/hide matching elements

txp.setClassDisplay = function(className, value)
{
	var elements = txp.getElementsByClass(className);
	var is_ie = (navigator.appName == 'Microsoft Internet Explorer');

	for (var i = 0; i < elements.length; i++)
	{
		var tagname = elements[i].nodeName.toLowerCase();
		var type = 'block';

		if (tagname == 'td' || tagname == 'th')
		{
			type = (is_ie ? 'inline' : 'table-cell');
		}

		elements[i].style.display = (value== 1 ? type : 'none');
	}
}

// -------------------------------------------------------------
// toggle show/hide matching elements, and set a cookie to remember

txp.toggleClassRemember = function(className)
{
	var v = getCookie('toggle_' + className);
	v = (v == 1 ? 0 : 1);

	txp.setCookie('toggle_' + className, v, 365);

	txp.setClassDisplay(className, v);
	txp.setClassDisplay(className+'_neg', 1-v);
}

// -------------------------------------------------------------
// show/hide matching elements based on cookie value

txp.setClassRemember = function(className, force)
{
	if (typeof(force) != 'undefined')
		txp.setCookie('toggle_' + className, force, 365);
	var v = getCookie('toggle_' + className);

	txp.setClassDisplay(className, v);
	txp.setClassDisplay(className+'_neg', 1-v);
}

//--------------------------------------------------------------
// AJAX

txp.sendAsyncEvent = function(data, fn)
{
	data.app_mode = 'async';
	$.post('index.php', data, fn, 'xml');
}

// -------------------------------------------------------------

txp.popup = function(url, winName, width, height)
{
	win = window.open(url,winName,"resizable,status=no,scrollbars=yes,width=" + width + ",height=" + height);
	win.focus();
}

// -------------------------------------------------------------

txp.pophelp = function(item,lang) 
{
	url = 'http://rpc.textpattern.com/help/?item=' + item + '&lang=' + lang;
	
	win = window.open(url,'pophelp','width=400,height=400,scrollbars,resizable');
	win.focus();
}

// -------------------------------------------------------------

txp.getQueryVariable = function(variable) 
{
	var query = window.location.search.substring(1);
	var vars = query.split("&");

	for (var i=0;i<vars.length;i++) {
		var pair = vars[i].split("=");
		if (pair[0] == variable) {
			return pair[1];
		}
	}
}

// -------------------------------------------------------------

txp.get_scroll_point = function() 
{
	var scroll = 0;
	
	// Netscape compliant
	
	if (typeof(window.pageYOffset) == 'number') {
		
		scroll = window.pageYOffset;
	
	// DOM compliant
	
	} else if (document.body && document.body.scrollTop) {
		
		scroll = document.body.scrollTop;
	
	// IE6 standards compliant mode
	
	} else if (document.documentElement && document.documentElement.scrollTop) {
		
		scroll = document.documentElement.scrollTop;
	}
	
	// needed for IE6 (when vertical scroll bar is on the top)
	
	return (scroll > 0) ? scroll : '0.0'; 
}

// -------------------------------------------------------------

txp.remove_image = function() {
	
	$.post("index.php", { 
		event: 	  txp.event, 
		step: 	  "remove_image",
		app_mode: "async",
		win:	  txp.winid,
		article:  txp.docid
	}, hide_content_image);
}

txp.hide_content_image = function(data) {
	
	// console.log(data);
	
	$(".image #image-view").hide();
	$(".image #image-add").show();
	$(".image #image-add .images").hide();
}

// -------------------------------------------------------------

txp.SimpleHash = function(text) {    
 
	var hash = 0;
	
 	for (var c = 0; c < text.length; c++) {
 		
 		var num = text.charCodeAt(c);
 		hash += (num != 13) ? num : 0; 
 	}
 	
	return hash;
}

// -------------------------------------------------------------

txp.getClientHeight = function() {

	return (document.compatMode == 'CSS1Compat' && !window.opera) 
		? document.documentElement.clientHeight
		: document.body.clientHeight;
}


