{if $name eq 'Articles' and $value}
	
	<td class="articles view col col-{$pos}">
		<a target="mini" title="Show articles" href="index.php?event=list&step=filter&image={$id}">{$value}</a>
	</td> 
	
{elseif $name eq 'Name' and $is_edit_mode}
	
	{capture name=edit_value}{strip}
		{if $type neq 'folder'}{$image_name}{else}{$value}{/if}
	{/strip}{/capture}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<input name="{$name}[{$id}]" class="text" type="text" value="{$smarty.capture.edit_value}"/>
	</td> 

{/if}