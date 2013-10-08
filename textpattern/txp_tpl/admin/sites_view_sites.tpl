<html>
<head>
	<title>Sites</title>
	<link rel="stylesheet" type="text/css" href="theme/classic/css/page_sites_view.css"/>
	<script type="text/javascript" src="js/lib/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="js/page_sites_view.js"></script>
</head>
<body>
		
	<div id="nav">
	
		<div class="bg">
			
			<form id="sites">
			
				<a class="prev" title="Previous" href="#">&#171;</a>
				
				<select name="site">
					{section name=site loop=$sites}
						<option id="{$smarty.section.site.index + 1}" value="{$sites[site].href}">{$sites[site].Title}</option>
					{/section}
				</select>
				
				<a class="next" title="Next" href="#">&#187;</a>
			
			</form>
		
		</div>
		
	</div>
	
	<div id="display">
		<iframe id="frame1" src="{$src}"></iframe>
	</div>
		
</body>
</html>