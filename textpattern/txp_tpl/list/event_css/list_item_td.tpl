{if $name eq 'Type' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		
		<select name="{$name}[{$id}]">
			<option value="folder" {if $value eq 'folder'}selected="selected"{/if}>Group</option>
			<option value="css" {if $value eq 'css'}selected="selected"{/if}>CSS</option>
		</select>
	
	</td> 

{/if}