<html>
<head>
<title>{$txt_select_image}</title>
	<link href="textpattern.css" rel="Stylesheet" type="text/css" />
	<link href="css/textpattern.css{$nocache}" rel="Stylesheet" type="text/css" />
	<link href="css/image_popup.css{$nocache}" rel="Stylesheet" type="text/css" />
	<script src="scripts/lib/Browser.js{$nocache}" language="JavaScript"></script>
	<script src="scripts/article_image_select.js{$nocache}" language="JavaScript"></script>
</head>
<body onLoad="resizeWindow();">

<div id="head">
	<form action="index.php" method="post">
		
		<input type="hidden" name="ID" value="{$id}" />
		<input type="hidden" name="event" value="article" />
		<input type="hidden" name="step" value="image_sel" />
		<input type="hidden" name="nohead" value="1">
		
		Show: 
		<select name="category" class="list" onchange="submit(this.form)">
			{html_options options=$categories selected=$category}
		</select>
		
		<input type="checkbox" name="unused" onchange="submit(this.form)" {if $unused}checked="checked"{/if}>Unused 
		
	</form>
</div>

<div id="images">
	{section name=i loop=$images}
		<a href="#" onMouseOver="hover({$images[i].id},1)" onMouseOut="hover({$images[i].id},0)" onClick="{$images[i].onclick}">
		<img id="{$images[i].id}" src="{$images[i].src}" width="100" height="100" border="0"/></a>
	{/section}
</div>

<form action="index.php" method="post" name="article">

	<input type="hidden" name="ID" value="{$id}" />
	<input type="hidden" name="event" value="article" />
	<input type="hidden" name="step" value="image_add" />
	<input type="hidden" name="image_id" value="" />
	<input type="hidden" name="nohead" value="1">

</form>

</body>
</html>
