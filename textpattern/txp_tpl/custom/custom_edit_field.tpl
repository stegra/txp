{if $is_not_folder}
<p>
	<label for="custom-name">Name</label>
	<input id="custom-name" type="text" value="{$name}" name="Name" size="20" class="edit"/>
</p>
{/if}

<p>
	<label>Type</label>
	{$type_pop} 
</p>

{if $is_not_folder}
<p>
	<label>Input method</label> {$input_help}
	{$input_pop} 
</p>

<p>
	<label for="custom-default">Default value</label> {$default_help}
	<input id="custom-default" type="text" value="{$default}" name="default" size="20" class="edit"/> 
	
</p>
{/if}
		