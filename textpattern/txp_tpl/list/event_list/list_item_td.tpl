{if $name eq 'Status' and $is_edit_mode}
	
	<td class="status edit multi-select-off col col-{$pos}">
		{html_options name="{$name}[{$id}]" options="$options" selected="$value"}
	</td>

{/if}