{if $name eq 'Name' and $is_edit_mode}
	
	{capture name=edit_value}{strip}
		{if $type neq 'folder'}{$file_name}{else}{$value}{/if}
	{/strip}{/capture}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<input name="{$name}[{$id}]" class="text" type="text" value="{$smarty.capture.edit_value}"/>
	</td> 

{/if}