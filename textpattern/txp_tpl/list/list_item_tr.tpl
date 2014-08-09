<!-- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -->
<!-- {$id} / {$item_title} / {$item_name} -->

{if $level gt 1}

	{cycle assign="odd_even" values="odd,even"}

	{capture name=class}
		data 
		child
		row-{$row_pos}
		level-{$level}
		type-{$type}
		thumb-{$thumb}
		{$display_mode}
		{$odd_even}
		{$is_first_row}
		{$is_last_row}
		{$is_checked}
		{$is_open} 
		{$is_closed} 
		{$is_folder} 
		{$is_leaf} 
		{$is_alias}
	{/capture}

	<tr id="article-{$id}" data-pos="{$row_pos}" class="{$smarty.capture.class|strip}">
		
		{$column_data}
		
		{$custom_column_data}
		
		<td class="chbox">
			<input type="checkbox" name="selected[{$id}]" {if $is_checked}checked="yes"{/if} value="{$id}" class="article" id="{$id}"/>
		</td>

	</tr>

{/if}

