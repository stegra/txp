{if $filename}

	<a class="remove" title="Remove this file" onclick="remove_file()" href="#"><img src="/textpattern/txp_img/remove.gif" width="10" height="11" alt="Remove" border="0"/></a>
	<a href="?event=file&step=file_edit&id={$file_id}" title="{$filename}">{$name}.{$extension}</a>

	{if $extension eq 'mp3'}
		{include file="play2.tpl"}
	{/if}

{/if}

