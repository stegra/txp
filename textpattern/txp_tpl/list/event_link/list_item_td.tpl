{if $name eq 'url' and !$is_edit_mode and $value}
	
	<td class="url view col col-{$pos}">
		<a title="http://{$value}" target="new" href="http://{$value}">{$value}</a>
	</td> 

{elseif $name eq 'Type' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<select name="{$name}[{$id}]">
			<option value="folder" {if $value eq 'folder'}selected="selected"{/if}>Group</option>
			<option value="link" {if $value eq 'link'}selected="selected"{/if}>Link</option>
		</select>
	</td> 
	
{/if}