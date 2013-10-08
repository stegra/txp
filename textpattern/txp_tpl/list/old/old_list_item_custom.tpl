{if $edit}
<td>
	{if $type eq 'checkbox'}
		<select name="{$name}[]">
			<option value="0"{if $value eq 'CHECKBOX-OFF'}selected="Yes"{/if}>No</option>
			<option value="1"{if $value eq 'CHECKBOX-ON'}selected="Yes"{/if}>Yes</option>
		</select>
	{else}
		{if $options}
			<select name="{$name}[]" class="list">
				{html_options options=$options selected=$value}
			</select>
		{else}
			<input name="{$name}[]" class="custom" type="text" value="{$value}"/> 
		{/if}
		
	{/if}
</td>
{else}
	{if $value}
		<td>{$value}</td>
	{else}
		<td class="blank">&minus;</td>
	{/if}
{/if}