{if $is_edit_mode}
	
	<td class="{$name} edit col custom">
		<input name="custom_value_{$name}_{$rowid}[{$id}]" class="text" type="text" value="{$value}"/>
	</td>

{else}

	{if $value}
		<td class="{$name} view col custom">{$value}</td>
	{else}
		<td class="{$name} view col custom blank">&mdash;</td>
	{/if}

{/if}