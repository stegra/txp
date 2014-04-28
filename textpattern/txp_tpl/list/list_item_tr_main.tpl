
<tr class="hr" id="hr1"><td colspan="20"><div><span></span></div></td></tr>

<tr id="article-{$id}" class="
	
	data 
	main-article 
	type-{$type}
	{$display_mode} 
	{$is_checked} 
	{$is_root}">
	
	{$column_data}
		
	{$custom_column_data}
		
	<td class="chbox">
		<input type="checkbox" name="selected[{$id}]" {if $is_checked}checked="yes"{/if} value="{$id}" class="article" id="{$id}"/>
		{if $nextid} 
			<a class="next" title="Next" href="?event={$event}&win={$winid}&id={$nextid}">&#187;</a>
		{/if}
	</td>

</tr>

