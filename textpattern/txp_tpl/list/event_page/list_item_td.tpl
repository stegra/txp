{if $name eq 'Type' and $is_edit_mode}
		
	<td class="{$name|lower} edit col col-{$pos}">
		<select name="{$name}[{$id}]">
			<option value="dir" {if $value eq 'dir'}selected="selected"{/if}>GRP</option>
			<option value="xsl" {if $value eq 'xsl'}selected="selected"{/if}>XSL</option>
			<option value="txp" {if $value eq 'txp'}selected="selected"{/if}>TXP</option>
		</select>
	</td> 
	
{elseif $name eq 'Type' and !$is_edit_mode and $value eq 'dir'}	

	<td class="{$name|lower} view col col-{$pos}">GRP</td>
	
{/if}
	
