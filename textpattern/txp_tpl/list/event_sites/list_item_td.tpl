{if $name eq 'Name' and $is_edit_mode}
	
	{if $status eq 4} 
		<td class="{$name|lower} view col col-{$pos}">{$value}</td>
	{else}
		<td class="{$name|lower} edit col col-{$pos}">
			<input name="{$name}[{$id}]" class="text" type="text" value="{$value}"/>
		</td> 
	{/if}

	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
{elseif $name eq 'Type' and $is_edit_mode}
	
	<td class="{$name|lower} edit col col-{$pos}">
		<select name="{$name}[{$id}]">
			<option value="folder" {if $value eq 'folder'}selected="selected"{/if}>Group</option>
			<option value="site" {if $value eq 'site'}selected="selected"{/if}>Site</option>
		</select>
	</td> 
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
{elseif $name eq 'DB' and $is_edit_mode}
	
	{if $status eq 4} 
		<td class="{$name|lower} view col col-{$pos}">{$value}</td>
	{else}
		<td class="{$name|lower} edit col col-{$pos}">
			<input name="{$name}[{$id}]" class="text" type="text" value="{$value}"/>
		</td> 
	{/if}
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
{elseif $name eq 'Prefix' and $is_edit_mode}
	
	{if $status eq 4}
		{if $value}
			<td class="{$name|lower} view col col-{$pos}">{$value}</td>
		{else}
			<td class="{$name|lower} view col col-{$pos} blank">&mdash;</td>
		{/if}
	{else}
		<td class="{$name|lower} edit col col-{$pos}">
			<input name="{$name}[{$id}]" class="text" type="text" value="{$value}"/>
		</td> 
	{/if}
	
	<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
	
{/if}