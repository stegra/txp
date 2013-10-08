<div class="file {$mode}">

	<script>
		var article = {$article|default:0};
		var path    = '{$path}';
	</script>
	
	<div id="file-add">
		
		<p class="header"><a class="add" href="#"><b>Add File</b></a></p>

		<div class="files">
			
			<a class="cancel" title="Cancel" href="#"><img src="/textpattern/txp_img/cancel.gif" width="10" height="11" border="0" alt="Cancel"/></a>
			
			<ul>
			{section name=i loop=$files}
					<li><a class="add" id="{$files[i].id}" title="{$files[i].filename}" href="#">{$files[i].name}.{$files[i].extension}</a></li>
			{/section}
			</ul>
			
		</div>
		
		<input type="hidden" name="file" value=""/>
		
	</div>
	
	<div id="file-view">

		<div id="file-view-min">

			<p class="header"><a class="toggle" href="#"><b>File</b></a> <span class="ext">{$extension}</span></p>

		</div>
		
		<div id="file-view-max">
	
			<p class="header"><a class="toggle" href="#"><b>File</b></a></p>

			<p class="filename">
				{include file="article/file_name.tpl"}
			</p>

		</div>

	</div>
	
</div>



