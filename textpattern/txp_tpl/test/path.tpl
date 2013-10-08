<html>
<head>
	<title>Path Test</title>
	<link href="http://txp.steffigra.com/textpattern/theme/classic/css/test_path.css" type="text/css" rel="stylesheet">
	<style type="text/css" rel="stylesheet">
		div#container {literal} { {/literal} 
			width: {$table_count * 460}px; 
		{literal} } {/literal} 
		div.message {literal} { {/literal} 
			min-height: {$maxlines * 15}px; 
		{literal} } {/literal} 
	</style>
</head>
<body>
	
	<div class="context">
		CONTEXT&#160;&#160;&#160;&#160;
		ID: <b>{$context_id}</b>&#160;&#160;&#160;&#160;
		LEVEL: <b>{$context_level}</b>&#160;&#160;&#160;&#160;
		NAME: <b>{$context_name}</b>&#160;&#160;&#160;&#160;
		PATH: <b>/{$context_path}</b>&#160;&#160;&#160;&#160;
	</div>
	
	<div id="container">
		
		{$tables}
		
	</div>
	
</body>
</html>