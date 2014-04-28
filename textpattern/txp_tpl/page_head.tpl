<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{$lang}" lang="{$lang}" dir="{$lang_dir}">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta name="robots" content="noindex, nofollow" />
	<meta name='url' content="{$cleanurl}">
	
	<title>{$sitename} &#8250; {$area_title} &#8250; {$page_title}</title>

	<link rel="stylesheet" type="text/css" href="{$base}plugins/jquery-ui/css/ui-lightness/jquery-ui.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/pulldown/style.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/colorpicker/css/colorpicker.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/audioplayer/style.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/filedrop/style.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/notes/notes.css"/>
	
	{if $area eq 'presentation' && $mode eq 'edit'}
	<link rel="stylesheet" type="text/css" href="{$base}plugins/codemirror/lib/codemirror.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}plugins/jScrollPane/style/jquery.jscrollpane.css"/>
	<link rel="stylesheet" type="text/css" href="{$base}theme/{$theme}/css/codemirror.css"/>
    {/if}
	
	{$theme_head}
	
	<script type="text/javascript">
	
		var txp = {literal}{{/literal}
			event   : '{$event}', 
			step    : '{$step}', 
			method  : '{$method}', 
			mode    : '{$mode}', 
			view    : '{$view}', 
			linenum : '{$linenum}',
			mini	: {$mini},
			winid   : {$window},
			docid	: {$docid},
			docname	: '{$docname}',
			scroll  : {$scroll}, 
			checked : {$checked},
			list	: {},
			edit	: {},
			plugins : {}
		{literal}}{/literal}
			
	</script>
	
	<script type="text/javascript" src="{$base}js/lib/jquery-1.8.3.min.js"></script>
	<script type="text/javascript" src="{$base}js/lib/jquery-cookie/jquery.cookie.js"></script>
	<script type="text/javascript" src="{$base}js/lib/jquery-ui/js/jquery-ui-1.10.2.custom.min.js"></script>
	<script type="text/javascript" src="{$base}js/lib/jquery-ui/js/jquery.drop.js"></script>
	<script type="text/javascript" src="{$base}js/lib/class.array.js"></script>
	<script type="text/javascript" src="{$base}js/lib/codemirror.js"></script>
	<script type="text/javascript" src="{$base}plugins/pulldown/script.js"></script>
  	<script type="text/javascript" src="{$base}plugins/colorpicker/js/colorpicker.js"></script>
  	<script type="text/javascript" src="{$base}plugins/audioplayer/player.js"></script>
  	<script type="text/javascript" src="{$base}plugins/filedrop/script.js"></script>
  	<script type="text/javascript" src="{$base}plugins/notes/notes.js"></script>
  	
  	{if $area eq 'presentation' && $mode eq 'edit'}
	<script type="text/javascript" src="{$base}plugins/codemirror/lib/codemirror.js"></script>
	<script type="text/javascript" src="{$base}plugins/codemirror/mode/xml/xml.js"></script>
	<script type="text/javascript" src="{$base}plugins/codemirror/mode/css/css.js"></script>
	<script type="text/javascript" src="{$base}plugins/jScrollPane/script/jquery.mousewheel.js"></script>
	<script type="text/javascript" src="{$base}plugins/jScrollPane/script/jquery.jscrollpane.js"></script>
	{/if}
  	
  	<script type="text/javascript" src="{$base}js/global.js"></script>
  	
  	{if $mode_script eq 'list'}
  		<script type="text/javascript" src="{$base}js/txp_mode/txp_mode_list.js"></script>
  		{if $view eq 'div'}
  		<script type="text/javascript" src="{$base}js/txp_mode/txp_mode_list_grid.js"></script>
  		{/if}
  	{/if}
  	
  	{if $mode_script eq 'edit'}
  		<script type="text/javascript" src="{$base}js/txp_mode/txp_mode_edit.js"></script>
  	{/if}
  	
  	{if $event_js}
  		<script type="text/javascript" src="{$base}js/txp_event/txp_event_{$event}.js"></script>
  	{/if}
  	
  	<script type="text/javascript" src="{$base}js/main.js"></script>
  	
  	<script type="text/javascript" language="JavaScript">
		
		txp.cookieEnabled = txp.checkCookies();
		
		if(!txp.cookieEnabled) {literal}{{/literal} confirm('{$cookie}'); {literal}}{/literal} 
		
		{if $unset_cookie}unset_cookie();{/if}
		 
	</script>
</head>
<body id="{$body_id}" class="{$body_class}">

<!-- ======================================================================================================================== -->

{$header}

<!-- ======================================================================================================================== -->

<div id="content" class="{$doctype}">
