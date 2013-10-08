{if $name eq 'Type' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		
		<select name="{$name}[{$id}]">
			<option value="folder" {if $value eq 'folder'}selected="selected"{/if}>Group</option>
			<option value="article" {if $value eq 'article'}selected="selected"{/if}>Article</option>
			<option value="comment" {if $value eq 'comment'}selected="selected"{/if}>Comment</option>
			<option value="file" {if $value eq 'file'}selected="selected"{/if}>File</option>
			<option value="link" {if $value eq 'link'}selected="selected"{/if}>Link</option>
			<option value="misc" {if $value eq 'misc'}selected="selected"{/if}>Misc</option>
			<option value="xsl" {if $value eq 'xsl'}selected="selected"{/if}>XSL</option>
		</select>
	
	</td> 

{/if}